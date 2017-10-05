<?php
namespace TinyApp\System;

use TinyApp\System\ErrorHandler;
use TinyApp\System\Router;

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
        $placeholders = [self::ROUTED_CONTROLLER_PLACEHOLDER, self::ROUTED_ACTION_PLACEHOLDER];
        $values = ['@' . $request->controller() . '@', $request->action()];
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
        $dependencies[self::APPLICATION_STARTING_POINT]['object']->process($request);
    }

    private function analyseInjections(int $counter, array $dependencies, array &$toCreate, string $name)
    {
        $counter++;
        if ($counter > 1000) {
            throw new \Exception('Too many dependencies or danger of infinite recurrence, reached counter ' . $counter);
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
}
