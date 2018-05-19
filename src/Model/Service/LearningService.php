<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Service\SimulationService;

class LearningService
{
    private const STRATEGY_TO_LEARN = 'TinyApp\Model\Strategy\RigidAverageDistanceDeviationStrategy';
    private const INITIAL_PARAMS = [
        'rigidStopLoss' => 0.002,
        'takeProfitMultiplier' => 1,
        'longFastAverage' => 10,
        'longSlowAverage' => 20,
        'signalFastAverage' => 1,
        'signalSlowAverage' => 5,
        'followTrend' => 1,
        'lastPricesPeriod' => 'P10D'
    ];
    private const INSTRUMENT = 'EUR_USD';

    private const PARAM_MODIFICATION_FACTOR = 1.3;

    private $simulationService;
    private $params;

    public function __construct(SimulationService $simulationService)
    {
        $this->simulationService = $simulationService;
        $this->params = self::INITIAL_PARAMS;
    }

    public function learn() : array
    {
        $this->simulationService->setStrategiesForTest($this->getSttrategiesForTest());
        $result = $this->simulationService->run();

        var_dump($result);exit;

        //calculate sum of the simulation ids but disqualify all that have negative value and remove best and worst scores
        //if there was no previous set run simulation and set them both to current and previous
        //if the previous set was better reverse last change and go to next parameter and change it
        //if the previous set was equal or worse then change the parameter and run simulation
        //after full cycle of params switch direction of modification to (negative/positive) and run again
    }

    private function getSttrategiesForTest() : array
    {
        return [[
            'className' => self::STRATEGY_TO_LEARN,
            'params' => $this->params + ['instrument' => self::INSTRUMENT]
        ]];
    }

    private function modifyParamsForStrategy(string $paramName, bool $increase = true) : void
    {
        $this->params[$paramName] = $this->params[$paramName] * self::PARAM_MODIFICATION_FACTOR * ($increase ? 1 : -1);
    }
}
