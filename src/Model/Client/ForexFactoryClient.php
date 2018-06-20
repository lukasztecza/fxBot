<?php declare(strict_types=1);
namespace FxBot\Model\Client;

use HttpClient\Client\ClientAbstract;

class ForexFactoryClient extends ClientAbstract
{
    public function getIndicators(string $range) : array
    {
        $query = ['range' => $range];

        return $this->get(['calendar.php' => null], $query);
    }
}
