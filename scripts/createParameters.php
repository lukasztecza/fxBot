<?php
echo 'Checking parameters.json' . PHP_EOL;

if (!file_exists(__DIR__ . '/../src/Config/parameters.json.dist')) {
    echo 'parameters.json.dist is required, create it first in ' . __DIR__ . '/../src/Config/parameters.json.dist';
    exit;
}

$pattern = json_decode(file_get_contents(__DIR__ . '/../src/Config/parameters.json.dist'), true);

if (!file_exists(__DIR__ . '/../src/Config/parameters.json')) {
    echo 'Please specify following values:' . PHP_EOL;
    $parameters = [];
    foreach ($pattern as $key => $value) {
        $parameter = readline($key . ': ');
        $parameters[$key] = $parameter;
    }
    file_put_contents(__DIR__ . '/../src/Config/parameters.json', json_encode($parameters));
    echo 'parameters.json -> created' . PHP_EOL;
    exit;
}

$parameters = json_decode(file_get_contents(__DIR__ . '/../src/Config/parameters.json'), true);
$missingKeys = array_diff(array_keys($pattern), array_keys($parameters));
if (!empty($missingKeys)) {
    $leftover = [];
    foreach ($missingKeys as $key) {
        $parameter = readline($key . ': ');
        $leftover[$key] = $parameter;
    }
    $parameters += $leftover;
    file_put_contents(__DIR__ . '/../src/Config/parameters.json', json_encode($parameters));
    echo 'parameters.json -> updated' . PHP_EOL;
    exit;
}

echo 'parameters.json -> exists' . PHP_EOL;
