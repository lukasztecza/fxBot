<?php
namespace TinyApp\System;

class Project
{
    public function run()
    {
        // Get project variables and set error handler
        $parameters = json_decode(file_get_contents(__DIR__ . '/../Config/parameters.json'), true);
        $project = json_decode(file_get_contents(__DIR__ . '/../Config/project.json'), true);
        $project = array_merge($project, $parameters);
        $errorHandler = new $project['error_handling_class']($project['environment']);

        // Get routing and build request object
        $routing = json_decode(file_get_contents(__DIR__ . '/../Config/routing.json'), true);
        $router = new $project['routing_class']($routing);
        $request = $router->buildRequest($project['system_request_class']);

        // Update dependency placeholders
        $dependency = file_get_contents(__DIR__ . '/../Config/dependency.json');
        $dependency = str_replace(
            [$project['dependency_controller_placeholder'], $project['dependency_action_placeholder']],
            ['@' . $request->controller(), $request->action()],
            $dependency
        );
        $dependency = json_decode($dependency, true);

        // Check classes to create
        $toCreate = [];
        $counter = 0;
        $this->analyseInjections($counter, $dependency, $toCreate, substr($project['application_starting_point'], 1));

        // Build dependency tree
        $this->inject($dependency, $toCreate);
        var_dump($dependency['user_controller']);exit;

        //var_dump([$parameters, $project, $routing, $dependency]);
    }

    private function analyseInjections(int $counter, array $dependency, array &$toCreate, string $name)
    {
        $counter++;
        if ($counter > 1000) {
            throw new \Exception('Too many dependencies or danger of infinite recurrence, reached counter ' . $counter);
        }

        if (!in_array($name, $toCreate)) {
            $toCreate[] = $name;
        }

        foreach ($dependency[$name]['inject'] as $injection) {
            if (is_string($injection) && strpos($injection, '@') === 0) {
                $subname = substr($injection, 1);
                $this->analyseInjections($counter, $dependency, $toCreate, $subname);
            }
        }
    }

    private function inject(&$dependency, $toCreate)
    {
        $index = count($toCreate);
        while ($index--) {
            foreach ($dependency[$toCreate[$index]]['inject'] as &$injection) {
                if (is_string($injection) && strpos($injection, '@') === 0) {
                    $injection = $dependency[substr($injection, 1)]['object'];
                }
            }

            if (empty($dependency[$toCreate[$index]]['object'])) {
                $dependency[$toCreate[$index]]['object'] = new $dependency[$toCreate[$index]]['class'](...$dependency[$toCreate[$index]]['inject']);
            }
        }
    }
}
