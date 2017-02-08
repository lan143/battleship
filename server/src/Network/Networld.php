<?php
namespace Battleship\Network;

use Battleship\Battleship;

class Networld
{
    protected $sessions;

    public function __construct()
    {
        $this->sessions = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $session = new ClientSession($conn);
        $session->word_session = $this;
        $this->sessions->attach($session);

        Battleship::$app->logger->debug("New connection! ({$conn->resourceId})");
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        Battleship::$app->logger->debug('Connection '.$from->resourceId.' sending message "'.$msg.'"');

        $session = $this->findSession($from);
        if ($session !== null)
        {
            $session->handle($msg);
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