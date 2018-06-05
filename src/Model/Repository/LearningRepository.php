<?php
namespace FxBot\Model\Repository;

use LightApp\Model\Repository\RepositoryAbstract;

class LearningRepository extends RepositoryAbstract
{
    public function saveLearning(array $learning) : int
    {
        try {
            $this->getWrite()->begin();
            $affectedId = $this->getWrite()->execute(
                'INSERT INTO `learning` (`total`, `max_balance`, `min_balance`, `pack`)
                VALUES (:total, :maxBalance, :minBalance, :pack)', [
                    'total' => $learning['total'],
                    'maxBalance' => $learning['maxBalance'],
                    'minBalance' => $learning['minBalance'],
                    'pack' => $learning['pack']
                ]
            );

            $this->getWrite()->prepare(
                'INSERT INTO `learning_simulation` (`learning_id`, `simulation_id`) VALUES (:learningId, :simulationId)'
            );
            foreach ($learning['simulationIds'] as $id) {
                $this->getWrite()->execute(null, [
                    'learningId' => $affectedId,
                    'simulationId' => $id
                ]);
            }
            $this->getWrite()->commit();
        } catch(\Throwable $e) {
            trigger_error(
                'Rolling back after failed attempt to save learning with message ' .
                $e->getMessage() . ' with payload ' . var_export($learning, true),
                E_USER_NOTICE
            );
            $this->getWrite()->rollBack();
            throw $e;
        }
        $this->getWrite()->clean();

        return (int)$affectedId;
    }
}
