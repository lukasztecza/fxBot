<?php
namespace TinyApp\Model\Repository;

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
                (:instrument, :final_balance, :max_balance, :min_balance, :profits, :losses, :simulation_start, :simulation_end, :datetime)', [
                    'instrument' => $simulation['instrument'],
                    'final_balance' => $simulation['finalBalance'],
                    'max_balance' => $simulation['maxBalance'],
                    'min_balance' => $simulation['minBalance'],
                    'profits' => $simulation['profits'],
                    'losses' => $simulation['losses'],
                    'simulation_start' => $simulation['simulationStart'],
                    'simulation_end' => $simulation['simulationEnd'],
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
}
