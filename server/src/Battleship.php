<?php
namespace Battleship;

use Battleship\DI\Container;
use Battleship\Exceptions\InvalidConfigException;

/**
 * Class Battleship
 * @package Battleship
 *
 * @property Application $app
 */
class Battleship
{
    public static $app;

    public static $container;

    public static function createObject($type, array $params = [])
    {
        if (is_string($type)) {
            return static::$container->get($type, $params);
        } elseif (is_array($type) && isset($type['class'])) {
            $class = $type['class'];
            unset($type['class']);
            return static::$container->get($class, $params, $type);
        } elseif (is_callable($type, true)) {
            return static::$container->invoke($type, $params);
        } elseif (is_array($type)) {
            throw new InvalidConfigException('Object configuration must be an array containing a "class" element.');
        }
        throw new InvalidConfigException('Unsupported configuration type: ' . gettype($type));
    }

    public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }
        return $object;
    }
}

Battleship::$container = new Container();