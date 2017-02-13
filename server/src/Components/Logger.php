<?php
namespace Battleship\Components;

use Battleship\Base\Component;
use Monolog\Handler\StreamHandler;

class Logger extends Component
{
    private $logger;

    public function init()
    {
        $this->logger = new \Monolog\Logger('battleship');

        foreach ($this->handlers as $handler)
        {
            $this->logger->pushHandler(new StreamHandler($handler['stream'], $handler['level']));
        }

        $this->info("Log inited.");
    }

    public function info($message, array $context = array())
    {
        return $this->logger->info($message, $context);
    }

    public function debug($message, array $context = array())
    {
        return $this->logger->debug($message, $context);
    }

    public function notice($message, array $context = array())
    {
        return $this->logger->notice($message, $context);
    }

    public function warn($message, array $context = array())
    {
        return $this->logger->warn($message, $context);
    }

    public function error($message, array $context = array())
    {
        return $this->logger->error($message, $context);
    }

    public function crit($message, array $context = array())
    {
        return $this->logger->crit($message, $context);
    }

    public function alert($message, array $context = array())
    {
        return $this->logger->alert($message, $context);
    }
}