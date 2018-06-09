<?php
namespace FxBot\Model\Service;

abstract class FetchingServiceAbstract implements FetchingServiceInterface
{
    abstract public function populatePrices() : bool;

    abstract public function populateIndicators() : bool;
}
