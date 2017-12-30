<?php
namespace TinyApp\Model\Command;

use TinyApp\Model\Command\CommandResult;
use TinyApp\Model\Service\PriceService;

class PopulateRealCommand implements CommandInterface
{
    private const EXTERNAL_DATETIME_FORMAT = 'Y-m-d\TH:i:s.u000\Z';
    private const INTERNAL_DATETIME_FORMAT = 'Y-m-d H:i:s';
    private const BEGINING_DATETIME = '2017-09-01';
    private const INTERVAL = ' +2 weeks';
    private const REAL_PACK = 'real';

    private const INSTRUMENTS = [
        'AUD_USD',
        'AUD_CAD',
        'AUD_JPY',
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
        'USD_CHF'
    ];

    private $priceService;
    private $client;

    public function __construct(PriceService $priceService, $clientFactory)
    {
        $this->priceService = $priceService;
        $this->client = $clientFactory->getClient('oandaClient');
    }

    public function execute() : CommandResult
    {
        $instrument = 'EUR_USD';
        $expectedSuccesses = count(self::INSTRUMENTS);
        $successes = 0;
        foreach (self::INSTRUMENTS as $instrument) {
            if ($this->storePrices($instrument)) {
                $successes++;
            }
        }

        if ($successes === $expectedSuccesses) {
            return new CommandResult(true, 'successfully fetched all prices');
        } else {
            return new CommandResult(false, 'failed to fetch prices got: ' . $successes . ' from expected: ' . $expectedSuccesses);
        }
    }

    private function storePrices($instrument) : bool
    {
        $latestPrice = $this->priceService->getLatestPriceByInstrumentAndPack($instrument, self::REAL_PACK);

        if (!empty($latestPrice['datetime'])) {
            $startDate = date_format(date_create_from_format(self::INTERNAL_DATETIME_FORMAT, $latestPrice['datetime']), self::EXTERNAL_DATETIME_FORMAT);
            $endDate = date(self::EXTERNAL_DATETIME_FORMAT, strtotime($latestPrice['datetime'] . self::INTERVAL));

            // latest record mey be not complete so remove it and fetch new one for it's datetime
            $this->priceService->deletePriceById($latestPrice['id']);
        } else {
            $startDate = date(self::EXTERNAL_DATETIME_FORMAT, strtotime(self::BEGINING_DATETIME));
            $endDate = date(self::EXTERNAL_DATETIME_FORMAT, strtotime(self::BEGINING_DATETIME . self::INTERVAL));
        }

        $query = ['from' => $startDate, 'granularity' => 'M15'];
        if ($endDate < date(self::EXTERNAL_DATETIME_FORMAT, time())) {
            $query['to'] = $endDate;
        }
        $realValues = $this->client->get(
            ['v3' => 'instruments', $instrument => 'candles'],
            $query
        );

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
