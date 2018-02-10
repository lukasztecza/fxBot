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
    private const SIMULATION_START = '2017-10-01 00:00:00';
    private const SIMULATION_END = '2018-01-15 00:00:00';

    private const STRATEGIES = [
        'TinyApp\Model\Strategy\MinSpreadRigidOneMultiOneRandomStrategy',
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
            $activeOrder = null;
            //@TODO add how many trades executed and min max for strategy

            while ($counter < self::MAX_ITERATIONS_PER_STRATEGY && $currentDate < self::SIMULATION_END) {
                $counter++;
                $prices = $this->priceService->getInitialPrices($this->priceInstruments, $currentDate);
//@TODO we do not have bid ask fix it
                $prices = $this->getCurrentPrices($prices);
                if (empty($prices)) {
                    return [
                        'status' => false,
                        'message' => 'Could not get current prices for ' . var_export($strategyClass, true)
                    ];
                }
                $currentDate = (new \DateTime($currentDate, new \DateTimeZone('UTC')));
                $currentDate = $currentDate->add(new \DateInterval('PT15M'))->format('Y-m-d H:i:s');

                if ($activeOrder) {
                    $prices[$activeOrder->getInstrument()]['ask'];
                }
 
                if (is_null($activeOrder)) {
                    try {
                        $activeOrder = $strategy->getOrder($prices, $balance, $currentDate);
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
                    $activeOrder = null;
                } elseif (
                    ($activeOrder->getUnits() > 0 && $activeOrder->getStopLoss() > $prices[$activeOrder->getInstrument()]['bid']) ||
                    ($activeOrder->getUnits() < 0 && $activeOrder->getStopLoss() < $prices[$activeOrder->getInstrument()]['ask'])
                ) {
                    $balance = $balance - ($balance * self::SINGLE_TRANSACTION_RISK);
                    echo 'LOSS   ' . $this->formatBalance($balance) . PHP_EOL;
                    $activeOrder = null;
                }
            }
            $results[$strategyClass] = $balance;
        }

        echo '=====================================================' . PHP_EOL;
        echo 'Simulation results between ' . self::SIMULATION_START . ' and ' . $currentDate . PHP_EOL;
        echo '=====================================================' . PHP_EOL;
        foreach ($results as $strategyClass => $finalBalance) {
            echo $strategyClass . ' finished with ' . $this->formatBalance($finalBalance) . PHP_EOL;
        }
        echo '=====================================================' . PHP_EOL;

        return ['status' => true, 'message' => 'simulation finished'];
    }

    private function getCurrentPrices($inputPrices) : array
    {
        $prices = [];
        try {
            foreach ($inputPrices as $inputPrice) {
                $prices[$inputPrice['instrument']] = [
                    'ask' => $inputPrice['high'],
                    'bid' => $inputPrice['low']
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
