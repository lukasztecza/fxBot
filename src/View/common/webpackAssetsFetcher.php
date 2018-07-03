<?php declare(strict_types=1);
function fetchWebpackAsset(string $type) : string
{
    $webpackAssetsFile = __DIR__ . '/webpackAssets.html';
    if (!file_exists($webpackAssetsFile)) {
        return '';
    }

    $contents = file_get_contents($webpackAssetsFile);
    $contents = explode(PHP_EOL, $contents);

    $return = '';
    switch ($type) {
        case 'css':
            foreach ($contents as $content) {
                if (strpos($content, '/assets/css') !== false) {
                    $return .= str_replace(
                        ['<head>', '</head>', '../../../public/.'],
                        [''],
                        $content
                    );
                }
            }
            return $return;
        case 'js':
            foreach ($contents as $content) {
                if (strpos($content, '/assets/js') !== false) {
                    $return .= str_replace(
                        ['<body>', '</body>', '../../../public/.'],
                        [''],
                        $content
                    );
                }
            }
            return $return;
    }
}
