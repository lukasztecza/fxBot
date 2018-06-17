<?php declare(strict_types=1);
namespace FxBot\Model\Client;

use HttpClient\Client\ClientAbstract;
use FxBot\Model\Entity\Order;

class OandaClient extends ClientAbstract
{
    protected function getClientHeaders() : array
    {
        return ['Authorization' => 'Bearer ' . $this->options['apiKey']];
    }

    public function getCurrentPrice($instrument) : array
    {
       $query = ['count' => 1, 'granularity' => 'S5', 'price' => 'BA'];

       return $this->get(['v3' => 'instruments', $instrument => 'candles'], $query);
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

    public function getAccountSummary(string $oandaAccount) : array
    {
        return $this->get(['v3' => 'accounts', $oandaAccount => 'summary']);
    }

    public function executeTrade(string $oandaAccount, Order $order) : array
    {
        return $this->post(['v3' => 'accounts', $oandaAccount => 'orders'], [], [], $order->getFormatted());
    }

    public function getOpenTrades(string $oandaAccount) : array
    {
        return $this->get(['v3' => 'accounts', $oandaAccount => 'trades']);
    }

    //@TODO add update trade/order
}
