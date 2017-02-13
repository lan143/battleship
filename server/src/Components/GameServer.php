<?php
namespace Battleship\Components;

use Battleship\Base\Component;
use Battleship\Battleship;
use Battleship\Network\Networld;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\Server;

class GameServer extends Component
{
    public function init()
    {
        Battleship::$app->logger->info("Game server.");
        Battleship::$app->logger->info("Version 2.0.");
        Battleship::$app->logger->info("");

        $loop = Factory::create();

        $webSock = new Server($loop);
        $webSock->listen($this->listen_port, $this->listen_host);

        $networld = new Networld();

        $server = new IoServer(
            new HttpServer(
                new WsServer(
                    $networld
                )
            ),
            $webSock
        );

        Battleship::$app->set('queue', [
            'class' => 'Battleship\Game\QueueMgr'
        ]);

        Battleship::$app->logger->info("Server running...");

        $loop->run();
    }
}