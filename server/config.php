<?php

return [
    'websocket' => [
        'listen_host' => '0.0.0.0',
        'listen_port' => 8080,
    ],
    'logs' => [
        [
            'stream' => "php://stdout",
            'level' => \Monolog\Logger::DEBUG
        ]
    ]
];

?>
