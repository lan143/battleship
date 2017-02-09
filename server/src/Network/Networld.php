<?php
namespace Battleship\Network;

use Battleship\Battleship;
use Ratchet\ConnectionInterface;

class Networld
{
    protected $sessions;

    public function __construct()
    {
        $this->sessions = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $session = new ClientSession($conn, $this);
        $this->sessions->attach($session);

        Battleship::$app->logger->debug("New connection! ({$conn->resourceId})");
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        Battleship::$app->logger->debug('Connection '.$from->resourceId.' sending message "'.$msg.'"');

        $session = $this->findSession($from);
        if ($session !== null)
        {
            $packet = json_decode($msg);
            $handlerName = $packet->{'opcode'};

            if (method_exists('PacketHandler', $handlerName))
            {
                PacketHandler::$handlerName($packet->{'data'}, $session);
            }
            else
            {
                Battleship::$app->logger->error("Got unknown packet: ".$handlerName);

                $packet = array(
                    'opcode' => 'smsg_error',
                    'data' => array(
                        'message' => 'received unknown packet',
                    )
                );

                $from->send(json_encode($packet));
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $session = $this->findSession($conn);
        if ($session !== null)
        {
            $this->sessions->detach($session);
        }

        Battleship::$app->logger->debug("Connection {$conn->resourceId} has disconnected");
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Battleship::$app->logger->error("An error has occurred: {$e->getMessage()}");
        $conn->close();
    }

    private function findSession($conn)
    {
        foreach ($this->sessions as $session)
        {
            if ($session->conn->resourceId == $conn->resourceId)
                return $session;
        }

        return null;
    }
}