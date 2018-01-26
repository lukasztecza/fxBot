<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Strategy\StrategyFactory;
use TinyApp\Model\Repository\TradeRepository;

use TinyApp\Model\Strategy\RandomStrategy;

class SimulationService
{
    private const INITIAL_TEST_BALANCE = 100;
    private const MAX_ALLOWED_OPEN_POSITIONS = 1;

    private const STRATEGIES = [
        'TinyApp\Model\Strategy\RandomStrategy'
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
        $balance = self::INITIAL_TEST_BALANCE;
        $initialPrices = $this->priceService->getInitialPrices($this->priceInstruments);
        $prices = $this->getCurrentPrices($initialPrices);
        if (empty($prices)) {
            return ['status' => false, 'message' => 'Could not get current prices'];
        }

        foreach (self::STRATEGIES as $strategyClass) {
            $strategy = $this->strategyFactory->getStrategy($strategyClass);
            try {
                $order = $strategy->getOrder($prices, $balance);
            } catch(\Throwable $e) {
                trigger_error('Failed to build order with message ' . $e->getMessage(), E_USER_NOTICE);

                return ['status' => false, 'message' => 'Could not create order'];
            }


            var_dump($order);exit;
        }
        //@TODO pick all strategies
        try {
            $order = $strategy->getOrder($prices, $balance);
        } catch(\Throwable $e) {
            trigger_error('Failed to build order with message ' . $e->getMessage(), E_USER_NOTICE);

            return ['status' => false, 'message' => 'Could not create order'];
        }

        try {
            $result = $this->oandaClient->executeTrade($this->oandaAccount, $order);
        } catch(\Throwable $e) {
            trigger_error('Failed to execute trade with message ' . $e->getMessage() . ' with order ' . var_export($order, true), E_USER_NOTICE);

            return ['status' => false, 'message' => 'Got exception trying to execute trade  prices'];
        }

        try {
        //@TODO add current balance when saving trade
            $this->tradeRepository->saveTrade([
                'instrument' => $order->getInstrument(),
                'units' => $order->getUnits(),
                'price' => $order->getUnits() > 0 ? $prices[$order->getInstrument()]['ask'] : $prices[$order->getInstrument()]['bid'],
                'takeProfit' => $order->getTakeProfit(),
                'stopLoss' => $order->getStopLoss(),
                'datetime' => (new \DateTime(null, new \DateTimeZone('UTC')))->format('Y-m-d H:i:s')
            ]);
        } catch(\Throwable $e) {
            trigger_error('Failed to save trade for order ' . var_export($order, true) . ' with message ' . $e->getMessage(), E_USER_NOTICE);

            return ['status' => false, 'message' => 'Got exception trying to save  execute trade  prices'];
        }

        return ['status' => true, 'message' => 'Trade executed and stored successfully'];
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
}
