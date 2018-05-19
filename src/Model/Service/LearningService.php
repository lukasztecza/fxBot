<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Service\SimulationService;

class LearningService
{
    private const MAX_LEARNING_ITERATIONS = 20;
    private const EXPERT_LEVEL = 12;

    private const STRATEGY_TO_LEARN = 'TinyApp\Model\Strategy\RigidAverageDistanceDeviationStrategy';
    private const INITIAL_PARAMS = [
        'rigidStopLoss' => 0.003,
        'takeProfitMultiplier' => 1,
        'signalFastAverage' => 20,
        'signalSlowAverage' => 50,
        'longFastAverage' => 400,
        'longSlowAverage' => 500
    ];
    private const INSTRUMENT = 'EUR_USD';

    private const PARAM_MODIFICATION_FACTOR = 1.1;

    private $simulationService;

    public function __construct(SimulationService $simulationService)
    {
        $this->simulationService = $simulationService;
    }

    public function learn() : array
    {
        $counter = 1;
        $params = self::INITIAL_PARAMS;
        end($params);
        $lastParam = key($params);
        reset($params);
        $currentParam = key($params);
        $lastSimulationIds = [];
        $currentSimulationIds = [];
        $lastSummary = [];
        $currentSummary = [];
        $increase = 1;
        $noChangeCounter = 0;

        while ($counter++ <= self::MAX_LEARNING_ITERATIONS) {
            $this->simulationService->setStrategiesForTest($this->getStrategiesForTest($params));
            $result = $this->simulationService->run();

            if (empty($currentSimulationIds)) {
                $lastSimulationIds = $result['simulationIds'];
            } else {
                $lastSimulationIds = $currentSimulationIds;
            }
            $currentSimulationIds = $result['simulationIds'];

            $lastSummary = $this->simulationService->getSimulationsSummaryByIds($lastSimulationIds);
            $currentSummary = $this->simulationService->getSimulationsSummaryByIds($currentSimulationIds);

            switch (true) {
                case (
                    $lastSummary['total'] < $currentSummary['total'] &&
                    $currentSummary['min_balance'] > $this->simulationService->getInitialTestBalance()
                ):
                    echo 'IMPROVEMENT' . PHP_EOL;
                    $this->modifyParamsForStrategy($params, $currentParam, self::PARAM_MODIFICATION_FACTOR, $increase);
                    $noChangeCounter = 0;
                    break 1;
                case (
                    $lastSummary['total'] > $currentSummary['total'] ||
                    $currentSummary['min_balance'] < $this->simulationService->getInitialTestBalance()
                ):
                    echo 'DETERIORATION' . PHP_EOL;
                    $this->modifyParamsForStrategy($params, $currentParam, self::PARAM_MODIFICATION_FACTOR, -$increase);
                    $this->selectNextParam($currentParam, $lastParam, $params, $increase);
                    $this->modifyParamsForStrategy($params, $currentParam, self::PARAM_MODIFICATION_FACTOR, $increase);
                    $noChangeCounter = 0;
                    break 1;
                default;
                    echo 'NO CHANGE' . PHP_EOL;
                    $this->selectNextParam($currentParam, $lastParam, $params, $increase);
                    $this->modifyParamsForStrategy($params, $currentParam, self::PARAM_MODIFICATION_FACTOR, $increase);
                    $noChangeCounter++;
                    if ($noChangeCounter > self::EXPERT_LEVEL) {
                        return [
                            'status' => 'success',
                            'message' => 'Reached expert level with params ' . var_export($params, true)
                        ];
                    }
                    break 1;
            }
        }

        return [
            'status' => 'success',
            'message' => 'Finished learnig with params ' . var_export($params, true)
        ];
    }

    private function getStrategiesForTest($params) : array
    {
        return [[
            'className' => self::STRATEGY_TO_LEARN,
            'params' => $params + [
                'instrument' => self::INSTRUMENT,
                'followTrend' => 0,
                'lastPricesPeriod' => 'P30D'
            ]
        ]];
    }

    private function modifyParamsForStrategy(array &$params, string $paramName, float $paramModificationFactor, int $increase) : void
    {
        $newValue = $params[$paramName] * ($increase > 0 ? $paramModificationFactor : (1/$paramModificationFactor));
        if ($newValue > 0) {
            $params[$paramName] = $newValue;
        } else {
            throw new \Exception('Learning ended up with negative parameter value ' . var_export($newValue, true));
        }
    }

    private function selectNextParam(string &$currentParam, string $lastParam, array &$params, int &$increase) : void
    {
        if ($currentParam === $lastParam) {
            reset($params);
            $increase *= -1;
        } else {
            next($params);
        }
        $currentParam = key($params);
    }
}
