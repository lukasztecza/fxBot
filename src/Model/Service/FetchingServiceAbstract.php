<?php
namespace TinyApp\Model\Service;

abstract class FetchingServiceAbstract implements FetchingServiceInterface
{
    //@TODO it might be better to inject configurations to fetching service constructor and use same configuration in strategies
    //it should probably live in indicator service which is pulled by fetching services and strategy factory
    private const BANK_RATE_INDICATOR = 'bank';
    private const INFLATION_INDICATOR = 'inflation';
    private const COMPANIES_INDICATOR = 'companies';
    private const TRADE_BALANCE_INDICATOR = 'trade';
    private const UNEMPLOYMENT_INDICATOR = 'unemployment';
    private const SALES_INDICATOR = 'sales';

    protected function getBankRateIndicator() : string
    {
        return self::BANK_RATE_INDICATOR;
    }

    protected function getInflationIndicator() : string
    {
        return self::INFLATION_INDICATOR;
    }

    protected function getCompaniesIndicator() : string
    {
        return self::COMPANIES_INDICATOR;
    }

    protected function getTradeBalanceIndicator() : string
    {
        return self::TRADE_BALANCE_INDICATOR;
    }

    protected function getUnemploymentIndicator() : string
    {
        return self::UNEMPLOYMENT_INDICATOR;
    }

    protected function getSalesIndicator() : string
    {
        return self::SALES_INDICATOR;
    }

    abstract public function populatePrices() : bool;

    abstract public function populateIndicators() : bool;
}
