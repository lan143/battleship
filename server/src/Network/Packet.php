<?php
namespace Battleship\Network;

use Battleship\Exceptions\InvalidArgumentException;
use Battleship\Network\Exceptions\PacketParseException;

class Packet
{
    private $opcode;
    private $data;

    public function __construct(array $options)
    {
        if (isset($options['json_data']))
        {
            $json_data = json_decode($options['json_data']);
            $jsonError = json_last_error();

            if ($jsonError === JSON_ERROR_NONE)
            {
                $this->opcode = $json_data->opcode;
                $this->data = $json_data->data;
            }
            else
            {
                $errorText = null;
                switch ($jsonError)
                {
                    case JSON_ERROR_DEPTH:
                        $errorText = 'Maximum stack depth exceeded';
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $errorText = 'Unexpected control character found';
                        break;
                    case JSON_ERROR_SYNTAX:
                        $errorText = 'Syntax error, malformed JSON';
                        break;
                }

                throw new PacketParseException($errorText);
            }
        }
        else if (isset($options['opcode']) && isset($options['data']))
        {
            $this->opcode = $options['opcode'];
            $this->data = $options['data'];
        }
        else
            throw new InvalidArgumentException();
    }

    public function getOpcode() : string
    {
        return $this->opcode;
    }

    public function setOpcode(string $opcode)
    {
        $this->opcode = $opcode;
    }

    public function getData() : \stdClass
    {
        return $this->data;
    }

    public function __toString() : string
    {
        $packet = [
            'opcode' => $this->opcode,
            'data' => $this->data,
        ];

        return json_encode($packet);
    }
}