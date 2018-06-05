<?php
define('APP_ROOT_DIR', str_replace('/public', '', __DIR__));
include(APP_ROOT_DIR . '/vendor/autoload.php');
(new LightApp\Model\System\Project())->run();
