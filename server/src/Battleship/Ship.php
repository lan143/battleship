<?
class Ship
{
    private $field;
    public $ship_lenght;
    private $destroyed;
    
    public $cells;
    public $direction;

    public function __construct($field, $ship_lenght)
    {
        $this->field = $field;
        $this->ship_lenght = $ship_lenght;
        $this->destroyed = false;
        
        //echo "Start create ship. Lenght: ".$this->ship_lenght."\r\n";
        
        $this->GenerateShip();
    }
    
    public function IsDestroyed()
    {
        return $this->destroyed;
    }
    
    public function Hit($x, $y)
    {
        $destroyed = true;

        foreach ($this->cells as &$cell)
        {
            if ($cell->x == $x && $cell->y == $y)
                $cell->type = Field::TYPE_HIT;
            else if ($cell->type != Field::TYPE_HIT)
                $destroyed = false;
        }

        $this->destroyed = $destroyed;

        $_cells = array();
        if ($this->destroyed)
        {
            foreach ($this->cells as $_cell)
            {
                $_cells[] = array('x' => $_cell->x, 'y' => $_cell->y);
            }
        }
        
        return array('result' => 1, 'destroyed' => $destroyed, 'ship' => $_cells);
    }

    private function GenerateShip()
    {
        $count = 0;
            
        $arFreeCells = $this->field->GetFreeCells();

        while (true)
        {
            $count++;
            
            $cell_key = null;
            $cell = null;

            if (count($arFreeCells) > 0)
            {
                $cell_key = rand(0, count($arFreeCells) - 1);
                $cell = $arFreeCells[$cell_key];
            }
            else
            {
                echo "fail when spawn ship.\r\n";
                return false;
            }

            $x = $cell['x'];
            $y = $cell['y'];

            $error = false;
            $directions = array();
            
            // Генерируем случайные направления
            while (true)
            {
                $dir = rand(0, 1);
                $fould = false;

                foreach ($directions as $direction)
                {
                    if ($direction == $dir)
                    {
                        $fould = true;
                        break;
                    }
                }
                
                if (count($directions) == 2)
                    break;
                
                if (!$fould)
                {
                    $directions[] = $dir;
                }
            }
            
            $end_point_x = 0;
            $end_point_y = 0;
            
            // Проверяем все возможные направления корабля для спавна
            foreach ($directions as $i)
            {
                $end_cell = $this->GetEndCell($x, $y, $i);
                
                $end_point_x = $end_cell['x'];
                $end_point_y = $end_cell['y'];

                if ($end_point_x > 9 || $end_point_x < 0 || $end_point_y > 9 || $end_point_y < 0 || !$this->CheckShipPosition($x, $y, $end_cell['x'], $end_cell['y']))
                {
                    $error = true;
                }
                else
                {
                    $this->direction = $i;
                    $error = false;
                    break;
                }
            }
            
            if ($error)
            {
                unset($arFreeCells[$cell_key]);
                $arFreeCells_tmp = array();
                foreach ($arFreeCells as $arFreeCell)
                {
                    $arFreeCells_tmp[] = $arFreeCell;
                }
                $arFreeCells = $arFreeCells_tmp;
                continue;
            }

            $this->ReleaseShip($x, $y, $end_point_x, $end_point_y);

            //echo "direction: ".$direction.". count: ".$count."\r\n";
            
            return;
        }
    }
    
    private function GetEndCell($start_x, $start_y, $direction)
    {
        $end_point_x = $start_x;
        $end_point_y = $start_y;
        
        //echo "GetEndCell: lenght: ".$this->ship_lenght."\r\n";

        // выбираем направление расположения коробля
        switch ($direction)
        {
            case 0:
                $end_point_x += $this->ship_lenght - 1;
                break;
            case 1:
                $end_point_y += $this->ship_lenght - 1;
                break;
        }

        return array('x' => $end_point_x, 'y' => $end_point_y);
    }
    
    private function CheckShipPosition($start_x, $start_y, $end_x, $end_y)
    {
        $start_pos_x = $start_x > $end_x ? $end_x : ($start_x < $end_x ? $start_x : $start_x);
        $end_pos_x   = $start_x > $end_x ? $start_x : ($start_x < $end_x ? $end_x : $start_x);
        
        $start_pos_y = $start_y > $end_y ? $end_y : ($start_y < $end_y ? $start_y : $start_y);
        $end_pos_y   = $start_y > $end_y ? $start_y : ($start_y < $end_y ? $end_y : $start_y);

        for ($i = $start_pos_x; $i <= $end_pos_x; $i++)
        {
            for ($j = $start_pos_y; $j <= $end_pos_y; $j++)
            {
                if (!$this->field->CheckCells($i, $j))
                    return false;
            }
        }

        return true;
    }

    private function ReleaseShip($start_x, $start_y, $end_x, $end_y)
    {
        $start_pos_x = $start_x > $end_x ? $end_x : ($start_x < $end_x ? $start_x : $start_x);
        $end_pos_x   = $start_x > $end_x ? $start_x : ($start_x < $end_x ? $end_x : $start_x);
        
        $start_pos_y = $start_y > $end_y ? $end_y : ($start_y < $end_y ? $start_y : $start_y);
        $end_pos_y   = $start_y > $end_y ? $start_y : ($start_y < $end_y ? $end_y : $start_y);
        
        //echo "Release ship. x1: ".$start_pos_x.", y1: ".$start_pos_y.", x2: ".$end_pos_x.", y2: ".$end_pos_y.".\r\n";

        $this->field->PlaceShip($start_pos_x, $start_pos_y, $end_pos_x, $end_pos_y, $this);
        
        for ($x = $start_pos_x; $x <= $end_pos_x; $x++)
        {
            for ($y = $start_pos_y; $y <= $end_pos_y; $y++)
            {
                $this->cells[] = new Vector2($x, $y);
            }
        }
    }
}
?>