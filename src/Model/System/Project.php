<?php
namespace TinyApp\Model\System;

use TinyApp\Model\System\ErrorHandler;
use TinyApp\Model\System\Router;
use TinyApp\Model\Middleware\ApplicationMiddlewareInterface;

class Project
{
    private const ROUTED_CONTROLLER_PLACEHOLDER = '%routedController%';
    private const ROUTED_ACTION_PLACEHOLDER = '%routedAction%';

    private const APPLICATION_STARTING_POINT_KEY = 'applicationStartingPoint';

    private const CONFIG_PATH = APP_ROOT_DIR . '/src/Config/';

    public function run() : void
    {
        // Get project parameters and create error handler
        $parameters = json_decode(file_get_contents(self::CONFIG_PATH . 'parameters.json'), true);
        new ErrorHandler($parameters['environment']);

        // Get routes and build request object
        $routes = json_decode(file_get_contents(self::CONFIG_PATH . 'routes.json'), true);
        $request = (new Router($routes))->buildRequest();

        // Check and replace dependencies placeholders from settings and parameters
        $settings = json_decode(file_get_contents(self::CONFIG_PATH . 'settings.json'), true);
        $parameters += $settings;
        $dependencies = file_get_contents(self::CONFIG_PATH . 'dependencies.json');
        $this->checkRequiredPlaceholders($dependencies, $parameters);
        $dependencies = json_decode($dependencies, true);
        $dependencies = $this->replacePlaceholders($dependencies, $parameters, $request);

        // Check classes to create
        $toCreate = [];
        $this->analyseInjections(0, $dependencies, $toCreate, $parameters[self::APPLICATION_STARTING_POINT_KEY]);

        // Build dependencies tree and process request
        $this->inject($dependencies, $toCreate);
        if (!($dependencies[$parameters[self::APPLICATION_STARTING_POINT_KEY]]['object'] instanceof ApplicationMiddlewareInterface)) {
            throw new \Exception('Application middleware has to implement ' . ApplicationMiddlewareInterface::class);
        }
        $response = $dependencies[$parameters[self::APPLICATION_STARTING_POINT_KEY]]['object']->process($request);
    }

    private function checkRequiredPlaceholders(string $dependencies, array $parameters) : void
    {
        if (
            !strpos($dependencies, self::ROUTED_CONTROLLER_PLACEHOLDER) ||
            !strpos($dependencies, self::ROUTED_ACTION_PLACEHOLDER)
        ) {
            throw new \Exception(
                'Could not find ' .
                self::ROUTED_CONTROLLER_PLACEHOLDER . ' placeholder or ' .
                self::ROUTED_ACTION_PLACEHOLDER . ' placeholder in dependencies.json' .
                'make sure you set these values as dependencies of object responsible for handling them'
            );
        }

        if(
            !isset($parameters[self::APPLICATION_STARTING_POINT_KEY]) ||
            !strpos($dependencies, $parameters[self::APPLICATION_STARTING_POINT_KEY])
        ) {
            throw new \Exception(
                'Could not find ' .
                self::APPLICATION_STARTING_POINT_KEY . ' key in settings.json or value in dependencies.json, ' .
                'make sure you specify it as one of the dependency object'
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
}
