<?php declare(strict_types=1);
namespace FxBot\Model\Service;

use FxBot\Model\Repository\PriceRepository;

class PriceService
{
    private const DEFAULT_INITIAL_DATE = '2017-01-01 00:00:00';

    private $priceRepository;

    public function __construct(PriceRepository $priceRepository) {
        $this->priceRepository = $priceRepository;
    }

    public function savePrices(array $prices) : array
    {
        try {
            return $this->priceRepository->savePrices($prices);
        } catch(\Throwable $e) {
            trigger_error('Failed to save prices with message ' . $e->getMessage() . ' with paylaod ' . var_export($prices, true), E_USER_NOTICE);

            return [];
        }
    }

    public function getLatestPriceByInstrument(string $instrument) : array
    {
        try {
            return $this->priceRepository->getLatestPriceByInstrument($instrument);
        } catch(\Throwable $e) {
            trigger_error('Failed to get latest price with message ' . $e->getMessage());

            return []; 
        }
    }

    public function getInitialPrices(array $priceInstruments, string $initialDateTime = null) : array
    {
        try {
            if (!$initialDateTime) {
                $initialDateTime = self::DEFAULT_INITIAL_DATE;
            }

            return $this->priceRepository->getInitialPrices($priceInstruments, $initialDateTime);
        } catch(\Throwable $e) {
            trigger_error('Failed to get initial prices with message ' . $e->getMessage());

            return [];
        }
    }

    public function getLastPricesByPeriod(string $instrument, string $period, string $currentDateTime = null) : array
    {
        try {
            if (is_null($currentDateTime)) {
                $currentDateTime = new \DateTime('', new \DateTimeZone('UTC'));
            } else {
                $currentDateTime = new \DateTime($currentDateTime, new \DateTimeZone('UTC'));
            }

            $endDateTime = clone $currentDateTime;
            $endDateTime = $endDateTime->sub(new \DateInterval($period));
            $prices = $this->priceRepository->getPricesForDates(
                $instrument, $endDateTime->format('Y-m-d H:i:s'), $currentDateTime->format('Y-m-d H:i:s')
            );

            $highLows = [];
            foreach ($prices as $price) {
                $highLows[] = [
                    'high' => $price['high'],
                    'low' => $price['low']
                ];
            }

            return $highLows;
        } catch(\Throwable $e) {
            trigger_error('Failed to get last prices by period with message ' . $e->getMessage());

            return [];
        }
    }
}
