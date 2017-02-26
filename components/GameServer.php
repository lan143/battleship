<?php
namespace app\components;

use Yii;
use app\game\network\Networld;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\Server;
use yii\base\Component;

class GameServer extends Component
{
    public $listen_host;
    public $listen_port;

    public function init()
    {
        Yii::$app->logger->info("Game server.");
        Yii::$app->logger->info("Version 2.0.");
        Yii::$app->logger->info("");

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

        Yii::$app->set('queue', [
            'class' => 'app\game\QueueMgr'
        ]);

        Yii::$app->logger->info("Server running...");

        $loop->run();
    }
}