<?php
namespace Battleship;

use Battleship\DI\ServiceLocator;

/**
 * Class Application
 * @package Battleship
 *
 * @property array $config
 * @property Logger $logger
 */
class Application extends ServiceLocator
{
    public $config;

    public function __construct($config)
    {
        $this->config = $config;

        Battleship::$app = $this;
    }

    public function run()
    {
        $this->setComponents($this->config['components']);

        if (isset($this->config['bootstrap']) && is_array($this->config['bootstrap']))
        {
            foreach ($this->config['bootstrap'] as $id)
            {
                $this->get($id);
            }
        }
    }
}