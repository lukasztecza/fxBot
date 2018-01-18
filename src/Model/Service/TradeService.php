<?php
namespace TinyApp\Model\Service;

use HttpClient\ClientFactory;
use TinyApp\Model\Strategy\StrategyFactory;

use TinyApp\Model\Strategy\RandomStrategy as SelectedStrategy;

class TradeService
{
    private const MAX_ALLOWED_OPEN_POSITIONS = 1;

    private $priceInstruments;
    private $oandaClient;
    private $oandaAccount;
    private $strategyFactory;

    public function __construct(
        array $priceInstruments,
        ClientFactory $clientFactory,
        string $oandaAccount,
        StrategyFactory $strategyFactory
    ) {
        $this->priceInstruments = $priceInstruments;
        $this->oandaClient = $clientFactory->getClient('oandaClient');
        $this->oandaAccount = $oandaAccount;
        $this->strategyFactory = $strategyFactory;
    }

    public function trade() : array
    {
        $accountDetails = $this->getAccountDetails(); //get balance
        if (empty($accountDetails)) {
            return ['status' => false, 'message' => 'Could not get account details'];
        }
        $balance = $accountDetails['balance'] ?? null;

        $prices = $this->getCurrentPrices();
        if (empty($prices)) {
            return ['status' => false, 'message' => 'Could not get current prices'];
        }

        $strategy = $this->strategyFactory->getStrategy(SelectedStrategy::class);
        try {
            $order = $strategy->getOrder($prices, $balance);
        } catch(\Throwable $e) {
            trigger_error('Failed to build order with message ' . $e->getMessage(), E_USER_NOTICE);

            return ['status' => false, 'message' => 'Could not create order'];
        }
var_dump($order);exit;
        try {
            $result = $this->oandaClient->executeTrade($this->oandaAccount, $order);
        } catch(\Throwable $e) {
            trigger_error('Failed to execute trade with message ' . $e->getMessage() . ' with order ' . var_export($order, true), E_USER_NOTICE);

            return ['status' => false, 'message' => 'Got exception trying to execute trade  prices'];
        }

        // @TODO store executed trades and pack
        try {
//            return $this->tradeRepository->saveTrade($trade);
        } catch(\Throwable $e) {

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
                case !isset($response['body']['account']['openPositionCount']) || !isset($response['body']['account']['balance']):
                    trigger_error('Failed to get expected account details in response ' . var_export($response, true), E_USER_NOTICE);
                    return [];
                case $response['body']['account']['openPositionCount'] >= self::MAX_ALLOWED_OPEN_POSITIONS:
                    trigger_error('Max allowed open positions reached', E_USER_NOTICE);
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
