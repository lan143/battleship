<?php
namespace Battleship\Game;

class Player
{
    const PLAYER_ONE = 1;
    const PLAYER_TWO = 2;

    public $id;
    public $session;
    public $field;

    public function __construct($id, $session, $field)
    {
        $this->id = $id;
        $this->session = $session;
        $this->field = $field;
    }
}