<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Strategy\StrategyFactory;
use TinyApp\Model\Strategy\Order;
use TinyApp\Model\Strategy\StrategyInterface;
use TinyApp\Model\Repository\TradeRepository;
use TinyApp\Model\Repository\SimulationRepository;

class SimulationService
{
    private const INITIAL_TEST_BALANCE = 100;
    private const SINGLE_TRANSACTION_RISK = 0.01;
    private const MAX_SPREAD = 0.0003;
    private const MAX_ITERATIONS_PER_STRATEGY = 4000000;
    private const SIMULATION_STEP = 'PT20M';

    private const SIMULATION_PERIODS = [
        ['start' => '2010-03-01 00:00:00', 'end' => '2011-03-01 00:00:00'],
        ['start' => '2011-03-01 00:00:00', 'end' => '2012-03-01 00:00:00'],
        ['start' => '2012-03-01 00:00:00', 'end' => '2013-03-01 00:00:00'],
        ['start' => '2013-03-01 00:00:00', 'end' => '2014-03-01 00:00:00'],
        ['start' => '2014-03-01 00:00:00', 'end' => '2015-03-01 00:00:00'],
        ['start' => '2015-03-01 00:00:00', 'end' => '2016-03-01 00:00:00'],
        ['start' => '2016-03-01 00:00:00', 'end' => '2017-03-01 00:00:00'],
        ['start' => '2017-03-01 00:00:00', 'end' => '2018-03-01 00:00:00'],
        ['start' => '2010-03-01 00:00:00', 'end' => '2018-03-01 00:00:00']
    ];

    private const FORCE_INSTRUMENT = 'EUR_USD';

    private const STRATEGIES_CLASS_FOR_SIMULATION = [
        'TinyApp\Model\Strategy\RigidAverageTrendingStrategy',
        'TinyApp\Model\Strategy\RigidFundamentalTrendingStrategyPattern',
        'TinyApp\Model\Strategy\RigidTrendingStrategy',
        'TinyApp\Model\Strategy\RigidLongAverageDeviationStrategy',
        'TinyApp\Model\Strategy\RigidAverageDistanceDeviationStrategy',
        'TinyApp\Model\Strategy\RigidTrendingDeviationStrategy',
        'TinyApp\Model\Strategy\RigidLongAverageTrendingStrategy',
        'TinyApp\Model\Strategy\RigidLongAverageTrendingDeviationStrategy',
        'TinyApp\Model\Strategy\RigidAverageTrendLongAverageDeviationStrategy',
        'TinyApp\Model\Strategy\RigidFundamentalStrategyPattern',
        'TinyApp\Model\Strategy\RigidRandomStrategyPattern'
    ];
    private const INSTRUMENT_INDEPENDENT_STRATEGIES = [
        'TinyApp\Model\Strategy\RigidFundamentalStrategyPattern',
        'TinyApp\Model\Strategy\RigidFundamentalTrendingStrategyPattern'
    ];

    private const CHANGING_PARAMETERS = [
        'rigidStopLoss' => [0.0010, 0.0020],
        'takeProfitMultiplier' => [1,2],
        'longFastAverage' => [50, 100],
        'longSlowAverage' => [200, 300],
        'extremumRange' => [12, 15],
        'signalFastAverage' => [25, 50],
        'signalSlowAverage' => [100, 125],
        'averageTrend' => [100, 500],
        'bankFactor' => [1],
        'inflationFactor' => [1],
        'tradeFactor' => [1],
        'companiesFactor' => [1],
        'salesFactor' => [1],
        'unemploymentFactor' => [1],
        'bankRelativeFactor' => [1,2],
        'followTrend' => [0,1],
        'lastPricesPeriod' => ['P60D']
    ];

    private $priceInstruments;
    private $priceService;
    private $strategyFactory;
    private $tradeRepository;
    private $simulationRepository;
    private $strategiesForTest;

    public function __construct(
        array $priceInstruments,
        PriceService $priceService,
        StrategyFactory $strategyFactory,
        TradeRepository $tradeRepository,
        SimulationRepository $simulationRepository
    ) {
        $this->priceInstruments = $priceInstruments;
        $this->priceService = $priceService;
        $this->strategyFactory = $strategyFactory;
        $this->tradeRepository = $tradeRepository;
        $this->simulationRepository = $simulationRepository;
        $this->strategiesForTest = $this->buildStrategiesForTest();
    }

