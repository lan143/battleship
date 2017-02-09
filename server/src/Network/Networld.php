<?php
namespace Battleship\Network;

use Battleship\Battleship;
use Battleship\Network\Exceptions\PacketParseException;
use Ratchet\ConnectionInterface;

class Networld
{
    protected $sessions;

    public function __construct()
    {
        $this->sessions = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) : void
    {
        $session = new ClientSession($conn, $this);
        $this->sessions->attach($session);

        Battleship::$app->logger->debug("New connection! ({$conn->resourceId})");
    }

    public function onMessage(ConnectionInterface $from, $msg) : void
    {
        Battleship::$app->logger->debug('Connection '.$from->resourceId.' sending message "'.$msg.'"');

        $session = $this->findSession($from);
        if ($session !== null)
        {
            try {
                $packet = new Packet([
                    'json_data' => $msg
                ]);
            } catch (PacketParseException $e) {
                Battleship::$app->logger->error("Failed parse packet. Error: ".$e->getMessage().". Data: {$msg}");
            }

            $handlerName = $packet->getOpcode();

            if (method_exists('PacketHandler', $handlerName))
            {
                PacketHandler::$handlerName($packet->getData(), $session);
            }
            else
            {
                Battleship::$app->logger->error("Got unknown packet: ".$handlerName);

                $packet = new Packet([
                    'opcode' => 'smsg_error',
                    'data' => [
                        'message' => 'received unknown packet',
                    ]
                ]);

                $this->sendPacket($packet, $session);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) : void
    {
        $session = $this->findSession($conn);
        if ($session !== null)
        {
            $this->sessions->detach($session);
        }

        Battleship::$app->logger->debug("Connection {$conn->resourceId} has disconnected");
    }

    public function onError(ConnectionInterface $conn, \Exception $e) : void
    {
        Battleship::$app->logger->error("An error has occurred: {$e->getMessage()}");
        $conn->close();
    }

    private function findSession($conn) : ClientSession
    {
        foreach ($this->sessions as $session)
        {
            if ($session->conn->resourceId == $conn->resourceId)
                return $session;
        }

        return null;
    }

    public function sendPacket(Packet $packet, ClientSession $session) : void
    {
        $session->getConnection()->send((string)$packet);
    }
}