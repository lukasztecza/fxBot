<?php
namespace TinyApp\Model\Client;

use HttpClient\Client\ClientAbstract;
use TinyApp\Model\Strategy\Order;

class OandaClient extends ClientAbstract
{
    private const OANDA_DATETIME_FORMAT = 'Y-m-d\TH:i:s.u000\Z';

    protected function getClientCurlOptions() : array
    {
        return [];
    }

    protected function getClientResource() : array
    {
        return [];
    }

    protected function getClientQuery() : array
    {
        return [];
    }

    protected function getClientHeaders() : array
    {
        return ['Authorization' => 'Bearer ' . $this->options['apiKey']];
    }

    protected function getClientPayload() : array
    {
        return [];
    }

    public function getOandaDateTimeFormat()
    {
        return self::OANDA_DATETIME_FORMAT;
    }

    public function getPrices(string $instrument, string $startDate, string $endDate = null) : array
    {
        $query = ['from' => $startDate, 'granularity' => 'M15', 'price' => 'M'];
        if ($endDate) {
            $query['to'] = $endDate;
        }

        return $this->get(['v3' => 'instruments', $instrument => 'candles'], $query);
    }

    public function getIndicators(string $period) : array
    {
        $query = ['period' => $period];
        return $this->get(['labs' => 'v1', 'calendar' => null], $query);
    }

    public function getOpenPositions(string $oandaAccount) : array
    {
        return $this->get(['v3' => 'accounts', $oandaAccount => 'openPositions']);
    }

    public function getCurrentPrice($instrument) : array
    {
       $query = ['count' => 1, 'granularity' => 'S5', 'price' => 'BA'];

       return $this->get(['v3' => 'instruments', $instrument => 'candles'], $query);
    }

    public function executeTrade(Order $order) : array
    {
        var_dump($order->getFormatted());exit;
    }
}
