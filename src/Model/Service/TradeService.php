<?php declare(strict_types=1);
namespace FxBot\Model\Service;

use HttpClient\ClientFactory;
use FxBot\Model\Strategy\StrategyFactory;
use FxBot\Model\Repository\TradeRepository;

class TradeService
{
    private const MAX_ALLOWED_OPEN_POSITIONS = 1;
    private const PER_PAGE = 2;

    private $priceInstruments;
    private $oandaClient;
    private $oandaAccount;
    private $selectedStrategy;
    private $strategyParams;
    private $strategyFactory;
    private $tradeRepository;

    public function __construct(
        array $priceInstruments,
        ClientFactory $clientFactory,
        string $oandaAccount,
        string $selectedStrategy,
        array $strategyParams,
        StrategyFactory $strategyFactory,
        TradeRepository $tradeRepository
    ) {
        $this->priceInstruments = $priceInstruments;
        $this->oandaClient = $clientFactory->getClient('oandaClient');
        $this->oandaAccount = $oandaAccount;
        $this->selectedStrategy = $selectedStrategy;
        $this->strategyParams = $strategyParams;
        $this->strategyFactory = $strategyFactory;
        $this->tradeRepository = $tradeRepository;
    }

    public function trade() : array
    {
        $accountDetails = $this->getAccountDetails();
        if (empty($accountDetails)) {
            return ['status' => false, 'message' => 'Could not get account details'];
        }
        if ($accountDetails['openPositionCount'] >= self::MAX_ALLOWED_OPEN_POSITIONS) {
            return ['status' => true, 'message' => 'Max allowed open positions reached'];
        }
        $balance = $accountDetails['balance'];

        $prices = $this->getCurrentPrices();
        if (empty($prices)) {
            return ['status' => false, 'message' => 'Could not get current prices'];
        }

        $strategy = $this->strategyFactory->getStrategy($this->selectedStrategy, $this->strategyParams);

        try {
            $order = $strategy->getOrder($prices, $balance);
        } catch(\Throwable $e) {
            trigger_error('Failed to build order with message ' . $e->getMessage(), E_USER_NOTICE);

            return ['status' => false, 'message' => 'Could not create order'];
        }

        if (empty($order)) {
            return ['status' => false, 'message' => 'Could not build order'];
        }

        try {
            $result = $this->oandaClient->executeTrade($this->oandaAccount, $order);
            //@TODO get price of the order from result do not assume the price got from initial call
            //this price should be set as actual price -> add setter for it
            //beside this price store also parameters in trade_parameters table
            //consider storing some external order id for it in trade table
        } catch(\Throwable $e) {
            trigger_error('Failed to execute trade with message ' . $e->getMessage() . ' with order ' . var_export($order, true), E_USER_NOTICE);

            return ['status' => false, 'message' => 'Got exception trying to execute trade  prices'];
        }

        try {
            $this->tradeRepository->saveTrade([
                'account' => $this->oandaAccount,
                'instrument' => $order->getInstrument(),
                'units' => $order->getUnits(),
                'price' => $order->getPrice(),
                'takeProfit' => $order->getTakeProfit(),
                'stopLoss' => $order->getStopLoss(),
                'balance' => $balance,
                'datetime' => (new \DateTime('', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s')
            ]);
        } catch(\Throwable $e) {
            trigger_error('Failed to save trade for order ' . var_export($order, true) . ' with message ' . $e->getMessage(), E_USER_NOTICE);

            return ['status' => false, 'message' => 'Got exception trying to save  execute trade  prices'];
        }

        return ['status' => true, 'message' => 'Trade executed and stored successfully'];
    }

    public function getTrades(int $page) : array
    {
        try {
            return $this->tradeRepository->getTrades($this->oandaAccount, $page, self::PER_PAGE);
        } catch (\Throwable $e) {
            trigger_error('Failed to get trades for page ' . var_export($page, true) . ' with message ' . $e->getMessage(), E_USER_NOTICE);
        }
    }

    private function getAccountDetails() : array
    {
        try {
            $response = $this->oandaClient->getAccountSummary($this->oandaAccount);

            switch (true) {
                case $response['info']['http_code'] !== 200:
                    trigger_error('Failed to get valid response ' . var_export($response, true), E_USER_NOTICE);
                    return [];
                case !isset($response['body']['account']['openPositionCount']) || empty($response['body']['account']['balance']):
                    trigger_error('Failed to get expected account details in response ' . var_export($response, true), E_USER_NOTICE);
                    return [];
            }
        } catch (\Throwable $e) {
            trigger_error('Failed to get account summary with message ' . $e->getMessage(), E_USER_NOTICE);

            return [];
        }

        return $response['body']['account'];
    }

    private function getCurrentPrices() : array
    {
        $prices = [];
        try {
            foreach ($this->priceInstruments as $instrument) {
                $response = $this->oandaClient->getCurrentPrice($instrument);
                if (empty($response['body']['candles'][0]['bid']['c']) || empty($response['body']['candles'][0]['ask']['c'])) {
                    trigger_error('Failed to get current price for ' . $instrument, E_USER_NOTICE);

                    return [];
                }

                $prices[$instrument] = [
                    'ask' => $response['body']['candles'][0]['ask']['c'],
                    'bid' => $response['body']['candles'][0]['bid']['c']
                ];
            }
        } catch (\Throwable $e) {
            trigger_error('Failed to get current prices with message ' . $e->getMessage(), E_USER_NOTICE);

            return [];
        }

        return $prices;
    }
}
