<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Repository\IndicatorRepository;

class IndicatorService
{
    private $indicatorRepository;

    public function __construct(IndicatorRepository $indicatorRepository) {
        $this->indicatorRepository = $indicatorRepository;
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
