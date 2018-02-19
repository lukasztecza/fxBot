<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Strategy\StrategyFactory;
use TinyApp\Model\Repository\TradeRepository;

use TinyApp\Model\Strategy\RandomStrategy;

class SimulationService
{
    private const INITIAL_TEST_BALANCE = 100;
    private const SINGLE_TRANSACTION_RISK = 0.01;

    private const MAX_ITERATIONS_PER_STRATEGY = 40000;
    private const SIMULATION_START = '2017-01-01 00:00:00';
    private const SIMULATION_END = '2017-12-31 00:00:00';
//    private const SIMULATION_END = '2017-01-10 00:00:00';

    private const DEFAULT_SPREAD = 0.0005;

    /*
     * Table results for strategies having more than 2 parameters will
     * show values for the first value of parameters not included below
     */
    private const TABLE_RESULT_PARAMS = [
        'vertical' => 2,
        'horizontal' => 0
    ];

    private $priceInstruments;
    private $priceService;
    private $strategyFactory;
    private $tradeRepository;

    public function __construct(
        array $priceInstruments,
        PriceService $priceService,
        StrategyFactory $strategyFactory,
        TradeRepository $tradeRepository
    ) {
        $this->priceInstruments = $priceInstruments;
        $this->priceService = $priceService;
        $this->strategyFactory = $strategyFactory;
        $this->tradeRepository = $tradeRepository;
    }

