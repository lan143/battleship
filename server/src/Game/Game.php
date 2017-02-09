<?php
namespace Battleship\Game;

use Battleship\Network\ClientSession;
use Battleship\Network\Packet;

class Game
{
    private $players;
    private $player_can_move;
    private $is_ended;

    public function __construct(ClientSession $player_1, ClientSession $player_2)
    {
        $this->is_ended = false;

        $this->players[] = array(
            'id'      => 1,
            'session' => $player_1,
            'field'   => $player_1->GetField()
        );

        $this->players[] = array(
            'id'      => 2,
            'session' => $player_2,
            'field'   => $player_2->GetField()
        );
        
        $packet = new Packet([
            'opcode' => 'smsg_start_battle',
            'data' => []
        ]);
        
        foreach ($this->players as $player)
        {
            $player['session']->SendPacket($packet);
        }

        $this->setPlayerCanMove(rand(0, 1) == 0 ? $player_1 : $player_2);
    }
    
    private function setPlayerCanMove(int $id) : void
    {
        $this->player_can_move = $id;
        
        foreach ($this->players as $player)
        {
            $packet = new Packet([
                'opcode' => 'smsg_can_move',
                'data' => [
                    'can_move' => $player['id'] == $id
                ]
            ]);

            $player['session']->sendPacket($packet);
        }
    }
    
    private function endGame(int $winner, int $lose) : void
    {
        foreach ($this->players as $player)
        {
            $packet = new Packet([
                'opcode' => 'smsg_end_game',
                'data' => [
                    'you_win' => $player['id'] == $winner,
                    'lose'    => $lose
                ]
            ]);

            $player['session']->sendPacket($packet);
            $player['session']->setGame(NULL);
        }

        $this->is_ended = true;
    }
    
    public function isEnded() : bool
    {
        return $this->is_ended;
    }

    public function playerLeave(int $player_id) : void
    {
        foreach ($this->players as $player)
        {
            if ($player['id'] != $player_id)
            {
                $this->endGame($player['id'], true);
                return;
            }
        }
    }

    public function chatMessage(string $message, ClientSession $session) : void
    {
        $player_id = $this->getPlayerIdBySession($session);

        foreach ($this->players as $player)
        {
            $packet = new Packet([
                'opcode' => 'smsg_game_chat_message',
                'data' => [
                    'message' => $message,
                    'self'    => $player['id'] == $player_id,
                ]
            ]);

            $player['session']->sendPacket($packet);
        }
    }
    
    public function playerMove(\stdClass $data, ClientSession $session) : void
    {
        $player_id = $this->getPlayerIdBySession($session);

        if ($this->player_can_move == $player_id)
        {
            foreach ($this->players as $player)
            {
                if ($player['id'] != $player_id)
                {
                    $result = $player['field']->shot($data->x, $data->y);

                    $packet = new Packet([
                        'opcode' => 'smsg_move',
                        'data' => [
                            'result'    => $result['result'],
                            'x'         => $data->x,
                            'y'         => $data->y,
                            'destroyed' => $result['destroyed'],
                            'ship'      => $result['ship'],
                        ]
                    ]);
                    
                    $player['session']->sendPacket($packet);
                    
                    $packet = new Packet([
                        'opcode' => 'smsg_opponent_move',
                        'data' => [
                            'result'    => $result['result'],
                            'x'         => $data->x,
                            'y'         => $data->y,
                            'destroyed' => $result['destroyed'],
                            'ship'      => $result['ship'],
                        ]
                    ]);

                    $player['session']->sendPacket($packet);
                    
                    if ($result['end_game'])
                        $this->endGame($player_id, false);
                    
                    if ($result['result'] == 1)
                        $this->setPlayerCanMove($player_id);
                    else
                        $this->setPlayerCanMove($player['id']);
                }
            }
        }
        else
        {
            $packet = new Packet([
                'opcode' => 'smsg_move',
                'data' => [
                    'error' => 1
                ]
            ]);

            foreach ($this->players as $player)
            {
                if ($player['id'] == $player_id)
                {
                    $player['session']->sendPacket($packet);
                }
            }
        }
    }

    private function getPlayerIdBySession(ClientSession $session) : int
    {
        foreach ($this->players as $player)
        {
            if ($player['session'] != $session)
            {
                return $player['id'];
            }
        }

        return null;
    }
}
?>