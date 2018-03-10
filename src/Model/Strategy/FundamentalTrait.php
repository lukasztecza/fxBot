<?php
namespace TinyApp\Model\Strategy;

trait FundamentalTrait
{
    protected function getFundamental(string $selectedInstrument) : int
    {
        return rand(0,1) ? 1 : -1;
    }
}
