<?php

function __autoload($class_name) 
{
    //if (file_exists($class_name.".php"))
        include $class_name . '.php';
}

class Websocket extends Listener
{
    private $usermgr;
    
    public function GetUserMgr()
    {
        return $this->usermgr;
    }

    protected function onLoad()
    {
        $this->usermgr = new UserMgr();
    }

    protected function onData($data, $id)
    {
        global $arConfig;
        
        if (!$this->usermgr->GetUser($id))
            $this->usermgr->AddUser($id);

        if (!$this->usermgr->GetUser($id)->IsWebsocketInited())
        {
            $headers = HttpUtils::ParseHeaders($data);
            $hash = base64_encode(pack('H*', sha1($headers['Sec-WebSocket-Key'].'258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

            $answer = "HTTP/1.1 101 Switching Protocols\r\n"
            ."Upgrade: websocket\r\n"
            ."Connection: Upgrade\r\n"
            ."Sec-WebSocket-Accept: ".$hash."\r\n\r\n";

            $this->Send($answer, $id);
            $this->usermgr->GetUser($id)->InitWebSocketConnection();
        }
        else
        {
            $decoded_data = $this->decode($data);
            Logger::getInstance()->outDebug("WebSocket: Receive data: ".var_export($decoded_data, true));

            switch ($decoded_data['type'])
            {
                case 'text':
                    $data = json_decode($decoded_data['payload']);

                    $handlerName = $data->{'opcode'};
                    if (method_exists('PacketHandler', $handlerName))
                    {
                        PacketHandler::$handlerName($data->{'data'}, $id, $this);
                    }
                    else
                    {
                        Logger::getInstance()->outError("Websocket: Got unknown packet: ".$handlerName);

                        $packet = array(
                            'opcode' => 'smsg_error',
                            'data' => array(
                                'message' => 'received unknown packet',
                            )
                        );
                        $this->Send($packet, $id);
                    }
                    break;
                case 'close':
                    $this->Close($id);
                    break;
            }
        }
    }
    
    public function Send($data, $id)
    {
        $user = $this->usermgr->GetUser($id);
        if (!$user || !$user->IsWebsocketInited())
            $this->SendData($data, $id);
        else
        {
            $json_encoded = json_encode($data);
            
            Logger::getInstance()->outDebug("WebSocket: Send data: ".var_export($json_encoded, true));
            $this->SendData($this->encode($json_encoded, 'text', false), $id);
        }
    }

    protected function onClose($id)
    {
        $this->usermgr->RemoveUser($id);
        QueueMgr::getInstance()->LeaveQueue($id);
    }
    
    private function decode($data)
    {
        $unmaskedPayload = '';
        $decodedData = array();

        // estimate frame type:
        $firstByteBinary = sprintf('%08b', ord($data[0]));
        $secondByteBinary = sprintf('%08b', ord($data[1]));
        $opcode = bindec(substr($firstByteBinary, 4, 4));
        $isMasked = ($secondByteBinary[0] == '1') ? true : false;
        $payloadLength = ord($data[1]) & 127;

        // unmasked frame is received:
        if (!$isMasked)
        {
            return array('type' => '', 'payload' => '', 'error' => 'protocol error (1002)');
        }

        switch ($opcode) 
        {
            // text frame:
            case 1:
                $decodedData['type'] = 'text';
                break;

            case 2:
                $decodedData['type'] = 'binary';
                break;

            // connection close frame:
            case 8:
                $decodedData['type'] = 'close';
                break;

            // ping frame:
            case 9:
                $decodedData['type'] = 'ping';
                break;

            // pong frame:
            case 10:
                $decodedData['type'] = 'pong';
                break;

            default:
                return array('type' => '', 'payload' => '', 'error' => 'unknown opcode (1003)');
        }

        if ($payloadLength === 126) 
        {
            $mask = substr($data, 4, 4);
            $payloadOffset = 8;
            $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
        } 
        elseif ($payloadLength === 127) 
        {
            $mask = substr($data, 10, 4);
            $payloadOffset = 14;
            $tmp = '';
            for ($i = 0; $i < 8; $i++) 
            {
                $tmp .= sprintf('%08b', ord($data[$i + 2]));
            }
            $dataLength = bindec($tmp) + $payloadOffset;
            unset($tmp);
        } 
        else 
        {
            $mask = substr($data, 2, 4);
            $payloadOffset = 6;
            $dataLength = $payloadLength + $payloadOffset;
        }

        /**
        * We have to check for large frames here. socket_recv cuts at 1024 bytes
        * so if websocket-frame is > 1024 bytes we have to wait until whole
        * data is transferd.
        */
        if (strlen($data) < $dataLength)
        {
            return false;
        }

        if ($isMasked)
        {
            for ($i = $payloadOffset; $i < $dataLength; $i++) 
            {
                $j = $i - $payloadOffset;
                if (isset($data[$i]))
                {
                    $unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
                }
            }
            $decodedData['payload'] = $unmaskedPayload;
        } 
        else 
        {
            $payloadOffset = $payloadOffset - 4;
            $decodedData['payload'] = substr($data, $payloadOffset);
        }

        return $decodedData;
    }

    private function encode($payload, $type = 'text', $masked = false)
    {
        $frameHead = array();
        $payloadLength = strlen($payload);

        switch ($type)
        {
            case 'text':
                // first byte indicates FIN, Text-Frame (10000001):
                $frameHead[0] = 129;
                break;

            case 'close':
                // first byte indicates FIN, Close Frame(10001000):
                $frameHead[0] = 136;
                break;

            case 'ping':
                // first byte indicates FIN, Ping frame (10001001):
                $frameHead[0] = 137;
                break;

            case 'pong':
                // first byte indicates FIN, Pong frame (10001010):
                $frameHead[0] = 138;
                break;
        }

        // set mask and payload length (using 1, 3 or 9 bytes)
        if ($payloadLength > 65535) 
        {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 255 : 127;
            for ($i = 0; $i < 8; $i++) {
                $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
            }
            // most significant bit MUST be 0
            if ($frameHead[2] > 127) {
                return array('type' => '', 'payload' => '', 'error' => 'frame too large (1004)');
            }
        } 
        elseif ($payloadLength > 125) 
        {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } 
        else
        {
            $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
        }

        // convert frame-head to string:
        foreach (array_keys($frameHead) as $i) 
        {
            $frameHead[$i] = chr($frameHead[$i]);
        }
        
        if ($masked === true)
        {
            // generate a random mask:
            $mask = array();
            for ($i = 0; $i < 4; $i++)
            {
                $mask[$i] = chr(rand(0, 255));
            }

            $frameHead = array_merge($frameHead, $mask);
        }
        $frame = implode('', $frameHead);

        // append payload to frame:
        for ($i = 0; $i < $payloadLength; $i++)
        {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }

        return $frame;
    }
}

?>