<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Service\FetchingServiceInterface;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\MarketService;
use HttpClient\ClientFactory;

class ForexFactoryFetchingService implements FetchingServiceInterface
{
    private const INTERNAL_DATETIME_FORMAT = 'Y-m-d H:i:s';
    private const BEGINING_DATETIME = '2017-01-01 00:00:00';
    private const INTERVAL = 'P1M';

    private const CALENDAR_TABLE_START = '<table class="calendar__table">';
    private const CALENDAR_TABLE_END = '<div class="foot">';

    private const DAY_KEY = 0;
    private const TIME_KEY = 1;
    private const INSTRUMENT_KEY = 2;
    private const NAME_KEY = 4;
    private const ACTUAL_KEY = 6;
    private const FORECAST_KEY = 7;

    private $priceInstruments;
    private $indicatorService;
    private $forexFactoryClient;

    public function __construct(
        array $priceInstruments,
        IndicatorService $indicatorService,
        ClientFactory $clientFactory
    ) {
        $this->priceInstruments = $priceInstruments;
        $this->indicatorService = $indicatorService;
        $this->forexFactoryClient = $clientFactory->getClient('forexFactoryClient');
    }

    public function populatePrices() : bool
    {
        trigger_error('Tried to populate prices using forex factory fetching service. This is not supported yet', E_USER_NOTICE);

        return false;
    }

    public function populateIndicators() : bool
    {
        $latestIndicator = $this->indicatorService->getLatestIndicator();
        $latestDateTime = $latestIndicator['datetime'] ?? null;
        $dateTimes = $this->getDateTimesByLatest($latestDateTime);

        try {
            $response = $this->forexFactoryClient->getIndicators(
                $dateTimes['start']->format('M') . $dateTimes['start']->format('d') . '.' . $dateTimes['start']->format('Y') . '-' .
                $dateTimes['end']->format('M') . $dateTimes['end']->format('d') . '.' . $dateTimes['end']->format('Y')
            );
        } catch (\Throwable $e) {
            trigger_error(
                'Got an exception response trying to get indicators with message ' . $e->getMessage() . ' for instrument ' .
                var_export($instrument ,true) . ' and dates ' . var_export($dateTimes, true), E_USER_NOTICE
            );

            return false;
        }

        $indicators = $this->buildIndicatorsValuesToStore($response['body'], $dateTimes['start'], $dateTimes['end']);
        if (empty($this->indicatorService->saveIndicators($indicators))) {
            return false;
        }

        return true;
    }

    private function buildIndicatorsValuesToStore(string $input, \DateTime $startDateTime, \DateTime $endDateTime) : array
    {
        $start = strpos($input, self::CALENDAR_TABLE_START);
        $end = strpos($input, self::CALENDAR_TABLE_END);
        $length = $end - $start;
        $output = substr($input, $start, $length);
        $dom = new \DOMDocument();
        $dom->loadHTML($output);
        $data = [];
        $rows = $dom->getElementsByTagName('tr');
        $currentDay = 1;
        $currentTime = '00:00:00';

        foreach ($rows as $row) {
            $dataChunk = [];
            $cells = $row->getElementsByTagName('td');
            foreach ($cells as $cell) {
                $dataChunk[] = $cell->nodeValue;
            }
            if (count($dataChunk) > 9) {
                $instrument = $dataChunk[self::INSTRUMENT_KEY];
                $included = false;
                foreach ($this->priceInstruments as $priceInstrument) {
                    if (!empty($instrument) && strpos($priceInstrument, $instrument) !== false) {
                        $included = true;
                        break 1;
                    }
                }
                if (!$included) {
                    continue 1;
                }

                $actual = preg_replace('/[^0-9\.-]/', '', $dataChunk[self::ACTUAL_KEY]);
                if (empty($actual)) {
                    continue 1;
                }

                $unit = preg_replace('/[0-9\.-]/', '', $dataChunk[self::ACTUAL_KEY]);
                $forecast = preg_replace('/[^0-9\.-]/', '', $dataChunk[self::FORECAST_KEY]);

                $time = preg_replace('/[^0-9:apm]/', '', $dataChunk[self::TIME_KEY]);
                if (strpos($time, 'pm') !== false) {
                    $time = str_replace('pm', '', $time);
                    $time .= ':00';
                    $timeElements = explode(':', $time);
                    if ($timeElements[0] != 12) {
                        $timeElements[0] += 12;
                    }
                    $time = implode(':', $timeElements);
                } elseif (strpos($time, 'am') !== false) {
                    $time = str_replace('am', '', $time);
                    $time .= ':00';
                } else {
                    $time = null;
                }
                $currentTime = !empty($time) ? $time : $currentTime;
                $day = preg_replace('/[^0-9]/', '', $dataChunk[self::DAY_KEY]);
                $currentDay = !empty($day) ? $day : $currentDay;
//@TODO get day by current day but switch month when it reaches down
                $data[] = [
                    'instrument' => $instrument,
                    'datetime' => \DateTime::createFromFormat(
                        'Y-M-j H:i:s', $year. '-' . $month . '-' . $currentDay . ' ' . $currentTime
                    )->format('Y-m-d H:i:s'),
                    'name' => trim($dataChunk[self::NAME_KEY]) . (!empty($unit) ? ' ' . $unit : ''),
                    'type' => $this->getTypeByInstrumentAndName($dataChunk[self::INSTRUMENT_KEY], $dataChunk[self::NAME_KEY]),
                    'actual' => $actual,
                    'forecast' => $forecast
                ];
            }
        }

        return $data;
    }

    private function getDateTimesByLatest(string $dateTime = null) : array
    {
        if (!empty($dateTime)) {
            $startDate = new \DateTime($dateTime, new \DateTimeZone('UTC'));
        } else {
            $startDate = new \DateTime(self::BEGINING_DATETIME, new \DateTimeZone('UTC'));
        }

        $endDate = clone $startDate;
        $endDate->add(new \DateInterval(self::INTERVAL));
        if ($endDate > new \DateTime(null, new \DateTimeZone('UTC'))) {
            $endDate = null;
        }

        return [
            'start' => $startDate,
            'end' => $endDate
        ];
    }

    private function getTypeByInstrumentAndName(string $instrument, string $name) : ?string
    {
        $name = strtolower($name);

        switch ($instrument) {
            case 'USD':
                switch (true) {
                    case strpos($name, 'ppi') !== false && strpos($name, 'core') === false:
                        return 'PPI';
                    break 2;
                }
                break;
        }

        return null;
    }
}
