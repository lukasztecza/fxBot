<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Service\SimulationService;
use TinyApp\Model\Repository\LearningRepository;

class LearningService
{
    private const MAX_LEARNING_ITERATIONS = 20;

    private const STRATEGY_TO_LEARN = 'TinyApp\Model\Strategy\RigidAverageDistanceDeviationStrategy';
    private const INITIAL_PARAMS = [
        'rigidStopLoss' => 0.0020,
        'takeProfitMultiplier' => 1,
        'signalFastAverage' => 10,
        'signalSlowAverage' => 50,
        'longFastAverage' => 100,
        'longSlowAverage' => 500
    ];
    private const INSTRUMENT = 'EUR_USD';

    private const PARAM_MODIFICATION_FACTOR = 1.1;
    private const PARAM_MODIFICATION_FACTOR_CHANGE = 0.1;
    private const PARAM_MODIFICATION_FACTOR_LIMIT = 2;

    private $simulationService;
    private $learningRepository;

    public function __construct(SimulationService $simulationService, LearningRepository $learningRepository)
    {
        $this->simulationService = $simulationService;
        $this->learningRepository = $learningRepository;
    }

    public function learn() : array
    {
        $pack = (new \DateTime(null, new \DateTimeZone('UTC')))->format('YmdHis') . bin2hex(random_bytes(8));
        $counter = 1;
        $params = self::INITIAL_PARAMS;
        $noImprovementLimit = count($params) * 2;
        end($params);
        $lastParam = key($params);
        reset($params);
        $currentParam = key($params);
        $bestSimulationIds = [];
        $currentSimulationIds = [];
        $bestSummary = [];
        $currentSummary = [];
        $increase = 1;
        $noImprovementCounter = 0;
        $paramModificationFactor = self::PARAM_MODIFICATION_FACTOR;

        while ($counter++ <= self::MAX_LEARNING_ITERATIONS) {
            $this->simulationService->setStrategiesForTest($this->getStrategiesForTest($params));
            $result = $this->simulationService->run();

            if (empty($currentSimulationIds)) {
                $bestSimulationIds = $result['simulationIds'];
            }
            $currentSimulationIds = $result['simulationIds'];

            $bestSummary = $this->simulationService->getSimulationsSummaryByIds($bestSimulationIds);
            $currentSummary = $this->simulationService->getSimulationsSummaryByIds($currentSimulationIds);

            echo PHP_EOL;
            switch (true) {
                case (
                    $bestSummary['total'] < $currentSummary['total'] &&
                    $currentSummary['minBalance'] > $this->simulationService->getInitialTestBalance()
                ):
                    echo 'IMPROVEMENT' . ' best: ' . $bestSummary['total'] . ' current: ' . $currentSummary['total'] . PHP_EOL;
                    $bestSummary = $currentSummary;
                    $this->modifyParamsForStrategy($params, $currentParam, $paramModificationFactor, $increase);
                    $bestSimulationIds = $currentSimulationIds;
                    $noImprovementCounter = 0;
                    $learning = [
                        'total' => $bestSummary['total'],
                        'maxBalance' => $bestSummary['maxBalance'],
                        'minBalance' => $bestSummary['minBalance'],
                        'pack' => $pack,
                        'simulationIds' => $bestSimulationIds
                    ];
                    try {
                        $this->learningRepository->saveLearning($learning);
                    } catch (\Throwable $e) {
                        trigger_error('Failed to save learning ' . var_export($learning, E_USER_NOTICE));
                    }
                    break 1;
                case (
                    $bestSummary['total'] > $currentSummary['total'] ||
                    $currentSummary['minBalance'] < $this->simulationService->getInitialTestBalance()
                ):
                    echo 'DETERIORATION' . ' best: ' . $bestSummary['total'] . ' current: ' . $currentSummary['total'] . PHP_EOL;
                    $this->modifyParamsForStrategy($params, $currentParam, $paramModificationFactor, -$increase);
                    $this->selectNextParam($currentParam, $lastParam, $params, $increase);
                    $this->modifyParamsForStrategy($params, $currentParam, $paramModificationFactor, $increase);
                    $noImprovementCounter++;
                    break 1;
                default;
                    echo 'NO CHANGE' . ' best: ' . $bestSummary['total'] . ' current: ' . $currentSummary['total'] . PHP_EOL;
                    $this->selectNextParam($currentParam, $lastParam, $params, $increase);
                    $this->modifyParamsForStrategy($params, $currentParam, $paramModificationFactor, $increase);
                    $noImprovementCounter++;
                    break 1;
            }
            echo 'Learn changing param: ' . $currentParam . ' with factor of ' . ($increase * $paramModificationFactor) . PHP_EOL;

            if ($noImprovementCounter > $noImprovementLimit) {
                if ($paramModificationFactor < self::PARAM_MODIFICATION_FACTOR_LIMIT) {
                    $paramModificationFactor += self::PARAM_MODIFICATION_FACTOR_CHANGE;
                    $noImprovementCounter = 0;
                } else {
                    return [
                        'status' => 'success',
                        'message' => 'Finished reaching limit of param modification factor with summary ' . var_export($bestSummary, true)
                    ];
                }
            }
        }

        return [
            'status' => 'success',
            'message' => 'Finished reaching max learning iterations with summary ' . var_export($bestSummary, true)
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
