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

    private const MAX_ITERATIONS_PER_STRATEGY = 10000;
    private const SIMULATION_START = '2017-10-02 00:00:00';
    private const SIMULATION_END = '2018-01-15 00:00:00';

    private const DEFAULT_SPREAD = 0.0003;

    private const STRATEGIES = [
        'TinyApp\Model\Strategy\MinSpreadRigidOneMultiOneRandomStrategy',
        'TinyApp\Model\Strategy\MinSpreadRigidOneMultiThreeRandomStrategy',
        'TinyApp\Model\Strategy\MinSpreadRigidOneMultiFiveRandomStrategy',
        'TinyApp\Model\Strategy\MinSpreadRigidTwoMultiOneRandomStrategy',
        'TinyApp\Model\Strategy\MinSpreadRigidTwoMultiThreeRandomStrategy',
        'TinyApp\Model\Strategy\MinSpreadRigidTwoMultiFiveRandomStrategy',
        'TinyApp\Model\Strategy\MinSpreadRigidOneMultiOneTrendFindStrategy',
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
        foreach (self::STRATEGIES as $strategyClass) {
            echo '=====================================================' . PHP_EOL;
            echo 'Simulation for ' . $strategyClass . PHP_EOL;
            echo '=====================================================' . PHP_EOL;
            $strategy = $this->strategyFactory->getStrategy($strategyClass);
            $balance = self::INITIAL_TEST_BALANCE;
            $currentDate = self::SIMULATION_START;
            $counter = 0;
            $executedTrades = 0;
            $minBalance = 100000;
            $maxBalance = 0;
            $profits = 0;
            $losses = 0;
            $activeOrder = null;
            //@TODO add how many trades executed and min max for strategy

            while ($counter < self::MAX_ITERATIONS_PER_STRATEGY && $currentDate < self::SIMULATION_END) {
                $counter++;
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
                        $executedTrades++;
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
                    echo 'PROFIT ' . $this->formatBalance($balance) . PHP_EOL;
                    $profits++;
                    $activeOrder = null;
                } elseif (
                    ($activeOrder->getUnits() > 0 && $activeOrder->getStopLoss() > $prices[$activeOrder->getInstrument()]['bid']) ||
                    ($activeOrder->getUnits() < 0 && $activeOrder->getStopLoss() < $prices[$activeOrder->getInstrument()]['ask'])
                ) {
                    $balance = $balance - ($balance * self::SINGLE_TRANSACTION_RISK);
                    echo 'LOSS   ' . $this->formatBalance($balance) . PHP_EOL;
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

            $results[$strategyClass] = [
                'finalBalance' => $balance,
                'minBalance' => $minBalance,
                'maxBalance' => $maxBalance,
                'executedTrades' => $executedTrades,
                'profits' => $profits,
                'losses' => $losses
            ];
        }

        echo '=====================================================' . PHP_EOL;
        echo 'Simulation results between ' . self::SIMULATION_START . ' and ' . $currentDate . PHP_EOL;
        echo '=====================================================' . PHP_EOL;
        foreach ($results as $strategyClass => $stats) {
            echo $strategyClass . ' finished with ' . $this->formatBalance($stats['finalBalance']) . PHP_EOL;
            echo '    with minimum balance of ' . $this->formatBalance($stats['minBalance']) . PHP_EOL;
            echo '    with maximum balance of ' . $this->formatBalance($stats['maxBalance']) . PHP_EOL;
            echo '    with total trades of ' . $stats['executedTrades'] . PHP_EOL;
            echo '    with trades ended with profit ' . $stats['profits'] . PHP_EOL;
            echo '    with trades ended with loss ' . $stats['losses'] . PHP_EOL;
            echo '    which gives average profit per trade ' .
                round(($stats['finalBalance'] - self::INITIAL_TEST_BALANCE) / $stats['executedTrades'], 4) . PHP_EOL;
        }
        echo '=====================================================' . PHP_EOL;

        return ['status' => true, 'message' => 'simulation finished'];
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

    private function formatBalance(float $balance) : string
    {
        return substr($balance, 0, strpos($balance, '.') + 3) . (strpos($balance, '.') === false ? '.00' : '');
    }
}