    public function run() : array
    {
        $simulationIds = [];
        foreach (self::SIMULATION_PERIODS as $simulationPeriod) {
            foreach ($this->getStrategiesForTest() as $settings) {
                echo PHP_EOL . '=========================================================================================================' . PHP_EOL;
                echo 'Simulation for ' . $settings['className'] . (
                    !empty($settings['params']) ? ' with params ' . var_export($settings['params'], true) : ''
                ) . PHP_EOL;
                echo '=========================================================================================================' . PHP_EOL;
                $strategy = $this->strategyFactory->getStrategy($settings['className'], $settings['params']);

                $balance = self::INITIAL_TEST_BALANCE;
                $currentDate = $simulationPeriod['start'];
                $counter = 1;
                $executedTrades = 0;
                $minBalance = self::INITIAL_TEST_BALANCE;
                $maxBalance = 0;
                $profits = 0;
                $losses = 0;
                $activeOrder = null;
                while ($counter++ < self::MAX_ITERATIONS_PER_STRATEGY && $currentDate < $simulationPeriod['end']) {
                    if ($balance < self::INITIAL_TEST_BALANCE / 5) {
                        $balance = 0;
                        break 1;
                    } elseif ($balance > self::INITIAL_TEST_BALANCE * 1000) {
                        break 1;
                    }

                    $currentDate = (new \DateTime($currentDate, new \DateTimeZone('UTC')));
                    $currentDate = $currentDate->add(new \DateInterval(self::SIMULATION_STEP))->format('Y-m-d H:i:s');

                    $prices = $this->priceService->getInitialPrices($this->priceInstruments, $currentDate);
                    $prices = $this->getCurrentPrices($prices);
                    if (empty($prices)) {
                        return [
                            'status' => false,
                            'message' => 'Could not get current prices'
                        ];
                    }

                    if (!$this->handleOrder(
                        $activeOrder, $strategy, $prices, $balance, $currentDate, $executedTrades, $profits, $losses
                    )) {
                        return [
                            'status' => false,
                            'message' => 'Could not create order'
                        ];
                    }

                    if ($minBalance > $balance) {
                        $minBalance = $balance;
                    }
                    if ($maxBalance < $balance) {
                        $maxBalance = $balance;
                    }
                }
                echo PHP_EOL;

                if (!$this->saveSimulationResult(
                    $settings, $balance, $minBalance, $maxBalance, $profits, $losses, $simulationPeriod['start'], $currentDate, $simulationIds
                )) {
                    return [
                        'status' => false,
                        'message' => 'Could not save simulation result'
                    ];
                }
            }
        }

        return ['status' => true, 'message' => 'simulation finished', 'simulationIds' => $simulationIds];
    }

    public function getInitialTestBalance() : float
    {
        return self::INITIAL_TEST_BALANCE;
    }

    public function getSimulationsSummaryByIds(array $ids) : array
    {
        try {
            $result = $this->simulationRepository->getSimulationsSummaryByIds($ids);
            $summary = array_pop($result);
            if (empty($summary)) {
                return [];
            }

            return $summary;
        } catch (\Throwable $e) {
            trigger_error('Failed to get simulations summary with message ' . $e->getMessage(), E_USER_NOTICE);

            return [];
        }
    }

    public function setStrategiesForTest(array $strategiesForTest) : void
    {
        foreach ($strategiesForTest as $strategy) {
            if (empty($strategy['className']) || empty($strategy['params']['instrument'])) {
                throw new \Exception('Wrong strategies for test');
            }
        }
        $this->strategiesForTest = $strategiesForTest;
    }

    private function getStrategiesForTest() : array
    {
        return $this->strategiesForTest;
    }

    private function buildStrategiesForTest() : array
    {
        $strategies = [];
        $counter = 0;
        $changingParameters = self::CHANGING_PARAMETERS;
        end($changingParameters);
        $lastKey = key($changingParameters);

        foreach (self::STRATEGIES_CLASS_FOR_SIMULATION as $strategy) {
            if (in_array($strategy, self::INSTRUMENT_INDEPENDENT_STRATEGIES)) {
                $params = ['instrument' => 'VARIED'];
                reset($changingParameters);
                $this->nestIteration($counter, $strategies, $changingParameters, $lastKey, $params, $strategy);
            } elseif(!is_null(self::FORCE_INSTRUMENT)) {
                $params = ['instrument' => self::FORCE_INSTRUMENT];
                reset($changingParameters);
                $this->nestIteration($counter, $strategies, $changingParameters, $lastKey, $params, $strategy);
            } else {
                foreach ($this->priceInstruments as $instrument) {
                    $params = ['instrument' => $instrument];
                    reset($changingParameters);
                    $this->nestIteration($counter, $strategies, $changingParameters, $lastKey, $params, $strategy);
                }
            }
        }

        return $strategies;
    }

    private function nestIteration(int $counter, array &$strategies, array &$changingParameters, string $lastKey, array $params, string $strategy) : void
    {
        $counter++;
        if ($counter > 100) {
            throw new \Exception('Too deep nesting or danger of infinite recurrence, reached counter ' . var_export($counter, true));
        }

        $key = key($changingParameters);
        foreach ($changingParameters[$key] as $value) {
            $params[$key] = $value;

            if ($key === $lastKey) {
                $strategies[] = [
                    'className' => $strategy,
                    'params' => $params
                ];
            } else {
                next($changingParameters);
                $this->nestIteration($counter, $strategies, $changingParameters, $lastKey, $params, $strategy);
            }
        }
        prev($changingParameters);
    }

