<?php declare(strict_types=1);
namespace FxBot\Model\Service;

use FxBot\Model\Service\PriceService;
use FxBot\Model\Strategy\StrategyFactory;
use FxBot\Model\Repository\TradeRepository;
use FxBot\Model\Repository\SimulationRepository;
use FxBot\Model\Entity\Order;
use FxBot\Model\Strategy\StrategyInterface;

class SimulationService
{
    private const INITIAL_TEST_BALANCE = 100;
    private const MAX_SPREAD = 0.0005;
    private const MAX_ITERATIONS_PER_STRATEGY = 4000000;
    private const SIMULATION_STEP = 'PT20M';

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
    }

    public function run() : array
    {
        $simulationIds = [];
        foreach ($this->getSimulationPeriods() as $simulationPeriod) {
            foreach ($this->getSimulationStrategies() as $settings) {
                $strategy = $this->strategyFactory->getStrategy($settings['className'], $settings['params']);
                echo PHP_EOL . '=========================================================================================================' . PHP_EOL;
                echo 'Simulation for ' . $strategy->getStrategyParams()['className'] . ' with params ';
                echo var_export($strategy->getStrategyParams()['params'], true) . PHP_EOL;
                echo '=========================================================================================================' . PHP_EOL;

                $balance = self::INITIAL_TEST_BALANCE;
                $currentDate = $simulationPeriod['start'];
                $counter = 1;
                $executedTrades = 0;
                $minBalance = self::INITIAL_TEST_BALANCE;
                $maxBalance = 0;
                $profits = 0;
                $losses = 0;
                $activeOrder = null;
                $singleTransactionRiskSize = null;

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
                        $activeOrder,
                        $singleTransactionRiskSize,
                        $strategy,
                        $prices,
                        $balance,
                        $currentDate,
                        $executedTrades,
                        $profits,
                        $losses
                    )) {
                        return [
                            'status' => false,
                            'message' => 'Could not handle order'
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
                    $strategy->getStrategyParams(),
                    $balance,
                    $minBalance,
                    $maxBalance,
                    $profits,
                    $losses,
                    $simulationPeriod['start'],
                    $currentDate,
                    $simulationIds
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

    public function setSimulationPeriods(array $simulationPeriods) : void
    {
        foreach ($simulationPeriods as $period) {
            if (empty($period['start']) || empty($period['end'])) {
                throw new \Exception('Wrong periods for test');
            }
        }
        $this->simulationPeriods = $simulationPeriods;
    }

    public function setSimulationStrategies(array $simulationStrategies) : void
    {
        foreach ($simulationStrategies as $strategy) {
            if (empty($strategy['className']) || empty($strategy['params']['instrument'])) {
                throw new \Exception('Wrong strategies for test');
            }
        }
        $this->simulationStrategies = $simulationStrategies;
    }

    private function getSimulationPeriods() : array
    {
        if (empty($this->simulationPeriods)) {
            throw new \Exception('Periods for test are not set');
        }

        return $this->simulationPeriods;
    }

    private function getSimulationStrategies() : array
    {
        if (empty($this->simulationStrategies)) {
            throw new \Exception('Strategies for test are not set');
        }

        return $this->simulationStrategies;
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
        $balance = (string) $balance;
        return substr($balance, 0, strpos($balance, '.') + 3) . (strpos($balance, '.') === false ? '.00' : '');
    }

    private function handleOrder(
        Order &$activeOrder = null,
        float &$singleTransactionRiskSize = null,
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
                        ' ' . str_pad((string) $activeOrder->getPrice(), 10)
                    ;
                    $executedTrades++;
                    $singleTransactionRiskSize = abs($activeOrder->getPrice() - $activeOrder->getStopLoss());
                }
            } catch(\Throwable $e) {
                trigger_error('Could not create order due to ' . $e->getMessage(), E_USER_NOTICE);

                return false;
            }

        } elseif (
            ($activeOrder->getUnits() > 0 && $activeOrder->getTakeProfit() < $prices[$activeOrder->getInstrument()]['bid']) ||
            ($activeOrder->getUnits() < 0 && $activeOrder->getTakeProfit() > $prices[$activeOrder->getInstrument()]['ask'])
        ) {
            $balance = $balance + (
                $balance *
                $strategy->getStrategyParams()['params']['singleTransactionRisk'] *
                abs($activeOrder->getTakeProfit() - $activeOrder->getPrice()) /
                $singleTransactionRiskSize
            );
            echo
                'PROFIT ' . str_pad($this->formatBalance($balance), 10) . ' on ' . $currentDate .
                ' due to ask ' . str_pad((string) $prices[$activeOrder->getInstrument()]['ask'], 10) .
                ' bid ' . str_pad((string) $prices[$activeOrder->getInstrument()]['bid'], 10) . PHP_EOL
            ;
            $profits++;
            $activeOrder = null;
            $singleTransactionRiskSize = null;
        } elseif (
            ($activeOrder->getUnits() > 0 && $activeOrder->getStopLoss() > $prices[$activeOrder->getInstrument()]['bid']) ||
            ($activeOrder->getUnits() < 0 && $activeOrder->getStopLoss() < $prices[$activeOrder->getInstrument()]['ask'])
        ) {
            $difference = (
                $balance *
                $strategy->getStrategyParams()['params']['singleTransactionRisk'] *
                abs($activeOrder->getStopLoss() - $activeOrder->getPrice()) /
                $singleTransactionRiskSize
            );

            if (
                $activeOrder->getUnits() > 0 && (
                    $activeOrder->getPrice() < $activeOrder->getStopLoss()
                ) ||
                $activeOrder->getUnits() < 0 && (
                    $activeOrder->getPrice() > $activeOrder->getStopLoss()
                )
            ) {
                $resultText = 'TSL    ';
                $balance += $difference;
            } elseif ($activeOrder->getPrice() == $activeOrder->getStopLoss()) {
                $resultText = 'BLOCK  ';
            }  else {
                $resultText = 'LOSS   ';
                $balance -= $difference;
            }

            echo
                $resultText . str_pad($this->formatBalance($balance), 10) . ' on ' . $currentDate .
                ' due to ask ' . str_pad((string) $prices[$activeOrder->getInstrument()]['ask'], 10) .
                ' bid ' . str_pad((string) $prices[$activeOrder->getInstrument()]['bid'], 10) . PHP_EOL
            ;
            $losses++;
            $activeOrder = null;
            $singleTransactionRiskSize = null;
        } else {
            $orderModification = $strategy->getOrderModification(
                'orderId',
                'tradeId',
                $activeOrder->getPrice(),
                $activeOrder->getStopLoss(),
                $activeOrder->getTakeProfit(),
                $prices[$activeOrder->getInstrument()]
            );
            if (!empty($orderModification)) {
                $activeOrder->applyOrderModification($orderModification);
            }
            $orderModification = null;
        }

        return true;
    }

    private function saveSimulationResult(
        array $strategyParams,
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
            $parameters = $strategyParams['params'];
            $parameters['strategy'] = substr($strategyParams['className'], strrpos($strategyParams['className'], '\\') + 1);
            $simulationIds[] = $this->simulationRepository->saveSimulation([
                'parameters' => $parameters,
                'finalBalance' => $balance,
                'minBalance' => $minBalance,
                'maxBalance' => $maxBalance,
                'profits' => $profits,
                'losses' => $losses,
                'simulationStart' => $simulationStart,
                'simulationEnd' => $simulationEnd,
                'datetime' => (new \DateTime('', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            trigger_error('Failed to save simulation result with message ' . $e->getMessage(), E_USER_NOTICE);

            return false;
        }

        return true;
    }
}
