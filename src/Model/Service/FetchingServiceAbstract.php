<?php declare(strict_types=1);
namespace FxBot\Model\Service;

abstract class FetchingServiceAbstract implements FetchingServiceInterface
{
    abstract public function populatePrices() : bool;

    abstract public function populateIndicators() : bool;
}
