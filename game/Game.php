<?php
namespace app\game;

use Yii;
use app\game\network\ClientSession;
use app\game\network\Packet;

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
        $player = $this->getPlayerBySession($session);
        if ($player)
        {
            $opponent = $this->getOpponent($player);
            if ($opponent)
            {
                $result = $opponent->field->shot($data->x, $data->y);

                $packet = new Packet([
                    'opcode' => 'smsg_move',
                    'data' => [
                        'result'    => $result['result'],
                        'x'         => $data->x,
                        'y'         => $data->y,
                        'destroyed' => isset($result['destroyed']) ? $result['destroyed'] : false,
                        'ship'      => isset($result['ship']) ? $result['ship'] : [],
                    ]
                ]);

                $player->sendPacket($packet);

                $packet = new Packet([
                    'opcode' => 'smsg_opponent_move',
                    'data' => [
                        'result'    => $result['result'],
                        'x'         => $data->x,
                        'y'         => $data->y,
                        'destroyed' => isset($result['destroyed']) ? $result['destroyed'] : false,
                        'ship'      => isset($result['ship']) ? $result['ship'] : [],
                    ]
                ]);

                $opponent->sendPacket($packet);

                if (isset($result['end_game']) && $result['end_game'])
                    $this->endGame($player->id, false);

                if ($result['result'] == 1)
                    $this->setPlayerCanMove($player->id);
                else
                    $this->setPlayerCanMove($opponent->id);
            }
            else
            {
                $packet = new Packet([
                    'opcode' => 'smsg_move',
                    'data' => [
                        'error' => 2
                    ]
                ]);

                $session->sendPacket($packet);
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

            $session->sendPacket($packet);
        }
    }

    /**
     * @param ClientSession $session
     * @return null|int
     */
    private function getPlayerIdBySession(ClientSession $session)
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

    /**
     * @param ClientSession $session
     * @return null|Player
     */
    private function getPlayerBySession(ClientSession $session)
    {
        foreach ($this->players as $player)
        {
            if ($player->session === $session)
            {
                return $player;
            }
        }

        return null;
    }

    /**
     * @param Player $player
     * @return Player|null
     */
    private function getOpponent(Player $player)
    {
        foreach ($this->players as $_player)
        {
            if ($_player !== $player)
            {
                return $_player;
            }
        }

        return null;
    }
}
?>