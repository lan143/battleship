<?php
namespace app\game\network;

use app\game\Field;
use app\game\Game;
use Ratchet\ConnectionInterface;

class ClientSession
{
    private $conn;
    private $current_game;
    private $networld;
    private $field;

    public function __construct(ConnectionInterface $conn, Networld $networld)
    {
        $this->conn = $conn;
        $this->current_game = NULL;
        $this->networld = $networld;
    }

    function __destruct()
    {
        if ($this->current_game)
            $this->current_game->playerLeave($this);
    }

    /**
     * @return null|Game
     */
    public function getGame()
    {
        return $this->current_game;
    }

    /**
     * @param Game $game
     */
    public function setGame(Game $game)
    {
        $this->current_game = $game;
    }

    public function removeGame()
    {
        $this->current_game = null;
    }

    public function getConnection() : ConnectionInterface
    {
        return $this->conn;
    }

    public function generateField()
    {
        $this->field = new Field($this);
    }

    /**
     * @return null|Field
     */
    public function getField()
    {
        return $this->field;
    }

    public function sendPacket(Packet $packet)
    {
        $this->networld->sendPacket($packet, $this);
    }
}