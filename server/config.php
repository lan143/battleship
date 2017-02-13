<?php

return [
    'bootstrap' => ['server'],
    'components' => [
        'logger' => [
            'class' => 'Battleship\Components\Logger',
            'handlers' => [
                [
                    'stream' => "php://stdout",
                    'level' => \Monolog\Logger::DEBUG
                ],
            ]
        ],
        'server' => [
            'class' => 'Battleship\Components\GameServer',
            'listen_host' => '0.0.0.0',
            'listen_port' => 8080,
        ],
    ],
];

?>
