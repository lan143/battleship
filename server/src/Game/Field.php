<?php
namespace Battleship\Game;

class Field
{
    private $field;
    private $ships;
    
    private $context;
    private $id;
    
    const TYPE_EMPTY = 0;
    const TYPE_SHIP  = 1;
    const TYPE_LOSE  = 2;
    const TYPE_HIT   = 3;

    public function __construct($context, $id)
    {
        $this->field = array();
        $this->ships = array();
        
        $this->context = $context;
        $this->id = $id;

        for ($x = 0; $x < 10; $x++)
        {
            for ($y = 0; $y < 10; $y++)
            {
                $this->field[$x][$y] = array('type' => self::TYPE_EMPTY, 'ship' => null);
            }
        }

        $this->GenerateShips();
        $this->Send();
    }
    
    public function shot(int $x, int $y)
    {
        if ($this->field[$x][$y]['type'] == self::TYPE_EMPTY)
        {
            $this->field[$x][$y]['type'] = self::TYPE_LOSE;
            return array('result' => 0);
        }
        else if ($this->field[$x][$y]['type'] == self::TYPE_SHIP)
        {
            $this->field[$x][$y]['type'] = self::TYPE_HIT;
            $ship_info = $this->field[$x][$y]['ship']->hit($x, $y);
            
            $ship_info['end_game'] = true;
            foreach ($this->ships as $ship)
            {
                if (!$ship->IsDestroyed())
                    $ship_info['end_game'] = false;
            }
            
            return $ship_info;
        }
        else
        {
            return -1;
        }
    }

    private function generateShips()
    {
        $this->ships[] = new Ship($this, 4);

        for ($i = 0; $i < 2; $i++)
            $this->ships[] = new Ship($this, 3);

        for ($i = 0; $i < 3; $i++)
            $this->ships[] = new Ship($this, 2);

        for ($i = 0; $i < 4; $i++)
            $this->ships[] = new Ship($this, 1);
    }
    
    public function placeShip($start_pos_x, $start_pos_y, $end_pos_x, $end_pos_y, $ship) : void
    {
        for ($i = $start_pos_x; $i <= $end_pos_x; $i++)
        {
            for ($j = $start_pos_y; $j <= $end_pos_y; $j++)
            {
                $this->field[$i][$j]['type'] = self::TYPE_SHIP;
                $this->field[$i][$j]['ship'] = $ship;
            }
        }
    }

    public function checkCells(int $x, int $y) : bool
    {
        if ($x > 9 || $x < 0 || $y > 9 || $y < 0)
            return false;
        
        if (!isset($this->field[$x][$y]))
        {
            return false;
        }
        
        if ($this->field[$x][$y]['type'] == self::TYPE_SHIP)
            return false;

        for ($_x = $x - 1; $_x < $x + 2; $_x++)
        {
            for ($_y = $y - 1; $_y < $y + 2; $_y++)
            {
                if ($_x > 9 || $_x < 0 || $_y > 9 || $_y < 0)
                    continue;

                if ($this->field[$_x][$_y]['type'] == self::TYPE_SHIP)
                    return false;
            }
        }
        
        return true;
    }

    public function getFreeCells()
    {
        $arFreeCells = array();

        for ($i = 0; $i < 10; $i++)
        {
            for ($j = 0; $j < 10; $j++)
            {
                if ($this->checkCells($i, $j))
                {
                    $arFreeCells[] = array('x' => $i, 'y' => $j);
                }
            }
        }
        
        return $arFreeCells;
    }
    
    private function send()
    {
        $field = array();
        foreach ($this->field as $x => $row)
        {
            foreach ($row as $y => $cell)
            {
                $start_ship = $this->field[$x][$y]['ship'] ? ($this->field[$x][$y]['ship']->cells[0]->x == $x && $this->field[$x][$y]['ship']->cells[0]->y == $y) : false;
                $ship_direction = $this->field[$x][$y]['ship'] ? $this->field[$x][$y]['ship']->direction : null;
                $ship_lenght = $this->field[$x][$y]['ship'] ? $this->field[$x][$y]['ship']->ship_lenght : null;

                $field[] = array(
                    'x'              => $x,
                    'y'              => $y,
                    'type'           => $cell['type'],
                    'start_ship'     => $start_ship,
                    'ship_direction' => $ship_direction,
                    'ship_lenght'    => $ship_lenght
                );
            }
        }
        
        $packet = array(
            'opcode' => 'smsg_field',
            'data' => array(
                'field' => $field
            )
        );
        
        $this->context->send($packet, $this->id);
    }
    
    public function toString()
    {
        for ($i = 0; $i < 10; $i++)
        {
            for ($j = 0; $j < 10; $j++)
            {
                echo $this->field[$i][$j]['type'].' ';
            }
            echo "\r\n";
        }
    }
}
?>