    private function getCurrentPrices($inputPrices) : array
    {
        $prices = [];
        try {
            foreach ($inputPrices as $inputPrice) {
                if (!isset($inputPrice['instrument']) || !isset($inputPrice['close'])) {
                    return [];
                }

                $closePrice = $inputPrice['close'];
                $spread = self::MAX_SPREAD;
                if (strpos($inputPrice['instrument'], 'JPY') !== false) {
                    $spread *= 100;
                }

                $prices[$inputPrice['instrument']] = [
                    'ask' => $inputPrice['close'] + ($spread / 2),
                    'bid' => $inputPrice['close'] - ($spread / 2)
                ];
            }
        } catch (\Throwable $e) {
            trigger_error('Failed to create prices with message ' . $e->getMessage(), E_USER_NOTICE);

            return [];
        }

        return $prices;
    }

    private function formatBalance(float $balance) : string
    {
        return substr($balance, 0, strpos($balance, '.') + 3) . (strpos($balance, '.') === false ? '.00' : '');
    }

    private function handleOrder(
        Order &$activeOrder = null,
        StrategyInterface $strategy,
        array $prices,
        float &$balance,
        string $currentDate,
        int &$executedTrades,
        int &$profits,
        int &$losses
    ) : bool {
        if (is_null($activeOrder)) {
            try {
                $activeOrder = $strategy->getOrder($prices, $balance, $currentDate);
                if (!empty($activeOrder)) {
                    echo $currentDate . ' balance ' . str_pad($this->formatBalance($balance), 10) .
                        ($activeOrder->getUnits() > 0 ? 'Buy ' : 'Sell') . ' at price on ' . $activeOrder->getInstrument() .
                        ' ' . str_pad($activeOrder->getPrice(), 10)
                    ;
                    $executedTrades++;
                }
            } catch(\Throwable $e) {
                trigger_error('Could not create order due to ' . $e->getMessage(), E_USER_NOTICE);

                return false;
            }
        } elseif (
            ($activeOrder->getUnits() > 0 && $activeOrder->getTakeProfit() < $prices[$activeOrder->getInstrument()]['bid']) ||
            ($activeOrder->getUnits() < 0 && $activeOrder->getTakeProfit() > $prices[$activeOrder->getInstrument()]['ask'])
        ) {
            $balance = $balance + ($balance * self::SINGLE_TRANSACTION_RISK * (
                abs($activeOrder->getPrice() - $activeOrder->getTakeProfit()) / abs($activeOrder->getPrice() - $activeOrder->getStopLoss())
            ));
            echo
                'PROFIT ' . str_pad($this->formatBalance($balance), 10) . ' on ' . $currentDate .
                ' due to ask ' . str_pad($prices[$activeOrder->getInstrument()]['ask'], 10) .
                ' bid ' . str_pad($prices[$activeOrder->getInstrument()]['bid'], 10) . PHP_EOL
            ;
            $profits++;
            $activeOrder = null;
        } elseif (
            ($activeOrder->getUnits() > 0 && $activeOrder->getStopLoss() > $prices[$activeOrder->getInstrument()]['bid']) ||
            ($activeOrder->getUnits() < 0 && $activeOrder->getStopLoss() < $prices[$activeOrder->getInstrument()]['ask'])
        ) {
            $balance = $balance - ($balance * self::SINGLE_TRANSACTION_RISK);
            echo
                'LOSS   ' . str_pad($this->formatBalance($balance), 10) . ' on ' . $currentDate .
                ' due to ask ' . str_pad($prices[$activeOrder->getInstrument()]['ask'], 10) .
                ' bid ' . str_pad($prices[$activeOrder->getInstrument()]['bid'], 10) . PHP_EOL
            ;
            $losses++;
            $activeOrder = null;
        }

        return true;
    }

    private function saveSimulationResult(
        array $settings,
        float $balance,
        float $minBalance,
        float $maxBalance,
        int $profits,
        int $losses,
        string $simulationStart,
        string $simulationEnd,
        array &$simulationIds
    ) : bool {
        try {
            $parameters = $settings['params'];
            $parameters['strategy'] = substr($settings['className'], strrpos($settings['className'], '\\') + 1);
            $parameters['singleTransactionRisk'] = self::SINGLE_TRANSACTION_RISK;
            $instrument = $settings['params']['instrument'];
            if (in_array($settings['className'], self::INSTRUMENT_INDEPENDENT_STRATEGIES)) {
                $instrument = 'VARIED';
            }
            $simulationIds[] = $this->simulationRepository->saveSimulation([
                'instrument' => $instrument,
                'parameters' => $parameters,
                'finalBalance' => $balance,
                'minBalance' => $minBalance,
                'maxBalance' => $maxBalance,
                'profits' => $profits,
                'losses' => $losses,
                'simulationStart' => $simulationStart,
                'simulationEnd' => $simulationEnd,
                'datetime' => (new \DateTime(null, new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            trigger_error('Failed to save simulation result with message ' . $e->getMessage(), E_USER_NOTICE);

            return false;
        }

        return true;
    }
}
