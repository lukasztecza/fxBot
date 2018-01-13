<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\MarketService;
use HttpClient\ClientFactory;

interface FetchingServiceInterface
{
    public function populatePrices() : bool;

    public function populateIndicators() : bool;
}
