<?php
namespace FxBot\Model\Service;

interface FetchingServiceInterface
{
    public function populatePrices() : bool;

    public function populateIndicators() : bool;
}
