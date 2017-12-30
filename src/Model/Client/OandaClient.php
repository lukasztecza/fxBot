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
}
