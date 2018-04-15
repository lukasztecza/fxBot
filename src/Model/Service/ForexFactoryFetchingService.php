<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Service\FetchingServiceAbstract;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\MarketService;
use HttpClient\ClientFactory;

class ForexFactoryFetchingService extends FetchingServiceAbstract
{
    private const BEGINING_DATETIME = '2017-02-05 00:00:00';
    private const INTERVAL = 'P14D';

    private const CALENDAR_TABLE_START = '<table class="calendar__table">';
    private const CALENDAR_TABLE_END = '<div class="foot">';

    private const INTERNAL_DATE_FORMAT = 'Y-m-d';
    private const INTERNAL_DAY_FORMAT = 'm-d';
    private const INTERNAL_TIME_FORMAT = 'H:i:s';
    private const FOREX_FACTORY_TIME_FORMAT = 'h:ia';
    private const FOREX_FACTORY_DAY_FORMAT = 'M j';

    private const DATE_KEY = 0;
    private const TIME_KEY = 1;
    private const INSTRUMENT_KEY = 2;
    private const NAME_KEY = 4;
    private const ACTUAL_KEY = 6;
    private const FORECAST_KEY = 7;

    private $instruments;
    private $indicatorService;
    private $forexFactoryClient;

    public function __construct(
        array $priceInstruments,
        IndicatorService $indicatorService,
        ClientFactory $clientFactory
    ) {
        $this->instruments = [];
        foreach ($priceInstruments as $priceInstrument) {
            $instruments = explode('_', $priceInstrument);
            foreach ($instruments as $instrument) {
                $this->instruments[$instrument] = true;
            }
        }
        $this->instruments = array_keys($this->instruments);
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
        $currentDay = $startDateTime->format(self::INTERNAL_DAY_FORMAT);
        $currentTime = $startDateTime->format(self::INTERNAL_TIME_FORMAT);

        foreach ($rows as $row) {
            $dataChunk = [];
            $cells = $row->getElementsByTagName('td');
            foreach ($cells as $cell) {
                $dataChunk[] = $cell->nodeValue;
            }
            if (count($dataChunk) > 9) {
                $instrument = $dataChunk[self::INSTRUMENT_KEY];
                if (!in_array($instrument, $this->instruments)) {
                    continue 1;
                }

                $time = preg_replace('/[^0-9:apm]/', '', $dataChunk[self::TIME_KEY]);
                $time = \DateTime::createFromFormat(self::FOREX_FACTORY_TIME_FORMAT, $time);
                if (!empty($time)) {
                    $currentTime = $time->format(self::INTERNAL_TIME_FORMAT);
                }
                $date = trim($dataChunk[self::DATE_KEY]);
                $date = substr($date, 3);
                $date = \DateTime::createFromFormat(self::FOREX_FACTORY_DAY_FORMAT, $date);
                if (!empty($date)) {
                    $currentDay = $date->format(self::INTERNAL_DAY_FORMAT);
                }

                $actual = preg_replace('/[^0-9\.-]/', '', $dataChunk[self::ACTUAL_KEY]);
                if (empty($actual)) {
                    continue 1;
                }
                $forecast = preg_replace('/[^0-9\.-]/', '', $dataChunk[self::FORECAST_KEY]);

                if ($currentDay >= $startDateTime->format(self::INTERNAL_DAY_FORMAT)) {
                    $dateTime = $startDateTime->format('Y') . '-' . $currentDay . ' ' . $currentTime;
                } else {
                    $dateTime = $endDateTime->format('Y') . '-' . $currentDay . ' ' . $currentTime;
                }

                $unit = preg_replace('/[0-9\.-]/', '', $dataChunk[self::ACTUAL_KEY]);
                $data[] = [
                    'instrument' => $instrument,
                    'datetime' => $dateTime,
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
            case 'AUD':
                switch (true) {
                    case strpos($name, 'cash') !== false && strpos($name, 'rate') !== false:
                        return $this->indicatorService->getBankRateIndicator();
                    case strpos($name, 'inflation') !== false && strpos($name, 'gauge') !== false:
                        return $this->indicatorService->getInflationIndicator();
                    case (
                            strpos($name, 'nab') !== false &&
                            strpos($name, 'business') !== false &&
                            strpos($name, 'confidence') !== false &&
                            strpos($name, 'quarterly') === false
                        ):
                        return $this->indicatorService->getCompaniesIndicator();
                    case strpos($name, 'trade') !== false && strpos($name, 'balance') !== false:
                        return $this->indicatorService->getTradeBalanceIndicator();
                    case strpos($name, 'unemployment') !== false && strpos($name, 'rate') !== false:
                        return $this->indicatorService->getUnemploymentIndicator();
                    case strpos($name, 'retail') !== false && strpos($name, 'sales') !== false:
                        return $this->indicatorService->getSalesIndicator();
                }
                break;
            case 'USD':
                switch (true) {
                    case strpos($name, 'rate') !== false && strpos($name, 'funds') !== false:
                        return $this->indicatorService->getBankRateIndicator();
                    case strpos($name, 'cpi') !== false && strpos($name, 'core') === false:
                        return $this->indicatorService->getInflationIndicator();
                    case strpos($name, 'pmi') !== false && strpos($name, 'final') !== false && strpos($name, 'services') !== false:
                        return $this->indicatorService->getCompaniesIndicator();
                    case strpos($name, 'trade') !== false && strpos($name, 'balance') !== false && strpos($name, 'goods') === false:
                        return $this->indicatorService->getTradeBalanceIndicator();
                    case strpos($name, 'unemployment') !== false && strpos($name, 'rate') !== false:
                        return $this->indicatorService->getUnemploymentIndicator();
                    case strpos($name, 'retail') !== false && strpos($name, 'core') === false:
                        return $this->indicatorService->getSalesIndicator();
                }
                break;
            case 'CAD':
                switch (true) {
                    case strpos($name, 'overnight') !== false && strpos($name, 'rate') !== false:
                        return $this->indicatorService->getBankRateIndicator();
                    case (
                            strpos($name, 'cpi') !== false &&
                            strpos($name, 'core') === false &&
                            strpos($name, 'common') === false &&
                            strpos($name, 'median') === false &&
                            strpos($name, 'trimmed') === false
                        ):
                        return $this->indicatorService->getInflationIndicator();
                    case strpos($name, 'ivey') !== false && strpos($name, 'pmi') !== false:
                        return $this->indicatorService->getCompaniesIndicator();
                    case strpos($name, 'trade') !== false && strpos($name, 'balance') !== false:
                        return $this->indicatorService->getTradeBalanceIndicator();
                    case strpos($name, 'unemployment') !== false && strpos($name, 'rate') !== false:
                        return $this->indicatorService->getUnemploymentIndicator();
                    case strpos($name, 'retail') !== false && strpos($name, 'core') === false:
                        return $this->indicatorService->getSalesIndicator();
                }
                break;
            case 'JPY':
                switch (true) {
                    case strpos($name, 'policy') !== false && strpos($name, 'rate') !== false:
                        return $this->indicatorService->getBankRateIndicator();
                    case strpos($name, 'national') !== false && strpos($name, 'cpi') !== false:
                        return $this->indicatorService->getInflationIndicator();
                    case strpos($name, 'final') !== false && strpos($name, 'pmi') !== false:
                        return $this->indicatorService->getCompaniesIndicator();
                    case strpos($name, 'trade') !== false && strpos($name, 'balance') !== false:
                        return $this->indicatorService->getTradeBalanceIndicator();
                    case strpos($name, 'unemployment') !== false && strpos($name, 'rate') !== false:
                        return $this->indicatorService->getUnemploymentIndicator();
                    case strpos($name, 'retail') !== false && strpos($name, 'sales') !== false:
                        return $this->indicatorService->getSalesIndicator();
                }
                break;
            case 'CHF':
                switch (true) {
                    case strpos($name, 'libor') !== false && strpos($name, 'rate') !== false:
                        return $this->indicatorService->getBankRateIndicator();
                    case strpos($name, 'cpi') !== false:
                        return $this->indicatorService->getInflationIndicator();
                    case strpos($name, 'pmi') !== false:
                        return $this->indicatorService->getCompaniesIndicator();
                    case strpos($name, 'trade') !== false && strpos($name, 'balance') !== false:
                        return $this->indicatorService->getTradeBalanceIndicator();
                    case strpos($name, 'unemployment') !== false && strpos($name, 'rate') !== false:
                        return $this->indicatorService->getUnemploymentIndicator();
                    case strpos($name, 'retail') !== false && strpos($name, 'sales') !== false:
                        return $this->indicatorService->getSalesIndicator();
                }
                break;
            case 'EUR':
                switch (true) {
                    case strpos($name, 'bid') !== false && strpos($name, 'rate') !== false:
                        return $this->indicatorService->getBankRateIndicator();
                    case (
                            strpos($name, 'final') !== false &&
                            strpos($name, 'cpi') !== false &&
                            strpos($name, 'core') === false &&
                            strpos($name, 'french') === false &&
                            strpos($name, 'german') === false
                        ):
                        return $this->indicatorService->getInflationIndicator();
                    case (
                            strpos($name, 'final') !== false &&
                            strpos($name, 'services') !== false &&
                            strpos($name, 'pmi') !== false &&
                            strpos($name, 'french') === false &&
                            strpos($name, 'german') === false
                        ):
                        return $this->indicatorService->getCompaniesIndicator();
                    case (
                            strpos($name, 'trade') !== false &&
                            strpos($name, 'balance') !== false &&
                            strpos($name, 'french') === false &&
                            strpos($name, 'german') === false &&
                            strpos($name, 'italian') === false
                        ):
                        return $this->indicatorService->getTradeBalanceIndicator();
                    case (
                            strpos($name, 'unemployment') !== false &&
                            strpos($name, 'rate') !== false &&
                            strpos($name, 'italian') === false &&
                            strpos($name, 'spanish') === false
                        ):
                        return $this->indicatorService->getUnemploymentIndicator();
                    case (
                            strpos($name, 'retail') !== false &&
                            strpos($name, 'sales') !== false &&
                            strpos($name, 'german') === false &&
                            strpos($name, 'italian') === false
                        ):
                        return $this->indicatorService->getSalesIndicator();
                }
                break;
            case 'GBP':
                switch (true) {
                    case strpos($name, 'official') !== false && strpos($name, 'rate') !== false && strpos($name, 'votes') === false:
                        return $this->indicatorService->getBankRateIndicator();
                    case strpos($name, 'cpi') !== false && strpos($name, 'core') === false:
                        return $this->indicatorService->getInflationIndicator();
                    case strpos($name, 'services') !== false && strpos($name, 'pmi') !== false:
                        return $this->indicatorService->getCompaniesIndicator();
                    case strpos($name, 'trade') !== false && strpos($name, 'balance') !== false:
                        return $this->indicatorService->getTradeBalanceIndicator();
                    case strpos($name, 'unemployment') !== false && strpos($name, 'rate') !== false:
                        return $this->indicatorService->getUnemploymentIndicator();
                    case strpos($name, 'retail') !== false && strpos($name, 'sales') !== false && strpos($name, 'monitor') === false:
                        return $this->indicatorService->getSalesIndicator();
                }
                break;
        }

        return null;
    }
}
