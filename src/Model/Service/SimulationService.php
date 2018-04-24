<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Strategy\StrategyFactory;
use TinyApp\Model\Repository\TradeRepository;
use TinyApp\Model\Repository\SimulationRepository;

class SimulationService
{
    private const INITIAL_TEST_BALANCE = 100;
    private const SINGLE_TRANSACTION_RISK = 0.03;
    private const MAX_SPREAD = 0.0003;

    private const MAX_ITERATIONS_PER_STRATEGY = 4000000;
    private const SIMULATION_START = '2010-05-19 00:00:00';
    private const SIMULATION_END = '2018-03-01 00:00:00';
    private const SIMULATION_STEP = 'PT20M';

    private const STRATEGIES_CLASS_FOR_SIMULATION = [
        'TinyApp\Model\Strategy\RigidFundamentalStrategyPattern',
        'TinyApp\Model\Strategy\RigidLongAverageTrendingDeviationStrategy',
        'TinyApp\Model\Strategy\RigidRandomStrategyPattern'
    ];
    private const INSTRUMENT_INDEPENDENT_STRATEGIES = [
        'TinyApp\Model\Strategy\RigidFundamentalStrategyPattern'
    ];

    private const CHANGING_PARAMETERS = [
        'rigidStopLoss' => [0.0025],
        'takeProfitMultiplier' => [5],
        'extremumRange' => [12],
        'signalFast' => [20],
        'signalSlow' => [40],
        'fastAverage' => [200],
        'slowAverage' => [400],
        'bankFactor' => [1],
        'inflationFactor' => [1],
        'tradeFactor' => [1],
        'companiesFactor' => [1],
        'salesFactor' => [1],
        'unemploymentFactor' => [1],
        'bankRelativeFactor' => [0.1]
    ];

    private $priceInstruments;
    private $priceService;
    private $strategyFactory;
    private $tradeRepository;
    private $simulationRepository;

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
    }

    public function run() : array
    {
        foreach ($this->getStrategiesForTest() as $settings) {
            echo PHP_EOL . '=========================================================================================================' . PHP_EOL;
            echo 'Simulation for ' . $settings['className'] . (
                !empty($settings['params']) ? ' with params ' . var_export($settings['params'], true) : ''
            ) . PHP_EOL;
            echo '=========================================================================================================' . PHP_EOL;
            $strategy = $this->strategyFactory->getStrategy($settings['className'], $settings['params']);

            $balance = self::INITIAL_TEST_BALANCE;
            $currentDate = self::SIMULATION_START;
            $counter = 0;
            $executedTrades = 0;
            $minBalance = self::INITIAL_TEST_BALANCE;
            $maxBalance = 0;
            $profits = 0;
            $losses = 0;
            $activeOrder = null;
            while ($counter < self::MAX_ITERATIONS_PER_STRATEGY && $currentDate < self::SIMULATION_END) {
                $counter++;
                if ($balance < self::INITIAL_TEST_BALANCE / 2) {
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
                        'message' => 'Could not get current prices for ' . var_export($settings['className'], true)
                    ];
                }

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
                        return [
                            'status' => false,
                            'message' => 'Could not create order due to ' . $e->getMessage()
                        ];
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

                if ($minBalance > $balance) {
                    $minBalance = $balance;
                }
                if ($maxBalance < $balance) {
                    $maxBalance = $balance;
                }
            }
            echo PHP_EOL;

            try {
                $parameters = $settings['params'];
                $parameters['strategy'] = substr($settings['className'], strrpos($settings['className'], '\\') + 1);
                $parameters['singleTransactionRisk'] = self::SINGLE_TRANSACTION_RISK;
                $this->simulationRepository->saveSimulation([
                    'instrument' => in_array($settings['className'], self::INSTRUMENT_INDEPENDENT_STRATEGIES) ? 'VARIED' : $settings['params']['instrument'],
                    'parameters' => $parameters,
                    'finalBalance' => $balance,
                    'minBalance' => $minBalance,
                    'maxBalance' => $maxBalance,
                    'profits' => $profits,
                    'losses' => $losses,
                    'simulationStart' => self::SIMULATION_START,
                    'simulationEnd' => $currentDate,
                    'datetime' => (new \DateTime(null, new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
                trigger_error('Failed to save simulation result with message ' . $e->getMessage(), E_USER_NOTICE);

                return [
                    'status' => false,
                    'message' => 'Could not save simulation result'
                ];
            }
        }

        return ['status' => true, 'message' => 'simulation finished'];
    }

    private function getStrategiesForTest() : array
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
}
