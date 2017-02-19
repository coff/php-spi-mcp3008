#!/usr/bin/php
<?php

namespace Coff\Examples;

include (__DIR__ . '/../vendor/autoload.php');

use Coff\Mcp3008\Mcp3008DataSource;

$s = new Mcp3008DataSource();
$s->setBusNumber(0);
$s->setCableSelect(0);
$s->setSpeed(100000);
$s->init();

while (true) {

    for ($ch=1;$ch<=8;$ch++) {
        echo sprintf("%8.2f", $s
                    ->setChannel($ch)
                    ->update()
                    ->getValue()/1024*5) . "v|";
        usleep(100000);
    }

    echo "\r";
    usleep(100000);
}
