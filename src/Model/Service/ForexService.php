<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\MarketService;
use HttpClient\ClientFactory;

class ForexService
{
    private const EXTERNAL_DATETIME_FORMAT = 'Y-m-d\TH:i:s.u000\Z';
    private const INTERNAL_DATETIME_FORMAT = 'Y-m-d H:i:s';
    private const UNIX_TIMESTAMP_FORMAT = 'U';
    private const BEGINING_DATETIME = '2018-01-12 15:00:00';
    private const INTERVAL = 'P5D';
    private const REAL_PACK = 'real';

    private const INSTRUMENTS = [
//        'AUD_USD',
        'AUD_CAD',
        'AUD_JPY',
/*        'AUD_CHF',
        'CAD_CHF',
        'CAD_JPY',
        'CHF_JPY',
        'EUR_USD',
        'EUR_GBP',
        'EUR_JPY',
        'EUR_CHF',
        'EUR_AUD',
        'EUR_CAD',
        'GBP_USD',
        'GBP_CAD',
        'GBP_JPY',
        'GBP_CHF',
        'GBP_AUD',
        'USD_JPY',
        'USD_CAD',
        'USD_CHF' */
    ];

    private $priceService;
    private $indicatorService;
    private $clientFactory;

    public function __construct(PriceService $priceService, IndicatorService $indicatorService, ClientFactory $clientFactory) {
        $this->priceService = $priceService;
        $this->indicatorService = $indicatorService;
        $this->oandaClient = $clientFactory->getClient('oandaClient');
    }

    public function populatePrices() : bool
    {
        $expectedSuccesses = count(self::INSTRUMENTS);
        $successes = 0;
        foreach (self::INSTRUMENTS as $instrument) {
            if ($this->storePrices($instrument)) {
                $successes++;
            }
        }

        return $successes === $expectedSuccesses;
    }

    private function storePrices(string $instrument) : bool
    {
        $latestPrice = $this->priceService->getLatestPriceByInstrumentAndPack($instrument, self::REAL_PACK);
        if (!empty($latestPrice['datetime'])) {
            $startDate = new \DateTime($latestPrice['datetime'], new \DateTimeZone('UTC'));
        } else {
            $startDate = new \DateTime(self::BEGINING_DATETIME, new \DateTimeZone('UTC'));
        }

        $endDate = clone $startDate;
        $startDate = $startDate->format(self::EXTERNAL_DATETIME_FORMAT);
        $endDate->add(new \DateInterval(self::INTERVAL));
        if ($endDate > new \DateTime(null, new \DateTimeZone('UTC'))) {
            $endDate = null;
        } else {
            $endDate = $endDate->format(self::EXTERNAL_DATETIME_FORMAT);
        }

        $realValues = $this->oandaClient->getPrices(
            $instrument,
            $startDate,
            $endDate
        );

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

    public function populateIndicators() : bool
    {
        $expectedSuccesses = count(self::INSTRUMENTS);
        $successes = 0;
        foreach (self::INSTRUMENTS as $instrument) {
            if ($this->storeIndicators($instrument)) {
                $successes++;
            }
        }

        return $successes === $expectedSuccesses;
    }

    private function storeIndicators($instrument) : bool
    {
        $latestIndicator = $this->indicatorService->getLatestIndicatorByInstrumentAndPack($instrument, self::REAL_PACK);

        if (!empty($latestPrice['datetime'])) {
            $startDate = date_format(date_create_from_format(self::INTERNAL_DATETIME_FORMAT, $latestPrice['datetime']), self::EXTERNAL_DATETIME_FORMAT);
            $endDate = date(self::EXTERNAL_DATETIME_FORMAT, strtotime($latestPrice['datetime'] . self::INTERVAL));

        } else {
            $startDate = date(self::EXTERNAL_DATETIME_FORMAT, strtotime(self::BEGINING_DATETIME));
            $endDate = date(self::EXTERNAL_DATETIME_FORMAT, strtotime(self::BEGINING_DATETIME . self::INTERVAL));
        }
//@TODO figure out proper periods based on dates
$period = 604800;

        $query = ['from' => $startDate, 'instrument' => $instrument, 'period' => $period];
        $realValues = $this->oandaClient->get(['labs' => 'v1', 'calendar' => null], $query);
        $indicators = $this->buildValuesToStore($realValues['body']);
        $result = $this->indicatorService->saveIndicators($indicators);

        return true;
    }

    private function buildValuesToStore(array $realValues) : array
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
}
