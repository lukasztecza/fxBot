<?php
namespace FxBot\Model\Service;

use FxBot\Model\Repository\IndicatorRepository;

class IndicatorService
{
    private const BANK_RATE_INDICATOR = 'bank';
    private const INFLATION_INDICATOR = 'inflation';
    private const COMPANIES_INDICATOR = 'companies';
    private const TRADE_BALANCE_INDICATOR = 'trade';
    private const UNEMPLOYMENT_INDICATOR = 'unemployment';
    private const SALES_INDICATOR = 'sales';

    private $indicatorRepository;

    public function __construct(IndicatorRepository $indicatorRepository) {
        $this->indicatorRepository = $indicatorRepository;
    }

    public function getBankRateIndicator() : string
    {
        return self::BANK_RATE_INDICATOR;
    }

    public function getInflationIndicator() : string
    {
        return self::INFLATION_INDICATOR;
    }

    public function getCompaniesIndicator() : string
    {
        return self::COMPANIES_INDICATOR;
    }

    public function getTradeBalanceIndicator() : string
    {
        return self::TRADE_BALANCE_INDICATOR;
    }

    public function getUnemploymentIndicator() : string
    {
        return self::UNEMPLOYMENT_INDICATOR;
    }

    public function getSalesIndicator() : string
    {
        return self::SALES_INDICATOR;
    }

    public function saveIndicators(array $indicators) : array
    {
        try {
            return $this->indicatorRepository->saveIndicators($indicators);
        } catch(\Throwable $e) {
            trigger_error(
                'Failed to save indicators with message ' . $e->getMessage() . ' with paylaod ' . var_export($indicators, true), E_USER_NOTICE
            );

            return [];
        }
    }

    public function getLatestIndicator() : array
    {
        try {
            return $this->indicatorRepository->getLatestIndicator();
        } catch(\Throwable $e) {
            trigger_error('Failed to get latest indicator with message ' . $e->getMessage());

            return [];
        }
    }

    public function getLastIndicatorsByPeriod(array $instruments, string $period, string $currentDateTime = null) : array
    {
        try {
            $startDateTime = $currentDateTime ? new \DateTime($currentDateTime,new \DateTimeZone('UTC')) : new \DateTime(null, \DateTimeZone('UTC'));
            $startDateTime = $startDateTime->sub(new \DateInterval($period));
            return $this->indicatorRepository->getIndicatorsForDates($instruments, $startDateTime->format('Y-m-d H:i:s'), $currentDateTime);
        } catch(\Throwable $e) {
            trigger_error('Failed to get last indicators by period with message ' . $e->getMessage());

            return [];
        }
    }
}
