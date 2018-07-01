<?php declare(strict_types=1);
namespace FxBot\Model\Repository;

use LightApp\Model\Repository\RepositoryAbstract;

class IndicatorRepository extends RepositoryAbstract
{
    public function saveIndicators(array $indicators) : array
    {
        $this->getWrite()->begin();
        try {
            $this->getWrite()->prepare(
                'INSERT INTO `indicator` (`instrument`, `datetime`, `name`, `type`, `forecast`, `actual`)
                VALUES (:instrument, :datetime, :name, :type, :forecast1, :actual1)
                ON DUPLICATE KEY UPDATE `forecast` = :forecast2, `actual` = :actual2'
            );
            $affectedIds = [];
            foreach ($indicators as $indicator) {
                $affectedIds[] = $this->getWrite()->execute(null, [
                    'instrument' => $indicator['instrument'],
                    'datetime' => $indicator['datetime'],
                    'name' => $indicator['name'],
                    'type' => $indicator['type'],
                    'forecast1' => $indicator['forecast'],
                    'actual1' => $indicator['actual'],
                    'forecast2' => $indicator['forecast'],
                    'actual2' => $indicator['actual']
                ]);
            }
            $this->getWrite()->commit();
        } catch(\Throwable $e) {
            $this->getWrite()->rollBack();
            trigger_error(
                'Rolling back after failed attempt to save indicators with message ' . $e->getMessage() .
                ' with payload ' . var_export($indicators, true)
            );
            throw $e;
        }

        return $affectedIds;
    }

    public function getLatestIndicator() : array
    {
        $records = $this->getRead()->fetch(
            'SELECT instrument, datetime, name, type, forecast, actual FROM `indicator` ORDER BY `datetime` DESC LIMIT 1'
        );

        return !empty($records) ? array_pop($records) : [];
    }

    public function getIndicatorsForDates(array $instruments, string $startDateTime, string $endDateTime) : array
    {
        $params = [];
        $placeholders = $this->getInPlaceholdersAndAddParams($instruments, $params);
        $params['startDateTime'] = $startDateTime;
        $params['endDateTime'] = $endDateTime;

        return $this->getRead()->fetch(
            'SELECT instrument, datetime, name, type, forecast, actual FROM `indicator`
            WHERE `instrument` IN (' . $placeholders . ') AND `datetime` > :startDateTime AND `datetime` <= :endDateTime
            ORDER BY `datetime` DESC',
            $params
        );
    }

    public function getComparison(string $type, string $priceInstrument) : array
    {
        $instruments = explode('_', $priceInstrument);

        $params = [];
        $placeholders = $this->getInPlaceholdersAndAddParams($instruments, $params);
        $params['type'] = $type;

        $comparisons = $this->getRead()->fetch(
            'SELECT `instrument`, `actual`, `datetime` FROM `indicator`
            WHERE `type` = :type
            AND `instrument` IN (' . $placeholders . ')
            ORDER BY `datetime` DESC',
            $params
        );

        $this->getRead()->prepare(
            'SELECT `close` FROM `price`
            WHERE `instrument` = :instrument
            AND `datetime` BETWEEN :startDateTime AND :endDateTime
            LIMIT 1'
        );

        foreach ($comparisons as $key => $comparison) {
            $startDateTime = new \DateTime($comparison['datetime'], new \DateTimeZone('UTC'));
            $endDateTime = clone $startDateTime;
            $startDateTime = $startDateTime->sub(new \DateInterval('PT20M'));
            $endDateTime = $endDateTime->add(new \DateInterval('PT20M'));
            $closePrice = $this->getRead()->fetch(null, [
                'instrument' => $priceInstrument,
                'startDateTime' => $startDateTime->format('Y-m-d H:i:s'),
                'endDateTime' => $endDateTime->format('Y-m-d H:i:s')
            ]);
            if (!empty($closePrice[0]['close'])) {
                $comparisons[$key]['price'] = $closePrice[0]['close'];
                $comparisons[$key]['priceInstrument'] = $priceInstrument;
            } else {
                unset($comparisons[$key]);
                trigger_error('Could not get proper structure for ' . $comparison['datetime']);
            }
        }

        return $comparisons;
    }
}
