<?php
namespace Battleship\Game;

class Vector2
{
    public $x;
    public $y;
    public $type;

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
        $this->type = Field::TYPE_SHIP;
    }
}
?>