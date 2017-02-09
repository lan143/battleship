<?php
namespace Battleship\Game;

class QueueMgr
{
    private function __construct(){  }
    private function __clone()    {  }
    private function __wakeup()   {  }
    private static $instance;

    private $users = array();
    private $is_inited = false;
    private $context = null;
    
    private $games = array();

    public static function getInstance()
    {
        if (empty(self::$instance))
            self::$instance = new self();

        return self::$instance;
    }
    
    public function init($context)
    {
        $this->context = $context;
        $this->is_inited = true;
    }
    
    public function isInited()
    {
        return $this->is_inited;
    }
    
    public function joinQueue($user_id)
    {
        $this->users[] = $user_id;
        
        if (count($this->users) > 1)
        {
            $player_1 = array_shift($this->users);
            $player_2 = array_shift($this->users);
            $this->startGame($player_1, $player_2);
        }
        
        foreach ($this->games as $key => $val)
        {
            if ($val['game'] && $val['game']->isEnded())
            {
                unset($this->games[$key]);
            }
        }
    }
    
    public function leaveQueue($user_id)
    {
        foreach ($this->users as $key => $user)
        {
            if ($user == $user_id)
            {
                unset($this->users[$key]);
            }
        }
    }

    private function startGame($player_1, $player_2)
    {
        $game = array(
            'game' => new Game($player_1, $player_2, $this->context),
            'player_1' => $player_1,
            'player_2' => $player_2
        );
        
        if ($this->context->GetUserMgr()->GetUser($player_1))
            $this->context->GetUserMgr()->GetUser($player_1)->SetGame($game['game']);
        else
            return false;
        
        if ($this->context->GetUserMgr()->GetUser($player_2))
            $this->context->GetUserMgr()->GetUser($player_2)->SetGame($game['game']);
        else
            return false;
        
        $this->games[] = $game;
    }
    
    public function getGameByUserId($id)
    {
        foreach ($this->games as $game)
        {
            if ($game['player_1'] == $id || $game['player_2'] == $id)
                return $game['game'];
        }
    }
}
?>