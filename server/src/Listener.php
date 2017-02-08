<?php

include_once("Logger.php");

abstract class Listener
{
    private $base = NULL;
    private $event = NULL;
    private $id = 0;
    protected $connections = array();
    private $buffers = array();

    public function __construct($address, $port)
    {
        $this->base = event_base_new();
        $this->event = event_new();

        Logger::getInstance()->outString("Create new socket for ".$address.":".$port);
        
        $errno = 0;
        $errstr = '';
        $socket = stream_socket_server('tcp://'.$address.':'.$port, $errno, $errstr);
        stream_set_blocking($socket, 0);
        event_set($this->event, $socket, EV_READ | EV_PERSIST, array($this, 'accept'), $this->base);
        event_base_set($this->event, $this->base);
        event_add($this->event);
        $this->onLoad();
    }

    public function accept($socket, $flag, $base)
    {
        Logger::getInstance()->outDebug("Try to accept new connection.");

        $this->id++;
        $connection = stream_socket_accept($socket);
        stream_set_blocking($connection, 0);
        $buffer = event_buffer_new($connection, array($this, 'onRead'), NULL, array($this, 'onError'), $this->id);
        event_buffer_base_set($buffer, $this->base);
        event_buffer_timeout_set($buffer, 180, 180);
        event_buffer_watermark_set($buffer, EV_READ, 0, 0xffffff);
        event_buffer_priority_set($buffer, 10);
        event_buffer_enable($buffer, EV_READ | EV_PERSIST);
        $this->connections[$this->id] = $connection;
        $this->buffers[$this->id] = $buffer;
    }

    abstract protected function onLoad();

    private function onError($buffer, $error, $id)
    {
        if ($error != 17 && $error != 65 && $error != 33)
            Logger::getInstance()->outError("Error in libevent. Error: ".$error.". Connection id: ".$id);

        $this->Close($id);
    }

    abstract protected function onClose($id);

    abstract protected function onData($data, $id);

    protected function Close($id)
    {
        event_buffer_disable($this->buffers[$id], EV_READ | EV_WRITE);
        event_buffer_free($this->buffers[$id]);
        fclose($this->connections[$id]);
        unset($this->buffers[$id], $this->connections[$id]);
        $this->onClose($id);
    }

    protected function SendData($data, $id)
    {
        //Logger::getInstance()->outDebug("Send data to client #".$id.": ".$data);
        $result = event_buffer_write($this->buffers[$id], $data, strlen($data));

        if (!$result)
            Logger::getInstance()->outError("Error while send data to client. Connection id: ".$id);
    }

    private function onRead($buffer, $id)
    {
        while ($data = @event_buffer_read($buffer, 2048))
        {
            //Logger::getInstance()->outDebug("Received data from client #".$id.": ".$data);
            $this->onData($data, $id);
        }
    }

    public function DoWork()
    {
        Logger::getInstance()->outString("Start listening...");

        event_base_loop($this->base);
    }
}

?>