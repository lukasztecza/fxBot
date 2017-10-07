<?php
namespace TinyApp\System;

use TinyApp\System\ErrorHandler;
use TinyApp\System\Router;
use TinyApp\System\ApplicationMiddlewareInterface;

class Project
{
    const ROUTED_CONTROLLER_PLACEHOLDER = '%routed_controller%';
    const ROUTED_ACTION_PLACEHOLDER = '%routed_action%';
    const APPLICATION_STARTING_POINT = 'rendering_middleware';

    public function run()
    {
        // Get project variables and set error handler
        $parameters = json_decode(file_get_contents(__DIR__ . '/../Config/parameters.json'), true);
        $errorHandler = new ErrorHandler($parameters['environment']);

        // Get routes and build request object
        $routes = json_decode(file_get_contents(__DIR__ . '/../Config/routes.json'), true);
        $router = new Router($routes);
        $request = $router->buildRequest();

        // Update dependencies placeholders
        $dependencies = file_get_contents(__DIR__ . '/../Config/dependencies.json');
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
        $placeholders = [self::ROUTED_CONTROLLER_PLACEHOLDER, self::ROUTED_ACTION_PLACEHOLDER];
        $values = ['@' . $request->getController() . '@', $request->getAction()];
        foreach ($parameters as $placeholder => $value) {
            $placeholders[] = '%' . $placeholder . '%';
            $values[] = $value;
        }
        $dependencies = str_replace($placeholders, $values, $dependencies);
        $dependencies = json_decode($dependencies, true);

        // Check classes to create
        $toCreate = [];
        $counter = 0;
        $this->analyseInjections($counter, $dependencies, $toCreate, self::APPLICATION_STARTING_POINT);

        // Build dependencies tree
        $this->inject($dependencies, $toCreate);
        if (!($dependencies[self::APPLICATION_STARTING_POINT]['object'] instanceof ApplicationMiddlewareInterface)) {
            throw new \Exception('Application middleware has to implement ' . ApplicationMiddlewareInterface::class);
        }
        $dependencies[self::APPLICATION_STARTING_POINT]['object']->process($request);
    }

    private function analyseInjections(int $counter, array $dependencies, array &$toCreate, string $name)
    {
        $counter++;
        if ($counter > 1000) {
            throw new \Exception('Too many dependencies or danger of infinite recurrence, reached counter ' . var_export($counter, true));
        }

        if (!in_array($name, $toCreate)) {
            $toCreate[] = $name;
        }

        foreach ($dependencies[$name]['inject'] as $injection) {
            if (is_string($injection) && strpos($injection, '@') === 0) {
                $subname = trim($injection, '@');
                $this->analyseInjections($counter, $dependencies, $toCreate, $subname);
            }
        }
    }

    private function inject(&$dependencies, $toCreate)
    {
        $index = count($toCreate);
        while ($index--) {
            foreach ($dependencies[$toCreate[$index]]['inject'] as &$injection) {
                if (is_string($injection) && strpos($injection, '@') === 0) {
                    $injection = $dependencies[trim($injection, '@')]['object'];
                }
            }

            if (empty($dependencies[$toCreate[$index]]['object'])) {
                $dependencies[$toCreate[$index]]['object'] = new $dependencies[$toCreate[$index]]['class'](
                    ...$dependencies[$toCreate[$index]]['inject']
                );
            }
        }
    }

    public static function buildParameters()
    {
        if (!file_exists(__DIR__ . '/../Config/parameters.json')) {
            $pattern = file_get_contents(__DIR__ . '/../Config/parameters.json.dist');
            //@TODO add reading and setting parameters if it is different than dist version
//            foreach ($pattern as $key => $value) {
// read inputed value and assign value
//            }
//            file_put_contents(__DIR__ . '/../Config/parameters.json', json_encode($pattern));
            var_dump(json_decode($pattern, true));
        }
    }
}
