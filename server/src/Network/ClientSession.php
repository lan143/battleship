<?php
namespace Battleship\Network;

use Battleship\Game\Field;
use Battleship\Game\Game;
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

    public function getGame() : Game
    {
        return $this->current_game;
    }

    public function setGame(Game $game)
    {
        $this->current_game = $game;
    }

    public function getConnection() : ConnectionInterface
    {
        return $this->conn;
    }

    public function generateField()
    {
        $this->field = new Field($this);
    }

    public function getField() : Field
    {
        return $this->field;
    }

    public function sendPacket(Packet $packet)
    {
        $this->networld->sendPacket($packet, $this);
    }
}