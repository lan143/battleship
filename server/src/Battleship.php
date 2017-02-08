<?php
namespace Battleship;

class Battleship
{
    private static $_app;

    public function __get(string $name)
    {
        if ($name == 'app')
            return self::$_app;
        else
            return null;
    }

    public function __set(string $name, $value): void
    {
        if (self::$_app === null)
            self::$_app = $value;
        else
            throw new \Exception('Read only');
    }
}