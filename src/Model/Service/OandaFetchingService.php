<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Service\FetchingServiceInterface;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\MarketService;
use HttpClient\ClientFactory;

class OandaFetchingService implements FetchingServiceInterface
{
    private const EXTERNAL_DATETIME_FORMAT = 'Y-m-d\TH:i:s.u000\Z';
    private const INTERNAL_DATETIME_FORMAT = 'Y-m-d H:i:s';
    private const BEGINING_DATETIME = '2017-10-01 00:00:00';
    private const SHORT_INTERVAL = 'P14D';
    private const LONG_INTERVAL = 'P3M';
    private const UNIX_TIMESTAMP_FORMAT = 'U';

    private const VALID_PERIODS = [
        '1h' => 3600,
        '12h' => 43200,
        '1d' => 86400,
        '1w' => 604800,
        '1m' => 2592000,
        '3m' => 7776000,
        '6m' => 15552000
    ];

    private const REAL_PACK = 'real';

    private const PRICE_INSTRUMENTS = [
        'AUD_USD',
        'AUD_CAD',
        'AUD_JPY',
        'AUD_CHF',
        'CAD_CHF',
        'CAD_JPY',
        'CHF_JPY',
        'EUR_GBP',
        'EUR_USD',
        'EUR_JPY',
        'EUR_CHF',
        'EUR_AUD',
        'EUR_CAD',
        'GBP_JPY',
        'GBP_USD',
        'GBP_CAD',
        'GBP_CHF',
        'GBP_AUD',
        'USD_JPY',
        'USD_CAD',
        'USD_CHF'
    ];

    private $priceService;
    private $indicatorService;
    private $oandaClient;

    public function __construct(PriceService $priceService, IndicatorService $indicatorService, ClientFactory $clientFactory) {
        $this->priceService = $priceService;
        $this->indicatorService = $indicatorService;
        $this->oandaClient = $clientFactory->getClient('oandaClient');
    }

    public function populatePrices() : bool
    {
        $expectedSuccesses = count(self::PRICE_INSTRUMENTS);
        $successes = 0;
        foreach (self::PRICE_INSTRUMENTS as $instrument) {
            if ($this->storePrices($instrument)) {
                $successes++;
            }
        }

        return $successes === $expectedSuccesses;
    }

    public function populateIndicators() : bool
    {
        return $this->storeIndicators();
    }

    private function storePrices(string $instrument) : bool
    {
        $latestPrice = $this->priceService->getLatestPriceByInstrumentAndPack($instrument, self::REAL_PACK);
        $latestDateTime = $latestPrice['datetime'] ?? null;
        $dateTimes = $this->getDateTimesByLatest($latestDateTime, self::SHORT_INTERVAL);
        $this->formatDateTimes($dateTimes, self::EXTERNAL_DATETIME_FORMAT);
        $realValues = $this->oandaClient->getPrices($instrument, $dateTimes['start'], $dateTimes['end']);

        if (empty($realValues['body']['candles']) || empty($realValues['body']['instrument'])) {
            trigger_error('Got wrong response ' . var_export($realValues ,true), E_USER_NOTICE);

            return false;
        }

        $prices = $this->buildPricesValuesToStore($realValues['body']['candles'], $realValues['body']['instrument']);
        if (empty($this->priceService->savePrices($prices))) {
            return false;
        }

        return true;
    }

    private function storeIndicators() : bool
    {
        $latestIndicator = $this->indicatorService->getLatestIndicatorByPack(self::REAL_PACK);
        $latestDateTime = $latestIndicator['datetime'] ?? null;
        $dateTimes = $this->getDateTimesByLatest($latestDateTime, self::LONG_INTERVAL);
        $period = $this->getPeriodByDateTimes($dateTimes['start'], $dateTimes['end']);

        $realValues = $this->oandaClient->getIndicators($period);
        $indicators = $this->buildIndicatorsValuesToStore($realValues['body']);
        $result = $this->indicatorService->saveIndicators($indicators);

        return true;
    }

    private function buildPricesValuesToStore(array $realValues, string $instrument) : array
    {
        $values = [];
        foreach ($realValues as $key => $value) {
            if (
                empty($value['time']) ||
                empty($value['mid']['o']) ||
                empty($value['mid']['h']) ||
                empty($value['mid']['l']) ||
                empty($value['mid']['c'])
            ) {
                trigger_error('Got wrong structure for price ' . var_export($value, true) . ' ignoring this value', E_USER_NOTICE);
                continue;
            }

            $values[] = [
                'pack' => self::REAL_PACK,
                'instrument' => $instrument,
                'datetime' => (\DateTime::createFromFormat(self::EXTERNAL_DATETIME_FORMAT, $value['time']))->format(self::INTERNAL_DATETIME_FORMAT),
                'open' => $value['mid']['o'],
                'high' => $value['mid']['h'],
                'low' => $value['mid']['l'],
                'close' => $value['mid']['c']
            ];
        }

        return $values;
    }

    private function buildIndicatorsValuesToStore(array $realValues) : array
    {
        $values = [];
        foreach ($realValues as $key => $value) {
            if (
                empty($value['currency']) ||
                empty($value['timestamp']) ||
                empty($value['title']) ||
                empty($value['actual'])
            ) {
                trigger_error('Got wrong structure for indicator ' . var_export($value, true) . ' ignoring this value', E_USER_NOTICE);
                continue;
            }

            $values[] = [
                'pack' => self::REAL_PACK,
                'instrument' => $value['currency'],
                'datetime' => (\DateTime::createFromFormat(self::UNIX_TIMESTAMP_FORMAT, $value['timestamp']))->format(self::INTERNAL_DATETIME_FORMAT),
                'name' => $value['title'],
                'unit' => $value['unit'] ?? null,
                'forecast' => $value['forecast'] ?? null,
                'market' => $value['market'] ?? null,
                'actual' => $value['actual']
            ];
        }

        return $values;
    }

    private function getDateTimesByLatest(string $dateTime = null, string $interval) : array
    {
        if (!empty($dateTime)) {
            $startDate = new \DateTime($dateTime, new \DateTimeZone('UTC'));
        } else {
            $startDate = new \DateTime(self::BEGINING_DATETIME, new \DateTimeZone('UTC'));
        }

        $endDate = clone $startDate;
        $endDate->add(new \DateInterval($interval));
        if ($endDate > new \DateTime(null, new \DateTimeZone('UTC'))) {
            $endDate = null;
        }

        return [
            'start' => $startDate,
            'end' => $endDate
        ];
    }

    private function formatDateTimes(array &$dateTimes, string $format) : void
    {
        foreach ($dateTimes as $key => $dateTime) {
            $dateTimes[$key] = $dateTime ? $dateTime->format(self::EXTERNAL_DATETIME_FORMAT) : null;
        }
    }

    private function getPeriodByDateTimes(\DateTime $startDate, \DateTime $endDate = null) : int
    {
        if (empty($endDate)) {
            $endDate = new \DateTime(null, new \DateTimeZone('UTC'));
        }

        $difference = $startDate->diff($endDate);
        $seconds = $difference->format('%a') * 86400;
        foreach (self::VALID_PERIODS as $period) {
            if ($period < $seconds) {
                continue;
            }
            return $period;
        }

        trigger_error(
            'Could not get long enough period for ' . var_export($startDate, true) .
            ' and ' . var_export($endDate, true) . ' setting the longest available', E_USER_NOTICE
        );
        return $period;
    }
}
