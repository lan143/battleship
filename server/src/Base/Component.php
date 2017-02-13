<?php
namespace Battleship\Base;

use Battleship\Battleship;

class Component extends Object
{
    public function __construct($config = [])
    {
        if (!empty($config))
        {
            Battleship::configure($this, $config);
        }

        $this->init();
    }

    public function init()
    {
    }
}