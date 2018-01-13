<?php
namespace TinyApp\Model\Service;

class TradeService
{
    public function trade() : array
    {
    var_dump('WILL TRADE');exit;
        try {
            return $this->priceRepository->savePrices($prices);
        } catch(\Throwable $e) {
            trigger_error('Failed to save prices with message ' . $e->getMessage() . ' with paylaod ' . var_export($prices, true), E_USER_NOTICE);

            return [];
        }
    }
}
