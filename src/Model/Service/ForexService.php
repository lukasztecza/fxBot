<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\MarketService;
use HttpClient\ClientFactory;

class ForexService
{
    private const EXTERNAL_DATETIME_FORMAT = 'Y-m-d\TH:i:s.u000\Z';
    private const INTERNAL_DATETIME_FORMAT = 'Y-m-d H:i:s';
    private const BEGINING_DATETIME = '2017-12-01';
    private const INTERVAL = 'P5D';
    private const REAL_PACK = 'real';

    private const INSTRUMENTS = [
        'AUD_USD',
        'AUD_CAD',
/*        'AUD_JPY',
        'AUD_CHF',
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
            $this->priceService->deletePriceById($latestPrice['id']);
        } else {
            $startDate = new \DateTime(self::BEGINING_DATETIME, new \DateTimeZone('UTC'));
        }
        return true;
        $endDate = clone $startDate;
        $startDate = $startDate->format(self::EXTERNAL_DATETIME_FORMAT);
        $endDate->add(new \DateInterval(self::INTERVAL));
        if ($endDate > new \DateTime(null, new \DateTimeZone('UTC'))) {
            $endDate = null;
        } else {
            $endDate = $endDate->format(self::EXTERNAL_DATETIME_FORMAT);
        }
//@TODO something wrong with latest prices deletes properly but still inserts wrong maybe on duplicate key should simply update primary key instrument datetime
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
            $values[] = [
                'pack' => self::REAL_PACK,
                'instrument' => $instrument,
                'datetime' => (\DateTime::createFromFormat(self::EXTERNAL_DATETIME_FORMAT, $value['time']))->format(self::INTERNAL_DATETIME_FORMAT),
                'open' => (int)($value['mid']['o'] * 100000),
                'high' => (int)($value['mid']['h'] * 100000),
                'low' => (int)($value['mid']['l'] * 100000),
                'average' => (int)((($value['mid']['h'] + $value['mid']['l']) / 2)  * 100000),
                'close' => (int)($value['mid']['c'] * 100000),
                'extrema' => null
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

    var_dump($instrument);exit;
        $latestPrice = $this->priceService->getLatestIndicatorsByInstrumentAndPack($instrument, self::REAL_PACK);

        if (!empty($latestPrice['datetime'])) {
            $startDate = date_format(date_create_from_format(self::INTERNAL_DATETIME_FORMAT, $latestPrice['datetime']), self::EXTERNAL_DATETIME_FORMAT);
            $endDate = date(self::EXTERNAL_DATETIME_FORMAT, strtotime($latestPrice['datetime'] . self::INTERVAL));

            // latest record mey be not complete so remove it and fetch new one for it's datetime
            $this->priceService->deletePriceById($latestPrice['id']);
        } else {
            $startDate = date(self::EXTERNAL_DATETIME_FORMAT, strtotime(self::BEGINING_DATETIME));
            $endDate = date(self::EXTERNAL_DATETIME_FORMAT, strtotime(self::BEGINING_DATETIME . self::INTERVAL));
        }

        $query = ['from' => $startDate, 'instrument' => $instrument, 'period' => 86400];
        $realValues = $this->client->get(['labs' => 'v1', 'calendar' => null], $query);

        if (empty($realValues['body']['candles']) || empty($realValues['body']['instrument'])) {
            trigger_error('Got wrong response ' . var_export($realValues ,true), E_USER_NOTICE);

            return false;
        }

        $prices = $this->buildValuesToStore($realValues['body']['candles'], $realValues['body']['instrument']);
        $result = $this->priceService->savePrices($prices);

        return true;
    }

    private function buildValuesToStore(array $realValues, string $instrument) : array
    {
        $values = [];
        foreach ($realValues as $key => $value) {
            $values[] = [
                'pack' => self::REAL_PACK,
                'instrument' => $instrument,
                'datetime' => date_format(date_create_from_format(self::EXTERNAL_DATETIME_FORMAT, $value['time']), self::INTERNAL_DATETIME_FORMAT),
                'open' => (int)($value['mid']['o'] * 100000),
                'high' => (int)($value['mid']['h'] * 100000),
                'low' => (int)($value['mid']['l'] * 100000),
                'average' => (int)((($value['mid']['h'] + $value['mid']['l']) / 2)  * 100000),
                'close' => (int)($value['mid']['c'] * 100000),
                'extrema' => null
            ];
        }

        return $values;
    }



}
