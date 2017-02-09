<?php
namespace Battleship\Network;

use Battleship\Battleship;
use Battleship\Game\QueueMgr;
use Battleship\Network\Exceptions\PacketParseException;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Networld implements MessageComponentInterface
{
    protected $sessions;

    public function __construct()
    {
        $this->sessions = new \SplObjectStorage;
    }

    /**
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $session = new ClientSession($conn, $this);
        $this->sessions->attach($session);

        Battleship::$app->logger->debug("New connection! ({$conn->resourceId})");
    }

    /**
     * @param ConnectionInterface $from
     * @param string $msg
     */
    public function onMessage(ConnectionInterface $from, $msg)
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

            if (method_exists('\Battleship\Network\PacketHandler', $handlerName))
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

    /**
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn)
    {
        $session = $this->findSession($conn);
        if ($session !== null)
        {
            $game = $session->getGame();
            if ($game)
                $game->playerLeave($session);

            QueueMgr::getInstance()->leaveQueue($session);

            $this->sessions->detach($session);
        }

        Battleship::$app->logger->debug("Connection {$conn->resourceId} has disconnected");
    }

    /**
     * @param ConnectionInterface $conn
     * @param \Exception $e
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Battleship::$app->logger->error("An error has occurred: {$e->getMessage()}");
        $conn->close();
    }

    /**
     * @param ConnectionInterface $conn
     * @return mixed|null|ClientSession
     */
    private function findSession(ConnectionInterface $conn)
    {
        foreach ($this->sessions as $session)
        {
            if ($session->getConnection()->resourceId == $conn->resourceId)
                return $session;
        }

        return null;
    }

    /**
     * @param Packet $packet
     * @param ClientSession $session
     */
    public function sendPacket(Packet $packet, ClientSession $session)
    {
        $session->getConnection()->send((string)$packet);
    }
}