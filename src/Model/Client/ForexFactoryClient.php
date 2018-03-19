<?php
namespace TinyApp\Model\Client;

use HttpClient\Client\ClientAbstract;

class ForexFactoryClient extends ClientAbstract
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
        return [];
    }

    protected function getClientPayload() : array
    {
        return [];
    }

    public function getIndicators(string $range) : array
    {
        $query = ['range' => $range];
        return $this->get(['calendar.php' => null], $query);
    }
}
