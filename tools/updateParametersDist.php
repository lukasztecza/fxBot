<?php
echo 'Updating parameters.json.dist' . PHP_EOL;
$inputFile = __DIR__ . '/../src/Config/parameters.json';
$outputFile = __DIR__ . '/../src/Config/parameters.json.dist';

if (!file_exists($inputFile)) {
    echo 'parameters.json is required, create it first in ' . $inputFile;
    exit;
}

if (file_exists($outputFile)) {
    $confirmation = readline('Are you sure you want to overwrite existing parameters.json.dist file? (y/n)');
    if ($confirmation !== 'y') {
        echo 'Quitting' . PHP_EOL;
        exit;
    }
}

$pattern = json_decode(file_get_contents($inputFile), true);

$parameters = [];
foreach ($pattern as $key => $value) {
    $parameters[$key] = '';
}
file_put_contents($outputFile, json_encode($parameters, JSON_PRETTY_PRINT));
echo 'parameters.json.dist -> created' . PHP_EOL;
