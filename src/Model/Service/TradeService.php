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
/*        try {
            $response = $this->oandaClient->getOpenPositions($this->oandaAccount);
            if ($response['info']['http_code'] !== 200) {
                return ['status' => false, 'message' => 'Did not get 200 response trying to get open positions'];
            }
            if (!empty($response['positions'])) {
                return ['status' => true, 'message' => 'Maximum allowed open positions reached, did not execute any trade'];
            }
        } catch (\Throwable $e) {
            trigger_error('Failed to get open positions with message ' . $e->getMessage(), E_USER_NOTICE);
        }
*/
        $prices = [];
        try {
            foreach ($this->priceInstruments as $instrument) {
                $response = $this->oandaClient->getCurrentPrice($instrument);
                if (empty($response['body']['candles'][0]['mid']['c'])) {
                    trigger_error('Failed to get current price for ' . $instrument . ' did not execute any trade', E_USER_NOTICE);

                    return ['status' => true, 'message' => 'Could not get current prices, did not execute any trade'];
                }

                $prices[$instrument] = [
                    'ask' => $response['body']['candles'][0]['ask']['c'],
                    'bid' => $response['body']['candles'][0]['bid']['c']
                ];
            }
        } catch (\Throwable $e) {
            trigger_error('Failed to get current prices with message ' . $e->getMessage(), E_USER_NOTICE);
        }


        $strategy = $this->strategyFactory->getStrategy(SelectedStrategy::class);

        $order = $strategy->getOrderForPrices($prices);


        $this->oandaClient->executeTrade($order);
echo 'DONE';exit;
//@TODO save executed trade
        try {
            return $this->priceRepository->savePrices($prices);
        } catch(\Throwable $e) {
            trigger_error('Failed to save prices with message ' . $e->getMessage() . ' with paylaod ' . var_export($prices, true), E_USER_NOTICE);

            return [];
        }
    }
}
