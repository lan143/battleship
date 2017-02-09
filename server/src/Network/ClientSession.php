<?php
namespace Battleship\Network;

use Battleship\Game\Game;
use Ratchet\ConnectionInterface;

class ClientSession
{
    private $conn;
    private $current_game;
    private $networld;

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

    public function setGame(Game $game) : void
    {
        $this->current_game = $game;
    }
}