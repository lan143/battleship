<?
require_once "Field.php";

class Game
{
    private $players;
    private $context;
    private $player_can_move;
    private $is_ended;

    public function __construct($player_1, $player_2, $context)
    {
        $this->context = $context;
        $this->is_ended = false;

        $this->players[] = array(
            'id'    => $player_1,
            'field' => $context->GetUserMgr()->GetUser($player_1)->GetField()
        );

        $this->players[] = array(
            'id'    => $player_2,
            'field' => $context->GetUserMgr()->GetUser($player_2)->GetField()
        );
        
        $packet = array(
            'opcode' => 'smsg_start_battle',
            'data' => array(
            )
        );
        
        foreach ($this->players as $player)
        {
            $this->context->Send($packet, $player['id']);
        }

        $this->SetPlayerCanMove(rand(0, 1) == 0 ? $player_1 : $player_2);
    }
    
    private function SetPlayerCanMove($id)
    {
        $this->player_can_move = $id;
        
        foreach ($this->players as $player)
        {
            $packet = array(
                'opcode' => 'smsg_can_move',
                'data' => array(
                    'can_move' => $player['id'] == $id
                )
            );
            
            $this->context->Send($packet, $player['id']);
        }
    }
    
    private function EndGame($winner, $lose)
    {
        foreach ($this->players as $player)
        {
            $packet = array(
                'opcode' => 'smsg_end_game',
                'data' => array(
                    'you_win' => $player['id'] == $winner,
                    'lose'    => $lose
                )
            );
            
            $this->context->Send($packet, $player['id']);
            
            $user = $this->context->GetUserMgr()->GetUser($player['id']);
            if ($user)
                $user->SetGame(NULL);
        }

        $this->is_ended = true;
    }
    
    public function IsEnded()
    {
        return $this->is_ended;
    }

    public function PlayerLeave($player_id)
    {
        foreach ($this->players as $player)
        {
            if ($player['id'] != $player_id)
            {
                $this->EndGame($player['id'], true);
                return;
            }
        }
    }

    public function ChatMessage($message, $player_id)
    {
        foreach ($this->players as $player)
        {
            $packet = array(
                'opcode' => 'smsg_game_chat_message',
                'data' => array(
                    'message' => $message,
                    'self'    => $player['id'] == $player_id,
                )
            );
            
            $this->context->Send($packet, $player['id']);
        }
    }
    
    public function PlayerMove($data, $player_id)
    {
        if ($this->player_can_move == $player_id)
        {
            foreach ($this->players as $player)
            {
                if ($player['id'] != $player_id)
                {
                    $result = $player['field']->Shot($data->{'x'}, $data->{'y'});

                    $packet = array(
                        'opcode' => 'smsg_move',
                        'data' => array(
                            'result'    => $result['result'],
                            'x'         => $data->{'x'},
                            'y'         => $data->{'y'},
                            'destroyed' => $result['destroyed'],
                            'ship'      => $result['ship'],
                        )
                    );
                    
                    $this->context->Send($packet, $player_id);
                    
                    $packet = array(
                        'opcode' => 'smsg_opponent_move',
                        'data' => array(
                            'result'    => $result['result'],
                            'x'         => $data->{'x'},
                            'y'         => $data->{'y'},
                            'destroyed' => $result['destroyed'],
                            'ship'      => $result['ship'],
                        )
                    );

                    $this->context->Send($packet, $player['id']);
                    
                    if ($result['end_game'])
                        $this->EndGame($player_id, false);
                    
                    if ($result['result'] == 1)
                        $this->SetPlayerCanMove($player_id);
                    else
                        $this->SetPlayerCanMove($player['id']);
                }
            }
        }
        else
        {
            $packet = array(
                'opcode' => 'smsg_move',
                'data' => array(
                    'error' => 1
                )
            );
            
            $this->context->Send($packet, $player_id);
        }
    }
}
?>