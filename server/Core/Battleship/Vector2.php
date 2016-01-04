<?
class Vector2
{
    public $x;
    public $y;
    public $type;

    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
        $this->type = Field::TYPE_SHIP;
    }
}
?>