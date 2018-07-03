<?php declare(strict_types=1);
namespace FxBot\Model\Command;

use LightApp\Model\Command\CommandInterface;
use FxBot\Model\Service\SimulationService;
use LightApp\Model\Command\CommandResult;

class SimulationCommand implements CommandInterface
{
    private const FORCE_INSTRUMENT = 'EUR_USD';
    private const SIMULATION_PERIODS = [
/*        ['start' => '2010-03-01 00:00:00', 'end' => '2010-03-20 00:00:00'],
        ['start' => '2011-03-01 00:00:00', 'end' => '2011-03-20 00:00:00'],
        ['start' => '2012-03-01 00:00:00', 'end' => '2013-03-01 00:00:00'],
        ['start' => '2013-03-01 00:00:00', 'end' => '2014-03-01 00:00:00'],
        ['start' => '2014-03-01 00:00:00', 'end' => '2015-03-01 00:00:00'],
        ['start' => '2015-03-01 00:00:00', 'end' => '2016-03-01 00:00:00'],
        ['start' => '2016-03-01 00:00:00', 'end' => '2017-03-01 00:00:00'],
        ['start' => '2017-03-01 00:00:00', 'end' => '2018-03-01 00:00:00'], */
        ['start' => '2010-03-01 00:00:00', 'end' => '2018-06-28 00:00:00']
    ];
    private const STRATEGIES_CLASS_FOR_SIMULATION = [
//        'FxBot\Model\Strategy\RigidAverageStrategy',
//        'FxBot\Model\Strategy\RigidFundamentalStrategy',
//        'FxBot\Model\Strategy\RigidTrendingStrategy',
//        'FxBot\Model\Strategy\RigidRandomStrategy'
        'FxBot\Model\Strategy\RigidDeviationStrategy',
        'FxBot\Model\Strategy\RigidRandomStrategy'
    ];
    private const CHANGING_PARAMETERS = [
        'rigidStopLoss' => [0.0025],
        'takeProfitMultiplier' => [9.6],
        'lossLockerFactor' => [1],
        'signalFastAverage' => [34],
        'signalSlowAverage' => [100],
/*        'longFastAverage' => [100],
        'longSlowAverage' => [200],
        'extremumRange' => [12],
        'bankFactor' => [1],
        'inflationFactor' => [1],
        'tradeFactor' => [1],
        'companiesFactor' => [1],
        'salesFactor' => [1],
        'unemploymentFactor' => [1],
        'bankRelativeFactor' => [1], */
        'homeCurrency' => ['CAD'],
        'singleTransactionRisk' => [0.02],
        'instrument' => ['EUR_USD'],
//        'followTrend' => [0],
        'lastPricesPeriod' => ['P60D'],
//        'lastIndicatorsPeriod' => ['P12M']
    ];

    private $simulationService;

    public function __construct(SimulationService $simulationService)
    {
        $this->simulationService = $simulationService;
    }

    public function execute() : CommandResult
    {
        $simulationPeriods = $this->buildSimulationPeriods();
        $simulationStrategies = $this->buildSimulationStrategies();
        $this->simulationService->setSimulationPeriods($simulationPeriods);
        $this->simulationService->setSimulationStrategies($simulationStrategies);
        $simulationResult = $this->simulationService->run();
        return new CommandResult($simulationResult['status'], $simulationResult['message']);
    }

    private function buildSimulationPeriods() : array
    {
        return self::SIMULATION_PERIODS;
    }

    private function buildSimulationStrategies() : array
    {
        $strategies = [];
        $counter = 0;
        $changingParameters = self::CHANGING_PARAMETERS;
        end($changingParameters);
        $lastKey = key($changingParameters);

        foreach (self::STRATEGIES_CLASS_FOR_SIMULATION as $strategy) {
            if(!is_null(self::FORCE_INSTRUMENT)) {
                $params = ['instrument' => self::FORCE_INSTRUMENT];
                reset($changingParameters);
                $this->nestIteration($counter, $strategies, $changingParameters, $lastKey, $params, $strategy);
            } else {
                foreach ($this->priceInstruments as $instrument) {
                    $params = ['instrument' => $instrument];
                    reset($changingParameters);
                    $this->nestIteration($counter, $strategies, $changingParameters, $lastKey, $params, $strategy);
                }
            }
        }

        return $strategies;
    }

    private function nestIteration(int $counter, array &$strategies, array &$changingParameters, string $lastKey, array $params, string $strategy) : void
    {
        $counter++;
        if ($counter > 100) {
            throw new \Exception('Too deep nesting or danger of infinite recurrence, reached counter ' . var_export($counter, true));
        }

        $key = key($changingParameters);
        foreach ($changingParameters[$key] as $value) {
            $params[$key] = $value;

            if ($key === $lastKey) {
                $strategies[] = [
                    'className' => $strategy,
                    'params' => $params
                ];
            } else {
                next($changingParameters);
                $this->nestIteration($counter, $strategies, $changingParameters, $lastKey, $params, $strategy);
            }
        }
        prev($changingParameters);
    }
}
