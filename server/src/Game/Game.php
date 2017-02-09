<?php
namespace Battleship\Game;

use Battleship\Battleship;
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

        $this->players[] = new Player(Player::PLAYER_ONE, $player_1, $player_1->getField());
        $this->players[] = new Player(Player::PLAYER_TWO, $player_2, $player_2->getField());

        $packet = new Packet([
            'opcode' => 'smsg_start_battle',
            'data' => []
        ]);
        
        foreach ($this->players as $player)
        {
            $player->session->SendPacket($packet);
        }

        $this->setPlayerCanMove(rand(Player::PLAYER_ONE, Player::PLAYER_TWO));
    }
    
    private function setPlayerCanMove(int $id)
    {
        $this->player_can_move = $id;
        
        foreach ($this->players as $player)
        {
            $packet = new Packet([
                'opcode' => 'smsg_can_move',
                'data' => [
                    'can_move' => $player->id == $id
                ]
            ]);

            $player->session->sendPacket($packet);
        }
    }
    
    private function endGame(int $winner, int $lose)
    {
        foreach ($this->players as $player)
        {
            $packet = new Packet([
                'opcode' => 'smsg_end_game',
                'data' => [
                    'you_win' => $player->id == $winner,
                    'lose'    => $lose
                ]
            ]);

            $player->session->sendPacket($packet);
            $player->session->removeGame();
        }

        $this->is_ended = true;
    }
    
    public function isEnded() : bool
    {
        return $this->is_ended;
    }

    public function playerLeave(ClientSession $session)
    {
        $player_id = $this->getPlayerIdBySession($session);

        foreach ($this->players as $player)
        {
            if ($player->id != $player_id)
            {
                $this->endGame($player->id, true);
                return;
            }
        }
    }

    public function chatMessage(string $message, ClientSession $session)
    {
        $player_id = $this->getPlayerIdBySession($session);

        foreach ($this->players as $player)
        {
            $packet = new Packet([
                'opcode' => 'smsg_game_chat_message',
                'data' => [
                    'message' => $message,
                    'self'    => $player->id == $player_id,
                ]
            ]);

            $player->session->sendPacket($packet);
        }
    }
    
    public function playerMove(\stdClass $data, ClientSession $session)
    {
        $player_id = $this->getPlayerIdBySession($session);

        Battleship::$app->logger->debug("player_id: ".var_export($player_id, true));
        Battleship::$app->logger->debug("player_can_move: ".var_export($this->player_can_move, true));

        if ($this->player_can_move == $player_id)
        {
            foreach ($this->players as $player)
            {
                Battleship::$app->logger->debug("player->id: ".var_export($player->id, true));

                if ($player->id != $player_id)
                {
                    $result = $player->field->shot($data->x, $data->y);

                    Battleship::$app->logger->debug("result shot: ".var_export($result, true));

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
                    
                    $player->session->sendPacket($packet);
                    
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

                    $player->session->sendPacket($packet);
                    
                    if ($result['end_game'])
                        $this->endGame($player_id, false);
                    
                    if ($result['result'] == 1)
                        $this->setPlayerCanMove($player_id);
                    else
                        $this->setPlayerCanMove($player->id);
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
                if ($player->id == $player_id)
                {
                    $player->session->sendPacket($packet);
                }
            }
        }
    }

    private function getPlayerIdBySession(ClientSession $session) : int
    {
        foreach ($this->players as $player)
        {
            if ($player->session === $session)
            {
                return $player->id;
            }
        }

        return null;
    }
}
?>