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

    public function getLastIndicatorsByPeriod(string $instrument, string $period, string $currentDateTime = null) : array
    {
        try {
            $endDateTime = $currentDateTime ? new \DateTime($currentDateTime,new \DateTimeZone('UTC')) : new \DateTime(null, \DateTimeZone('UTC'));
            $endDateTime = $endDateTime->sub(new \DateInterval($period));
            $instruments = explode('_', $instrument);
            $indicators = $this->indicatorRepository->getIndicatorsForDates($instruments, $endDateTime->format('Y-m-d H:i:s'), $currentDateTime);

            $sortedIndicators = [];
            foreach ($indicators as $indicator) {
                $sortedIndicators[$indicator['instrument']][] = $indicator;
            }

            return $sortedIndicators;
        } catch(\Throwable $e) {
            trigger_error('Failed to get last indicators by period with message ' . $e->getMessage());

            return [];
        }
    }
}
