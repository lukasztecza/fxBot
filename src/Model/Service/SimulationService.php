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

    private const MAX_ITERATIONS_PER_STRATEGY = 40000;
    private const SIMULATION_START = '2017-06-11 00:00:00';
    private const SIMULATION_END = '2017-12-31 00:00:00';
//    private const SIMULATION_END = '2017-02-01 00:00:00';

    private const MAX_SPREAD = 0.0003;

    private $priceInstruments;
    private $priceService;
    private $strategyFactory;
    private $tradeRepository;
    private $simulationRepository;

    private const STRATEGY_CLASS = 'TinyApp\Model\Strategy\RigidFundamentalTrendingDeviationStrategyPattern';
    private const CHANGING_PARAMETERS = [
        'extremumRange' => [8, 16],
        'fastAveragePeriod' => [4],
        'slowAveragePeriod' => [8, 12],
        'rigidStopLoss' => [0.003, 0.004],
        'takeProfitMultiplier' => [3, 4, 5]
    ];

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
            echo '=========================================================================================================' . PHP_EOL;
            echo 'Simulation for ' . $settings['className'] . (
                !empty($settings['params']) ? ' with params ' . var_export($settings['params'], true) : ''
            ) . PHP_EOL;
            echo '=========================================================================================================' . PHP_EOL;

            if (!empty($settings['params'])) {
                $strategy = $this->strategyFactory->getStrategy($settings['className'], $settings['params']);
            }

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
                if ($balance < self::INITIAL_TEST_BALANCE / 10) {
                    $balance = 0;
                    break 1;
                } elseif ($balance > self::INITIAL_TEST_BALANCE * 10) {
                    break 1;
                }

                $currentDate = (new \DateTime($currentDate, new \DateTimeZone('UTC')));
                $currentDate = $currentDate->add(new \DateInterval('PT15M'))->format('Y-m-d H:i:s');

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
                            $executedTrades++;
                        }
                    } catch(\Throwable $e) {
                        echo 'Could not create order due to ' . $e->getMessage() . ' skipping' . PHP_EOL;
                        continue;
                    }
                } elseif (
                    ($activeOrder->getUnits() > 0 && $activeOrder->getTakeProfit() < $prices[$activeOrder->getInstrument()]['bid']) ||
                    ($activeOrder->getUnits() < 0 && $activeOrder->getTakeProfit() > $prices[$activeOrder->getInstrument()]['ask'])
                ) {
                    $balance = $balance + ($balance * self::SINGLE_TRANSACTION_RISK * (
                        abs($activeOrder->getPrice() - $activeOrder->getTakeProfit()) / abs($activeOrder->getPrice() - $activeOrder->getStopLoss())
                    ));
                    echo str_pad('PROFIT', 10) . str_pad($this->formatBalance($balance), 10) . $currentDate . PHP_EOL;
                    $profits++;
                    $activeOrder = null;
                } elseif (
                    ($activeOrder->getUnits() > 0 && $activeOrder->getStopLoss() > $prices[$activeOrder->getInstrument()]['bid']) ||
                    ($activeOrder->getUnits() < 0 && $activeOrder->getStopLoss() < $prices[$activeOrder->getInstrument()]['ask'])
                ) {
                    $balance = $balance - ($balance * self::SINGLE_TRANSACTION_RISK);
                    echo str_pad('LOSS', 10) . str_pad($this->formatBalance($balance), 10) . $currentDate . PHP_EOL;
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

            try {
                $parameters = $settings['params'];
                $parameters['strategy'] = substr($settings['className'], strrpos($settings['className'], '\\') + 1);
                $parameters['singleTransactionRisk'] = self::SINGLE_TRANSACTION_RISK;
                $this->simulationRepository->saveSimulation([
                    'instrument' => $settings['params']['instrument'],
                    'parameters' => $parameters,
                    'finalBalance' => $balance,
                    'minBalance' => $minBalance,
                    'maxBalance' => $maxBalance,
                    'profits' => $profits,
                    'losses' => $losses,
                    'simulationStart' => self::SIMULATION_START,
                    'simulationEnd' => self::SIMULATION_END,
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

        foreach ($this->priceInstruments as $instrument) {
            $params = ['instrument' => $instrument];
            reset($changingParameters);
            $this->nestIteration($counter, $strategies, $changingParameters, $lastKey, $params);
        }

        return $strategies;
    }

    private function nestIteration(int $counter, array &$strategies, array &$changingParameters, string $lastKey, array $params) : void
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
                    'className' => self::STRATEGY_CLASS,
                    'params' => $params
                ];
            } else {
                next($changingParameters);
                $this->nestIteration($counter, $strategies, $changingParameters, $lastKey, $params);
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
