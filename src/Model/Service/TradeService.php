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
$this->handleExistingTrade((float) $accountDetails['balance']);//TODO remove this
        if ($accountDetails['openPositionCount'] >= self::MAX_ALLOWED_OPEN_POSITIONS) {
            $updatedText = '';
            if ($this->handleExistingTrade($accountDetails['balance'])) {
                $updatedText = ', updated existing trade blocking loss';
            }

            return ['status' => true, 'message' => 'Max allowed open positions reached' . $updatedText];
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
        } catch(\Throwable $e) {
            trigger_error('Failed to execute trade with message ' . $e->getMessage() . ' with order ' . var_export($order, true), E_USER_NOTICE);

            return ['status' => false, 'message' => 'Got exception trying to execute trade  prices'];
        }

        try {
            $parameters = $strategy->getStrategyParams();
            if (!empty($result['orderFillTransaction']['orderID'])) {
                $parameters['orderId'] = $result['orderFillTransaction']['orderID'];
            }
            if (!empty($result['orderFillTransaction']['tradeOpened']['tradeID'])) {
                $parameters['tradeId'] = $result['orderFillTransaction']['tradeOpened']['tradeID'];
            }
            if (!empty($result['orderFillTransaction']['price'])) {
                $parameters['executedPrice'] = $result['orderFillTransaction']['price'];
            }
            $this->tradeRepository->saveTrade([
                'parameters' => $parameters,
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

    private function handleExistingTrade(float $balance) : bool
    {
        $trade = $this->getTradeDetails();
        if (empty($trade)) {
            return false;
        }

        $currentPrices = $this->getCurrentPrice($trade['instrument']);
        if (empty($currentPrices)) {
            return ['status' => false, 'message' => 'Could not get current prices'];
        }
        $strategy = $this->strategyFactory->getStrategy($this->selectedStrategy, $this->strategyParams);
        $orderModification = $strategy->getOrderModification(
            $trade['id'],
            $trade['stopLossOrder']['id'],
            (float) $trade['price'],
            (float) $trade['stopLossOrder']['price'],
            (float) $trade['takeProfitOrder']['price'],
            $currentPrices
        );
var_dump($orderModification);exit;
//call exists but worng trade and params are passed TODO update it
        $result = $this->oandaClient->modifyTrade($this->oandaAccount, $orderModification);

        var_dump($result);exit;
    }

    private function getTradeDetails() : array
    {
        try {
            $response = $this->oandaClient->getOpenTrades($this->oandaAccount);

            switch (true) {
                case $response['info']['http_code'] !== 200:
                    trigger_error('Failed to get valid response ' . var_export($response, true), E_USER_NOTICE);
                    return [];
                case (
                    !isset($response['body']['trades'][0]['id']) ||
                    !isset($response['body']['trades'][0]['stopLossOrder']['id']) ||
                    !isset($response['body']['trades'][0]['stopLossOrder']['price'])
                ):
                    trigger_error('Failed to get expected trade details in response ' . var_export($response, true), E_USER_NOTICE);
                    return [];
            }
        } catch (\Throwable $e) {
            trigger_error('Failed to get trade details with message ' . $e->getMessage(), E_USER_NOTICE);

            return [];
        }

        return $response['body']['trades'][0];
    }

    private function getCurrentPrices() : array
    {
        $prices = [];
        try {
            foreach ($this->priceInstruments as $instrument) {
                $prices[$instrument] = $this->getCurrentPrice($instrument);
            }
        } catch (\Throwable $e) {
            trigger_error('Failed to get current prices with message ' . $e->getMessage(), E_USER_NOTICE);

            return [];
        }

        return $prices;
    }

    private function getCurrentPrice(string $instrument) : array
    {
        try {
            $response = $this->oandaClient->getCurrentPrice($instrument);
            if (empty($response['body']['candles'][0]['bid']['c']) || empty($response['body']['candles'][0]['ask']['c'])) {
                trigger_error('Failed to get current price for ' . $instrument, E_USER_NOTICE);

                return [];
            }

            return [
                'ask' => $response['body']['candles'][0]['ask']['c'],
                'bid' => $response['body']['candles'][0]['bid']['c']
            ];
        } catch (\Throwable $e) {
            trigger_error('Failed to get current price for ' . $instrument . ' with message ' . $e->getMessage(), E_USER_NOTICE);

            return [];
        }
    }
}
