<?php declare(strict_types=1);
namespace FxBot\Model\Repository;

use LightApp\Model\Repository\RepositoryAbstract; 

class SimulationRepository extends RepositoryAbstract
{
    public function saveSimulation(array $simulation) : int
    {
        try {
            $this->getWrite()->begin();
            $affectedId = $this->getWrite()->execute(
                'INSERT INTO `simulation`
                (`instrument`, `final_balance`, `max_balance`, `min_balance`, `profits`, `losses`, `simulation_start`, `simulation_end`, `datetime`)
                VALUES
                (:instrument, :finalBalance, :maxBalance, :minBalance, :profits, :losses, :simulationStart, :simulationEnd, :datetime)', [
                    'instrument' => $simulation['instrument'],
                    'finalBalance' => $simulation['finalBalance'],
                    'maxBalance' => $simulation['maxBalance'],
                    'minBalance' => $simulation['minBalance'],
                    'profits' => $simulation['profits'],
                    'losses' => $simulation['losses'],
                    'simulationStart' => $simulation['simulationStart'],
                    'simulationEnd' => $simulation['simulationEnd'],
                    'datetime' => $simulation['datetime']
                ]
            );

            $this->getWrite()->prepare(
                'INSERT INTO `parameter` (`name`) VALUES (:name)
                ON DUPLICATE KEY UPDATE `id` = `id`'
            );
            foreach ($simulation['parameters'] as $name => $value) {
                $this->getWrite()->execute(null, [
                    'name' => $name
                ]);
            }

            $this->getWrite()->prepare(
                'INSERT INTO `simulation_parameter` (`simulation_id`, `parameter_id`, `value`)
                SELECT :simulation_id, p.`id`, :value FROM `parameter` AS p WHERE p.`name` = :name'
            );
            foreach ($simulation['parameters'] as $name => $value) {
                $this->getWrite()->execute(null, [
                    'simulation_id' => $affectedId,
                    'value' => $value,
                    'name' => $name
                ]);
            }
            $this->getWrite()->commit();
        } catch(\Throwable $e) {
            trigger_error(
                'Rolling back after failed attempt to save simulation with message ' .
                $e->getMessage() . ' with payload ' . var_export($simulation, true),
                E_USER_NOTICE
            );
            $this->getWrite()->rollBack();
            throw $e;
        }
        $this->getWrite()->clean();

        return (int)$affectedId;
    }

    public function getSimulationsSummaryByIds(array $ids) : array
    {
        $params = [];
        $placeholders = $this->getInPlaceholdersIncludingParams($ids, $params);

        return $this->getRead()->fetch(
            "SELECT
                dummy.`params`,
                SUM(dummy.`final_balance`) total,
                MAX(dummy.`final_balance`) maxBalance,
                MIN(dummy.`final_balance`) minBalance,
                GROUP_CONCAT(dummy.id SEPARATOR ',') ids
            FROM (
                SELECT s.`id`, s.`final_balance`, s.`simulation_start`, s.`simulation_end`, GROUP_CONCAT(sp.`value` SEPARATOR ',') params
                FROM `simulation` s
                JOIN `simulation_parameter` sp ON sp.`simulation_id` = s.`id`
                WHERE s.`id` IN (" . $placeholders . ")
                GROUP BY s.`id`
                ORDER BY s.`simulation_start`
            ) dummy
            GROUP BY params
            ORDER BY total",
            $params
        );
    }
}
