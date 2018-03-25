<?php
namespace TinyApp\Model\Strategy;

trait FundamentalTrait
{
    //@TODO create logic to use indicators cpi ppi tb overnightRate inflation etc.
    //when indicators are strored consider GDP CPI PPI RetailSails TradeBalance OvernightRate check in notes
    //create forexfactory indicatorfetchservice and pull data from there
    //try to have common unit if possible
    //pull data from 2010 until now for both prices and indicators
    protected function getFundamental(array $lastIndicators, string $selectedInstrument) : int
    {
        return 1;
    }

    protected function selectInstrument(array $lastIndicators) : string
    {
        return 'AUD_USD';
    }
}
