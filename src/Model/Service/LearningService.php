<?php declare(strict_types=1);
namespace FxBot\Model\Service;

use FxBot\Model\Service\SimulationService;
use FxBot\Model\Repository\LearningRepository;

class LearningService
{
    private const MAX_LEARNING_ITERATIONS = 100;

    private const STRATEGY_TO_LEARN = 'FxBot\Model\Strategy\RigidTrendingStrategy';
    private const INITIAL_PARAMS = [
        'rigidStopLoss' => 0.001,
        'takeProfitMultiplier' => 9,
        'lossLockerFactor' => 2,
        'signalFastAverage' => 10,
        'signalSlowAverage' => 30,
        'longFastAverage' => 100,
        'longSlowAverage' => 200,
        'extremumRange' => 12,
        'bankFactor' => 1,
        'inflationFactor' => 1,
        'longFastAverage' => 1,
        'tradeFactor' => 1,
        'companiesFactor' => 1,
        'salesFactor' => 1,
        'unemploymentFactor' => 1,
        'bankRelativeFactor' => 1
    ];
    private const INSTRUMENT = 'EUR_USD';
    private const LEARNING_PERIODS = [
        ['start' => '2014-03-01 00:00:00', 'end' => '2015-03-01 00:00:00'],
        ['start' => '2015-03-01 00:00:00', 'end' => '2016-03-01 00:00:00'],
        ['start' => '2016-03-01 00:00:00', 'end' => '2017-03-01 00:00:00'],
        ['start' => '2017-03-01 00:00:00', 'end' => '2018-03-01 00:00:00']
    ];

    private const PARAM_MODIFICATION_FACTOR = 1.1;
    private const PARAM_MODIFICATION_FACTOR_CHANGE = 0.1;
    private const PARAM_MODIFICATION_FACTOR_LIMIT = 5;

    private $simulationService;
    private $learningRepository;

    public function __construct(SimulationService $simulationService, LearningRepository $learningRepository)
    {
        $this->simulationService = $simulationService;
        $this->learningRepository = $learningRepository;
    }

    public function learn() : array
    {
        $pack = (new \DateTime('', new \DateTimeZone('UTC')))->format('YmdHis') . bin2hex(random_bytes(8));
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
        $this->simulationService->setSimulationStrategies($this->getStrategiesForTest($params));
        $this->simulationService->setSimulationPeriods(self::LEARNING_PERIODS);

        while ($counter++ <= self::MAX_LEARNING_ITERATIONS) {
            if ($counter - 2 && !$this->modifyParamsForStrategy($params, $currentParam, $paramModificationFactor, $increase)) {
                $this->selectNextParam($currentParam, $lastParam, $params, $increase);
                trigger_error('Pushing change to the next param ' . var_export($currentParam, true), E_USER_NOTICE);
                continue;
            }

            $result = $this->simulationService->run();

            if (empty($currentSimulationIds)) {
                $bestSimulationIds = $result['simulationIds'];
            }
            $currentSimulationIds = $result['simulationIds'];

            $bestSummary = $this->simulationService->getSimulationsSummaryByIds($bestSimulationIds);
            $currentSummary = $this->simulationService->getSimulationsSummaryByIds($currentSimulationIds);
            $bestSummaryProfitability = $this->calculateProfitability($bestSummary);
            $currentSummaryProfitability = $this->calculateProfitability($currentSummary);

            echo PHP_EOL;
            switch (true) {
                case $bestSummaryProfitability < $currentSummaryProfitability:
                    echo 'IMPROVEMENT' . ' best total: ' . $bestSummary['total'] . ' current total: ' . $currentSummary['total'];
                    echo ' best profitability: ' . $bestSummaryProfitability . ' current profitability: ' . $currentSummaryProfitability . PHP_EOL;
                    $bestSummary = $currentSummary;
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
                case $bestSummaryProfitability > $currentSummaryProfitability:
                    echo 'DETERIORATION' . ' best total: ' . $bestSummary['total'] . ' current total: ' . $currentSummary['total'];
                    echo ' best profitability: ' . $bestSummaryProfitability . ' current profitability: ' . $currentSummaryProfitability . PHP_EOL;
                    $this->modifyParamsForStrategy($params, $currentParam, $paramModificationFactor, -$increase);
                    $this->selectNextParam($currentParam, $lastParam, $params, $increase);
                    $noImprovementCounter++;
                    break 1;
                default;
                    echo 'NO CHANGE' . ' best total: ' . $bestSummary['total'] . ' current total: ' . $currentSummary['total'];
                    echo ' best profitability: ' . $bestSummaryProfitability . ' current profitability: ' . $currentSummaryProfitability . PHP_EOL;
                    $this->selectNextParam($currentParam, $lastParam, $params, $increase);
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
                'homeCurrency' => 'CAD',
                'singleTransactionRisk' => 0.005,
                'instrument' => self::INSTRUMENT,
                'followTrend' => 0,
                'lastPricesPeriod' => 'P30D',
                'lastIndicatorsPeriod' => 'P12M'
            ]
        ]];
    }

    private function calculateProfitability(array $summary) : float
    {
        return $summary['total'] - ($summary['maxBalance'] - $summary['minBalance']);
    }

    private function modifyParamsForStrategy(array &$params, string $paramName, float $paramModificationFactor, int $increase) : bool
    {
        $newValue = $params[$paramName] * ($increase > 0 ? $paramModificationFactor : (1/$paramModificationFactor));
        switch (true) {
            case $newValue < 0:
                trigger_error('Learning tried to set negative parameter value ' . var_export($newValue, true), E_USER_NOTICE);
                break;
            case $paramName === 'rigidStopLoss' && $newValue < 0.001:
                trigger_error('Learnin tried to set too low stop loss' . var_export(['rigidStopLoss' => $newValue], true), E_USER_NOTICE);
                break;
            case $paramName === 'signalFastAverage' && $newValue >= $params['signalSlowAverage']:
                trigger_error('Learnin tried to set too high signalFastAverage' . var_export(['signalFastAverage' => $newValue], true), E_USER_NOTICE);
                break;
            case $paramName === 'longFastAverage' && $newValue >= $params['longSlowAverage']:
                trigger_error('Learnin tried to set too high longFastAverage' . var_export(['longFastAverage' => $newValue], true), E_USER_NOTICE);
                break;
            case $paramName === 'signalSlowAverage' && $newValue <= $params['signalFastAverage']:
                trigger_error('Learnin tried to set too low signalSlowAverage' . var_export(['signalSlowAverage' => $newValue], true), E_USER_NOTICE);
                break;
            case $paramName === 'longSlowAverage' && $newValue <= $params['longFastAverage']:
                trigger_error('Learnin tried to set too low longSlowAverage' . var_export(['longSlowAverage' => $newValue], true), E_USER_NOTICE);
                break;
            default:
                $params[$paramName] = $newValue;
                return true;
        }

        return false;
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
