<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Service\FetchingServiceInterface;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\MarketService;
use HttpClient\ClientFactory;

class OandaFetchingService implements FetchingServiceInterface
{
    private const INTERNAL_DATETIME_FORMAT = 'Y-m-d H:i:s';
    private const OANDA_DATETIME_FORMAT = 'Y-m-d\TH:i:s.u000\Z';

    private const BEGINING_DATETIME = '2010-01-01 00:00:00';
    private const SHORT_INTERVAL = 'P14D';
    private const LONG_INTERVAL = 'P1Y';
    private const UNIX_TIMESTAMP_FORMAT = 'U';

    private const VALID_PERIODS = [
        '1h' => 3600,
        '12h' => 43200,
        '1d' => 86400,
        '1w' => 604800,
        '1m' => 2592000,
        '3m' => 7776000,
        '6m' => 15552000,
        '1y' => 31536000
    ];

    private $priceInstruments;
    private $priceService;
    private $indicatorService;
    private $oandaClient;

    public function __construct(
        array $priceInstruments,
        PriceService $priceService,
        IndicatorService $indicatorService,
        ClientFactory $clientFactory
    ) {
        $this->priceInstruments = $priceInstruments;
        $this->priceService = $priceService;
        $this->indicatorService = $indicatorService;
        $this->oandaClient = $clientFactory->getClient('oandaClient');
    }

    public function populatePrices() : bool
    {
        $expectedSuccesses = count($this->priceInstruments);
        $successes = 0;
        foreach ($this->priceInstruments as $instrument) {
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
        $latestPrice = $this->priceService->getLatestPriceByInstrument($instrument);
        $latestDateTime = $latestPrice['datetime'] ?? null;
        $dateTimes = $this->getDateTimesByLatest($latestDateTime, self::SHORT_INTERVAL);
        $this->formatDateTimes($dateTimes);
        try {
            $response = $this->oandaClient->getPrices($instrument, $dateTimes['start'], $dateTimes['end']);
        } catch (\Throwable $e) {
            trigger_error(
                'Got an exception response trying to get prices with message ' . $e->getMessage() . ' for instrument ' .
                var_export($instrument ,true) . ' and dates ' . var_export($dateTimes, true), E_USER_NOTICE
            );

            return false;
        }

        if (empty($response['body']['candles']) || empty($response['body']['instrument'])) {
            trigger_error('Got wrong response ' . var_export($response ,true), E_USER_NOTICE);

            return false;
        }

        $prices = $this->buildPricesValuesToStore($response['body']['candles'], $response['body']['instrument']);
        if (empty($this->priceService->savePrices($prices))) {
            return false;
        }

        return true;
    }

    private function buildPricesValuesToStore(array $realValues, string $instrument) : array
    {
        $values = [];
        foreach ($realValues as $key => $value) {
            if (
                !isset($value['time']) ||
                !isset($value['mid']['o']) ||
                !isset($value['mid']['h']) ||
                !isset($value['mid']['l']) ||
                !isset($value['mid']['c'])
            ) {
                trigger_error('Got wrong structure for price ' . var_export($value, true) . ' ignoring this value', E_USER_NOTICE);
                continue;
            }

            $values[] = [
                'instrument' => $instrument,
                'datetime' => (
                    \DateTime::createFromFormat(self::OANDA_DATETIME_FORMAT, $value['time'])
                )->format(self::INTERNAL_DATETIME_FORMAT),
                'open' => $value['mid']['o'],
                'high' => $value['mid']['h'],
                'low' => $value['mid']['l'],
                'close' => $value['mid']['c']
            ];
        }

        return $values;
    }

    private function storeIndicators() : bool
    {
        $latestIndicator = $this->indicatorService->getLatestIndicator();
        $latestDateTime = $latestIndicator['datetime'] ?? null;
        $dateTimes = $this->getDateTimesByLatest($latestDateTime, self::LONG_INTERVAL);
        $period = $this->getPeriodByDateTimes($dateTimes['start'], $dateTimes['end']);

        try {
            $response = $this->oandaClient->getIndicators($period);
        } catch (\Throwable $e) {
            trigger_error(
                'Got an exception response trying to get indicators with message ' . $e->getMessage() . ' for instrument ' .
                var_export($instrument ,true) . ' and dates ' . var_export($dateTimes, true), E_USER_NOTICE
            );

            return false;
        }

        $indicators = $this->buildIndicatorsValuesToStore($response['body']);
        if (empty($this->indicatorService->saveIndicators($indicators))) {
            return false;
        }

        return true;
    }

    private function buildIndicatorsValuesToStore(array $realValues) : array
    {
        $values = [];
        foreach ($realValues as $key => $value) {
            if (
                !isset($value['currency']) ||
                !isset($value['timestamp']) ||
                !isset($value['title']) ||
                !isset($value['actual'])
            ) {
                trigger_error('Got wrong structure for indicator ' . var_export($value, true) . ' ignoring this value', E_USER_NOTICE);
                continue;
            }

            $values[] = [
                'instrument' => $value['currency'],
                'datetime' => (
                    \DateTime::createFromFormat(self::UNIX_TIMESTAMP_FORMAT, $value['timestamp'])
                )->format(self::INTERNAL_DATETIME_FORMAT),
                'name' => $value['title'] . !empty($value['unit']) ? ' ' . $value['unit'] : '',
                'forecast' => $value['forecast'] ?? null,
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

    private function formatDateTimes(array &$dateTimes) : void
    {
        foreach ($dateTimes as $key => $dateTime) {
            $dateTimes[$key] = $dateTime ? $dateTime->format(self::OANDA_DATETIME_FORMAT) : null;
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
