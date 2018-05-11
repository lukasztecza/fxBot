<?php
namespace TinyApp\Model\Repository;

use TinyApp\Model\Repository\DatabaseConnectionInterface;

class IndicatorRepository extends RepositoryAbstract
{
    public function __construct(DatabaseConnectionInterface $write)
    {
        parent::__construct($write);
    }

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
            'SELECT * FROM `indicator` ORDER BY `datetime` DESC LIMIT 1'
        );

        return !empty($records) ? array_pop($records) : [];
    }

    public function getIndicatorsForDates(array $instruments, string $startDateTime, string $endDateTime) : array
    {
        $params = [];
        $placeholders = $this->getInPlaceholdersIncludingParams($instruments, $params);
        $params['startdatetime'] = $startDateTime;
        $params['enddatetime'] = $endDateTime;

        return $this->getRead()->fetch(
            'SELECT * FROM `indicator`
            WHERE `instrument` IN (' . $placeholders . ') AND `datetime` > :startdatetime AND `datetime` <= :enddatetime
            ORDER BY `datetime` DESC',
            $params
        );
    }
}
