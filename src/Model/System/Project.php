<?php
namespace TinyApp\Model\System;

use TinyApp\Model\System\ErrorHandler;
use TinyApp\Model\System\Router;
use TinyApp\Model\Middleware\ApplicationMiddlewareInterface;

class Project
{
    const ROUTED_CONTROLLER_PLACEHOLDER = '%routed_controller%';
    const ROUTED_ACTION_PLACEHOLDER = '%routed_action%';
    const APPLICATION_STARTING_POINT = 'rendering_middleware';

    const CONFIG_PATH = __DIR__ . '/../../Config/';

    public function run() : void
    {
        // Get project parameters and create error handler
        $parameters = json_decode(file_get_contents(self::CONFIG_PATH . 'parameters.json'), true);
        new ErrorHandler($parameters['environment']);

        // Get routes and build request object
        $routes = json_decode(file_get_contents(self::CONFIG_PATH . 'routes.json'), true);
        $request = (new Router($routes))->buildRequest();

        // Check and replace dependecies placeholders from settings and parameters
        $settings = json_decode(file_get_contents(self::CONFIG_PATH . 'settings.json'), true);
        $parameters += $settings;
        $dependencies = file_get_contents(self::CONFIG_PATH . 'dependencies.json');
        $this->checkRequiredPlaceholders($dependencies);
        $dependencies = json_decode($dependencies, true);
        $dependencies = $this->replacePlaceholders($dependencies, $parameters, $request);

        // Check classes to create
        $toCreate = [];
        $counter = 0;
        $this->analyseInjections($counter, $dependencies, $toCreate, self::APPLICATION_STARTING_POINT);

        // Build dependencies tree and process request
        $this->inject($dependencies, $toCreate);
        if (!($dependencies[self::APPLICATION_STARTING_POINT]['object'] instanceof ApplicationMiddlewareInterface)) {
            throw new \Exception('Application middleware has to implement ' . ApplicationMiddlewareInterface::class);
        }
        $response = $dependencies[self::APPLICATION_STARTING_POINT]['object']->process($request);
    }

    private function checkRequiredPlaceholders(string $dependencies) : void
    {
        if (
            !strpos($dependencies, self::ROUTED_CONTROLLER_PLACEHOLDER) ||
            !strpos($dependencies, self::ROUTED_ACTION_PLACEHOLDER) ||
            !strpos($dependencies, self::APPLICATION_STARTING_POINT)
        ) {
            throw new \Exception(
                'Could not find ' .
                self::ROUTED_CONTROLLER_PLACEHOLDER . ' placeholder or ' .
                self::ROUTED_ACTION_PLACEHOLDER . ' placeholder or ' .
                self::APPLICATION_STARTING_POINT . ' application starting point in dependencies.json, ' .
                'make sure you specify application starting point and ' .
                'set routed controller placeholder and routed action placeholder ' .
                'as dependecies of object responsible for handling them.'
            );
        }
    }

    private function replacePlaceholders(array $dependencies, array $parameters, Request $request) : array
    {
        $replacements[self::ROUTED_CONTROLLER_PLACEHOLDER] = '@' . $request->getController() . '@';
        $replacements[self::ROUTED_ACTION_PLACEHOLDER] = $request->getAction();
        foreach ($parameters as $placeholder => $value) {
            $replacements['%' . $placeholder . '%'] = $value;
        }

        foreach ($dependencies as &$dependency) {
            if (isset($dependency['inject'])) {
                foreach ($dependency['inject'] as &$inject) {
                    if (isset($replacements[$inject])) {
                        $inject = $replacements[$inject];
                    }
                }
            }
        }

        return $dependencies;
    }

    private function analyseInjections(int $counter, array $dependencies, array &$toCreate, string $name) : void
    {
        $counter++;
        if ($counter > 1000) {
            throw new \Exception('Too many dependencies or danger of infinite recurrence, reached counter ' . var_export($counter, true));
        }

        if (empty($dependencies[$name])) {
            throw new \Exception('Unrecognized dependency ' . $name);
        }

        $toCreate[] = $name;

        if (isset($dependencies[$name]['inject'])) {
            foreach ($dependencies[$name]['inject'] as $injection) {
                if (is_string($injection) && strpos($injection, '@') === 0) {
                    $subname = trim($injection, '@');
                    $this->analyseInjections($counter, $dependencies, $toCreate, $subname);
                }
            }
        }
    }

    private function inject(array &$dependencies, array $toCreate) : void
    {
        $index = count($toCreate);
        while ($index--) {
            if (isset($dependencies[$toCreate[$index]]['inject'])) {
                foreach ($dependencies[$toCreate[$index]]['inject'] as &$injection) {
                    if (is_string($injection) && strpos($injection, '@') === 0) {
                        $injection = $dependencies[trim($injection, '@')]['object'];
                    }
                }
            }

            if (empty($dependencies[$toCreate[$index]]['object'])) {
                if (isset($dependencies[$toCreate[$index]]['inject'])) {
                    $dependencies[$toCreate[$index]]['object'] = new $dependencies[$toCreate[$index]]['class'](
                        ...$dependencies[$toCreate[$index]]['inject']
                    );
                } else {
                    $dependencies[$toCreate[$index]]['object'] = new $dependencies[$toCreate[$index]]['class']();
                }
            }
        }
    }

    public static function buildParameters() : void
    {
        if (!file_exists(__DIR__ . '/../../Config/parameters.json')) {
            $pattern = file_get_contents(__DIR__ . '/../../Config/parameters.json.dist');
            //@TODO add reading and setting parameters if it is different than dist version
//            foreach ($pattern as $key => $value) {
// read inputed value and assign value
//            }
//            file_put_contents(__DIR__ . '/../Config/parameters.json', json_encode($pattern));
            var_dump(json_decode($pattern, true));
        }
    }
}
