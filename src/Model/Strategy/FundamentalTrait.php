<?php
namespace TinyApp\Model\Strategy;

trait FundamentalTrait
{
    //@TODO create logic to use indicators cpi ppi tb overnightRate inflation etc.

    protected function getFundamental(array $lastIndicators, string $selectedInstrument) : int
    {
        return rand(0,1) ? 1 : -1;
    }

    protected function selectInstrument(array $lastIndicators) : string
    {
        return 'AUD_USD';
    }
}
