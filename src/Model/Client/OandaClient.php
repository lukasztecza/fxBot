<?php
namespace TinyApp\Model\Client;

use HttpClient\Client\ClientAbstract;

class OandaClient extends ClientAbstract
{
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

    public function getPrices(string $instrument, string $startDate, string $endDate = null) : array
    {
        $query = ['from' => $startDate, 'granularity' => 'M15'];
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
}
