<?php
echo 'Checking parameters.json' . PHP_EOL;
$inputFile = __DIR__ . '/../src/Config/parameters.json.dist';
$outputFile = __DIR__ . '/../src/Config/parameters.json';

if (!file_exists($inputFile)) {
    echo 'parameters.json.dist is required, create it first in ' . $inputFile;
    exit;
}

$pattern = json_decode(file_get_contents($inputFile), true);
if (!file_exists($outputFile)) {
    echo 'Please specify following values:' . PHP_EOL;
    $parameters = [];
    foreach ($pattern as $key => $value) {
        $parameter = readline($key . ': ');
        $parameters[$key] = $parameter;
    }
    file_put_contents($outputFile, json_encode($parameters, JSON_PRETTY_PRINT));
    echo 'parameters.json -> created' . PHP_EOL;
    exit;
}

$parameters = json_decode(file_get_contents($outputFile), true);
$missingKeys = array_diff(array_keys($pattern), array_keys($parameters));
if (!empty($missingKeys)) {
    $leftover = [];
    foreach ($missingKeys as $key) {
        $parameter = readline($key . ': ');
        $leftover[$key] = $parameter;
    }
    $parameters += $leftover;
    file_put_contents($outputFile, json_encode($parameters, JSON_PRETTY_PRINT));
    echo 'parameters.json -> updated' . PHP_EOL;
    exit;
}

echo 'parameters.json -> exists' . PHP_EOL;
