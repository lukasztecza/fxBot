<?php
echo 'Checking parameters.json' . PHP_EOL;

if (1 || !file_exists(__DIR__ . '/../src/Config/parameters.json')) {
    if (!file_exists(__DIR__ . '/../src/Config/parameters.json')) {
        echo 'parameters.json.dist required create it first in ' . __DIR__ . '/../src/Config/parameters.json';
        return;
    }
    $pattern = json_decode(file_get_contents(__DIR__ . '/../src/Config/parameters.json.dist'), true);
    echo 'Please specify following values:' . PHP_EOL;
    $parameters = [];
    foreach ($pattern as $key => $value) {
        $parameter = readline($key . ': ');
        $parameters[$key] = $parameter;
    }
    file_put_contents(__DIR__ . '/../src/Config/parameters.json', json_encode($parameters));
}
echo 'parameters.json -> ok' . PHP_EOL;
