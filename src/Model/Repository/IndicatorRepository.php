<?php
namespace TinyApp\Model\Repository;

class IndicatorRepository extends RepositoryAbstract
{
    public function saveIndicators(array $indicators) : array
    {
        $this->getWrite()->begin();
        try {
            $this->getWrite()->prepare(
                'INSERT INTO `indicator` (`instrument`, `datetime`, `name`, `unit`, `forecast`, `market`, `actual`)
                VALUES (:instrument, :datetime, :name, :unit, :forecast1, :market1, :actual1)
                ON DUPLICATE KEY UPDATE `forecast` = :forecast2, `market` = :market2, `actual` = :actual2'
            );
            $affectedIds = [];
            foreach ($indicators as $indicator) {
                $affectedIds[] = $this->getWrite()->execute(null, [
                    'instrument' => $indicator['instrument'],
                    'datetime' => $indicator['datetime'],
                    'name' => $indicator['name'],
                    'unit' => $indicator['unit'],
                    'forecast1' => $indicator['forecast'],
                    'market1' => $indicator['market'],
                    'actual1' => $indicator['actual'],
                    'forecast2' => $indicator['forecast'],
                    'market2' => $indicator['market'],
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
}
