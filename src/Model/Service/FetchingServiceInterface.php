<?php
namespace TinyApp\Model\Service;

interface FetchingServiceInterface
{
    public function populatePrices() : bool;

    public function populateIndicators() : bool;
}