    public function run() : array
    {
        $results = [];
        foreach ($this->getStrategiesForTest() as $settings) {
            $strategyClass = $settings['className'];
            echo '=========================================================================================================' . PHP_EOL;
            echo 'Simulation for ' . $strategyClass . (
                !empty($settings['params']) ? ' with params ' . implode(', ', $settings['params']) : ''
            ) . PHP_EOL;
            echo '=========================================================================================================' . PHP_EOL;
            if (!empty($settings['params'])) {
                $strategy = $this->strategyFactory->getStrategy($strategyClass, $settings['params']);
            } else {
                $strategy = $this->strategyFactory->getStrategy($strategyClass);
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
                if ($balance < self::INITIAL_TEST_BALANCE / 2) {
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
                        'message' => 'Could not get current prices for ' . var_export($strategyClass, true)
                    ];
                }

                if (is_null($activeOrder)) {
                    try {
                        $activeOrder = $strategy->getOrder($prices, $balance, $currentDate);
                        if (!empty($activeOrder)) {
                            $executedTrades++;
                        }
                    } catch(\Throwable $e) {
                        echo 'Could not create order skipping' . PHP_EOL;
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

            $results[] = [
                'strategy' => $strategyClass,
                'params' => $settings['params'],
                'finalBalance' => $balance,
                'minBalance' => $minBalance,
                'maxBalance' => $maxBalance,
                'executedTrades' => $executedTrades,
                'profits' => $profits,
                'losses' => $losses
            ];
        }

        $this->displayTextResults($results, $currentDate);
        $this->displayTableResults($results, $currentDate);

        return ['status' => true, 'message' => 'simulation finished'];
    }

    private function getStrategiesForTest() : array
    {
        $strategies = [];
        for ($i = 0.002; $i <= 0.005; $i += 0.001) {
            for ($j = 3; $j <= 3; $j++) {
                /*
                $strategies[] = [
                    'className' => 'TinyApp\Model\Strategy\MinSpreadRigidTrendingStrategyPattern',
                    //'className' => 'TinyApp\Model\Strategy\MinSpreadRigidStrategyPattern',
                    'params' => [$i, $j]
                ];
                */
                foreach (/*['AUD_USD', 'USD_CAD']*/$this->priceInstruments as $instrument) {
                    $strategies[] = [
                        'className' => 'TinyApp\Model\Strategy\RigidTrendingStrategyPattern',
                        'params' => [$i, $j, $instrument]
                    ];
                }
            }
        }

        return $strategies;
    }

    private function getCurrentPrices($inputPrices) : array
    {
        $prices = [];
        try {
            foreach ($inputPrices as $inputPrice) {
                $closePrice = $inputPrice['close'];
                $spread = self::DEFAULT_SPREAD;
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

    private function displayTableResults(array $results, string $currentDate) : void
    {
        if (
            empty($results[0]['params'][self::TABLE_RESULT_PARAMS['vertical']]) ||
            empty($results[0]['params'][self::TABLE_RESULT_PARAMS['horizontal']])
        ) {
            echo 'Could not create table view for specified params';
            return;
        }
        $params1 = [];
        $params2 = [];
        foreach ($results as $result) {
            $params1[(string)$result['params'][self::TABLE_RESULT_PARAMS['vertical']]] = true;
            $params2[(string)$result['params'][self::TABLE_RESULT_PARAMS['horizontal']]] = true;
        }

        echo '=========================================================================================================' . PHP_EOL;
        echo 'Table of final balance results between ' . self::SIMULATION_START . ' and ' . $currentDate . ' for ' . $results[0]['strategy'] . PHP_EOL;
        echo '=========================================================================================================' . PHP_EOL;
        $this->echoTableRows($params1, $params2, $results, 'finalBalance');

        foreach ($results as $key => $result) {
            $results[$key]['ratioPerTrade'] = $this->getRatioPerTrade($result);
        }
        echo '=========================================================================================================' . PHP_EOL;
        echo 'Table of ratio per trade results between ' . self::SIMULATION_START . ' and ' . $currentDate . ' for ' . $results[0]['strategy'] . PHP_EOL;
        echo '=========================================================================================================' . PHP_EOL;
        $this->echoTableRows($params1, $params2, $results, 'ratioPerTrade');
    }

    private function echoTableRows(array $params1, array $params2, array $results, string $field) : void
    {
        echo str_pad('|', 10);
        foreach ($params2 as $param2 => $val2) {
            echo str_pad('|' . $param2, 10);
        }
        echo PHP_EOL;
        foreach ($params1 as $param1 => $val1) {
            echo str_pad('|' . $param1, 10);
            foreach ($params2 as $param2 => $val2) {
                foreach ($results as $result) {
                    if (
                        (string)$result['params'][self::TABLE_RESULT_PARAMS['vertical']] == $param1 &&
                        (string)$result['params'][self::TABLE_RESULT_PARAMS['horizontal']] == $param2
                    ) {
                        echo str_pad('|' . $this->formatBalance($result[$field]), 10);
                        break 1;
                    }
                }
            }
            echo PHP_EOL;
        }
    }

    private function displayTextResults(array $results, string $currentDate) : void
    {
        echo '=========================================================================================================' . PHP_EOL;
        echo 'Simulation results between ' . self::SIMULATION_START . ' and ' . $currentDate . PHP_EOL;
        echo '=========================================================================================================' . PHP_EOL;
        $maxFinalBalance = ['value' => 0.00, 'strategy' => null];
        $maxTotalBalance = ['value' => 0.00, 'strategy' => null];
        $minTotalBalance = ['value' => self::INITIAL_TEST_BALANCE, 'strategy' => null];
        $maxRatioPerTrade = ['value' => -self::INITIAL_TEST_BALANCE, 'strategy' => null];
        foreach ($results as $result) {
            $paramsText = !empty($result['params']) ? ' with ' . implode(' and ', $result['params']) : '';
            echo $result['strategy'] . $paramsText . ' finished with balance of ' . $this->formatBalance($result['finalBalance']) . PHP_EOL;
            echo '    with minimum balance of ' . $this->formatBalance($result['minBalance']) . PHP_EOL;
            echo '    with maximum balance of ' . $this->formatBalance($result['maxBalance']) . PHP_EOL;
            echo '    with total trades of ' . $result['executedTrades'] . PHP_EOL;
            echo '    with trades ended with profit ' . $result['profits'] . PHP_EOL;
            echo '    with trades ended with loss ' . $result['losses'] . PHP_EOL;
            $ratioPerTrade = $this->getRatioPerTrade($result);
            echo '    which gives average profit per trade ' . $ratioPerTrade . PHP_EOL;
            if ($maxFinalBalance['value'] < $result['finalBalance']) {
                $maxFinalBalance['value'] = $result['finalBalance'];
                $maxFinalBalance['strategy'] = $result['strategy'] . $paramsText;
            }
            if ($maxTotalBalance['value'] < $result['maxBalance']) {
                $maxTotalBalance['value'] = $result['maxBalance'];
                $maxTotalBalance['strategy'] = $result['strategy'] . $paramsText;
            }
            if ($minTotalBalance['value'] > $result['minBalance']) {
                $minTotalBalance['value'] = $result['minBalance'];
                $minTotalBalance['strategy'] = $result['strategy'] . $paramsText;
            }
            if ($maxRatioPerTrade['value'] < $ratioPerTrade) {
                $maxRatioPerTrade['value'] = $ratioPerTrade;
                $maxRatioPerTrade['strategy'] = $result['strategy'] . $paramsText;
            }
        }
        echo '=========================================================================================================' . PHP_EOL;
        echo 'Best final balance is ' . $this->formatBalance($maxFinalBalance['value']) . ' for ' . $maxFinalBalance['strategy'] . PHP_EOL;
        echo 'Best max total balance is ' . $this->formatBalance($maxTotalBalance['value']) . ' for ' . $maxTotalBalance['strategy'] . PHP_EOL;
        echo 'Worst min total balance is ' . $this->formatBalance($minTotalBalance['value']) . ' for ' . $minTotalBalance['strategy'] . PHP_EOL;
        echo 'Best ratio per trade is  ' . $this->formatBalance($maxRatioPerTrade['value']) . ' for ' . $maxRatioPerTrade['strategy'] . PHP_EOL;
    }

    private function getRatioPerTrade(array $result) : string
    {
        return round(($result['finalBalance'] - self::INITIAL_TEST_BALANCE) / $result['executedTrades'], 4);
    }

    private function formatBalance(float $balance) : string
    {
        return substr($balance, 0, strpos($balance, '.') + 3) . (strpos($balance, '.') === false ? '.00' : '');
    }
}
