<?php
namespace Battleship;

use Battleship\Network\Networld;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\Server;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Application
{
    public $config;
    public $logger;

    public function __construct($config)
    {
        $this->config = $config;

        Battleship::$app = $this;
    }

    public function run()
    {
        $this->logger = new Logger('battleship');

        foreach ($this->config['logs'] as $log)
        {
            $this->logger->pushHandler(new StreamHandler($log['stream'], $log['level']));
        }

        $this->logger->info("Battleship server.");
        $this->logger->info("Version 2.0.");
        $this->logger->info("");

        $loop = Factory::create();

        $webSock = new Server($loop);
        $webSock->listen($this->config['websocket']['listen_port'], $this->config['websocket']['listen_host']);

        $networld = new Networld();

        $server = new IoServer(
            new HttpServer(
                new WsServer(
                    $networld
                )
            ),
            $webSock
        );

        $this->logger->info("Server running...");

        $loop->run();
    }
}