<?php
use Battleship\Application;

require __DIR__ . '/vendor/autoload.php';

$config = (require __DIR__ . '/config.php');

(new Application($config))->run();