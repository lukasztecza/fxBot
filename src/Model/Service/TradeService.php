<?php declare(strict_types=1);
namespace FxBot\Model\Service;

use HttpClient\ClientFactory;
use FxBot\Model\Strategy\StrategyFactory;
use FxBot\Model\Repository\TradeRepository;

class TradeService
{
    private const MAX_ALLOWED_OPEN_POSITIONS = 1;
    private const PER_PAGE = 10;

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
            $updatedText = '';
            if ($this->handleExistingTrade((float) $accountDetails['balance'])) {
                $updatedText = ', updated existing trade blocking loss';
            } else {
                $updatedText = ', did not update existing trade';
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
            $order = $strategy->getOrder($prices, (float) $balance);
        } catch (\Throwable $e) {
            trigger_error('Failed to build order with message ' . $e->getMessage(), E_USER_NOTICE);

            return ['status' => false, 'message' => 'Could not create order'];
        }
        if (empty($order)) {
            return ['status' => false, 'message' => 'Could not build order'];
        }

        try {
            $response = $this->oandaClient->executeTrade($this->oandaAccount, $order);
        } catch (\Throwable $e) {
            trigger_error('Failed to execute trade with message ' . $e->getMessage() . ' with order ' . var_export($order, true), E_USER_NOTICE);

            return ['status' => false, 'message' => 'Got exception trying to execute trade  prices'];
        }

        if (
            empty($response['body']['orderFillTransaction']['tradeOpened']['tradeID']) ||
            empty($response['body']['orderFillTransaction']['price'])
        ) {
            trigger_error('Got unexpected response structure ' . var_export($response, true), E_USER_NOTICE);

            return ['status' => false, 'message' => 'Failed to execute trade got unexpexted response'];
        }

        try {
            $parameters = $strategy->getStrategyParams()['params'];
            $parameters['executedPrice'] = $response['body']['orderFillTransaction']['price'];
            $this->tradeRepository->saveTrade([
                'parameters' => $parameters,
                'account' => $this->oandaAccount,
                'externalId' => $response['body']['orderFillTransaction']['tradeOpened']['tradeID'],
                'instrument' => $order->getInstrument(),
                'units' => $order->getUnits(),
                'price' => $order->getPrice(),
                'takeProfit' => $order->getTakeProfit(),
                'stopLoss' => $order->getStopLoss(),
                'balance' => $balance,
                'datetime' => (new \DateTime('', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
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
        $externalTrade = $this->getTradeDetails();
        if (empty($externalTrade['id'])) {
            return false;
        }
        try {
            $trade = $this->tradeRepository->getTradeWithParametersByExternalId($externalTrade['id']);
        } catch (\Throwable $e) {
            trigger_error('Failed to get stored trade with message ' . $e->getMessage());

            return false;
        }
        if (empty($trade['id']) || !empty($trade['parameters']['modifiedPrice'])) {
            trigger_error('Trade can not be modified');

            return false;
        }

        $currentPrices = $this->getCurrentPrice($externalTrade['instrument']);
        if (empty($currentPrices)) {
            return false;
        }

        $strategy = $this->strategyFactory->getStrategy($this->selectedStrategy, $this->strategyParams);
        $orderModification = $strategy->getOrderModification(
            $externalTrade['id'],
            $externalTrade['stopLossOrder']['id'],
            (float) $externalTrade['price'],
            (float) $externalTrade['stopLossOrder']['price'],
            (float) $externalTrade['takeProfitOrder']['price'],
            $currentPrices
        );
        if (empty($orderModification)) {
            return false;
        }

        try {
            $response = $this->oandaClient->modifyTrade($this->oandaAccount, $orderModification);
        } catch (\Throwable $e) {
            trigger_error('Failed to modify trade with message ' . $e->getMessage(), E_USER_NOTICE);

            return false;
        }

        if (empty($response['body']['orderCreateTransaction']['price'])) {
            trigger_error('Got unexpected response structure ' . var_export($response, true), E_USER_NOTICE);

            return false;
        }

        try {
            $this->tradeRepository->updateTrade(
                $trade['id'], ['modifiedPrice' => $response['body']['orderCreateTransaction']['price']]
            );
        } catch (\Throwable $e) {
            trigger_error('Failed to store trade modification with message ' . $e->getMessage(), E_USER_NOTICE);

            return true;
        }

        return true;
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
        foreach ($this->priceInstruments as $instrument) {
            $result = $this->getCurrentPrice($instrument);
            if (empty($result)) {
                return [];
            }
            $prices[$instrument] = $result;
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
