<?php

if (file_exists(__DIR__.'/../../../autoload.php')) {
    require __DIR__.'/../../../autoload.php';
}
else {
    require __DIR__.'/../vendor/autoload.php';
}

use Infira\Console\Bin;
use Symfony\Component\Console\Application;

Bin::init();
Bin::run('infira-openapi-model-generator', function (Application $app) {
    $app->add(new \Infira\omg\OmgCommand());
});