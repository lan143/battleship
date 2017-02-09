<?php
namespace Battleship\Game;

use Battleship\Network\ClientSession;
use Battleship\Network\Packet;

class Player
{
    const PLAYER_ONE = 1;
    const PLAYER_TWO = 2;

    public $id;
    public $session;
    public $field;

    public function __construct(int $id, ClientSession $session, Field $field)
    {
        $this->id = $id;
        $this->session = $session;
        $this->field = $field;
    }

    public function sendPacket(Packet $packet)
    {
        $this->session->sendPacket($packet);
    }
}