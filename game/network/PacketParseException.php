<?php
namespace app\game\network;

use yii\base\Exception;

class PacketParseException extends Exception
{
    public function getName()
    {
        return "Packet parse error";
    }
}