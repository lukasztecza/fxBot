<?php declare(strict_types=1);
namespace FxBot\Model\Service;

interface FetchingServiceInterface
{
    public function populatePrices() : bool;

    public function populateIndicators() : bool;
}
