<?php
namespace TinyApp\Model\Command;

use TinyApp\Model\Command\CommandResult;
use TinyApp\Model\Service\MarketService;

class PopulateRealCommand implements CommandInterface
{
    private const EXTERNAL_DATETIME_FORMAT = 'Y-m-d\TH:i:s.u000\Z';
    private const INTERNAL_DATETIME_FORMAT = 'Y-m-d H:i:s';
    private const BEGINING_DATETIME = '2017-09-01';
    private const INTERVAL = ' +2 weeks';

    private $marketService;
    private $client;

    public function __construct(MarketService $marketService, $clientFactory)
    {
        $this->marketService = $marketService;
        $this->client = $clientFactory->getClient('oandaClient');
    }

    public function execute() : CommandResult
    {
        $latestRecord = $this->marketService->getLatestRecord();

        if (!empty($latestRecord['datetime'])) {
            $startDate = date_format(date_create_from_format(self::INTERNAL_DATETIME_FORMAT, $latestRecord['datetime']), self::EXTERNAL_DATETIME_FORMAT);
            $endDate = date(self::EXTERNAL_DATETIME_FORMAT, strtotime($latestRecord['datetime'] . self::INTERVAL));
        } else {
            $startDate = date(self::EXTERNAL_DATETIME_FORMAT, strtotime(self::BEGINING_DATETIME));
            $endDate = date(self::EXTERNAL_DATETIME_FORMAT, strtotime(self::BEGINING_DATETIME . self::INTERVAL));
        }

        $query = ['from' => $startDate, 'granularity' => 'M15'];
        if ($endDate < date(self::EXTERNAL_DATETIME_FORMAT, time())) {
            $query['to'] = $endDate;
        }
        $realValues = $this->client->get(
            ['v3' => 'instruments', 'EUR_USD' => 'candles'],
            $query
        );

        if (empty($realValues['body']['candles']) || empty($realValues['body']['instrument'])) {
            trigger_error('Got wrong response ' . var_export($realValues ,true), E_USER_NOTICE);
            return new CommandResult(false, 'wrong response');
        }

        $values = $this->buildValuesToStore($realValues['body']['candles'], $realValues['body']['instrument']);
        $result = $this->marketService->saveValues($values);

        return new CommandResult(true, 'inserted');
    }

    private function buildValuesToStore(array $realValues, string $instrument) : array
    {
        $values = [];
        foreach ($realValues as $key => $value) {
            $values[] = [
                'pack' => 'real',
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
