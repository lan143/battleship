<?php
namespace Battleship\Game;

use Battleship\Network\ClientSession;
use Battleship\Patterns\Singleton\SingletonInterface;
use Battleship\Patterns\Singleton\SingletonTrait;

class QueueMgr implements SingletonInterface
{
    use SingletonTrait;
    
    private $sessions = array();
    private $games = array();
    
    public function joinQueue(ClientSession $session)
    {
        $this->sessions[] = $session;
        
        if (count($this->sessions) > 1)
        {
            $player_1 = array_shift($this->sessions);
            $player_2 = array_shift($this->sessions);
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
    
    public function leaveQueue(ClientSession $session)
    {
        foreach ($this->sessions as $key => $_session)
        {
            if ($_session == $session)
            {
                unset($this->sessions[$key]);
            }
        }
    }

    private function startGame(ClientSession $player_1, ClientSession $player_2)
    {
        $game = array(
            'game' => new Game($player_1, $player_2),
            'player_1' => $player_1,
            'player_2' => $player_2
        );

        $player_1->SetGame($game['game']);
        $player_2->SetGame($game['game']);
        
        $this->games[] = $game;
    }
    
    public function getGameByUserSession(ClientSession $session) : Game
    {
        foreach ($this->games as $game)
        {
            if ($game['player_1'] == $session || $game['player_2'] == $session)
                return $game['game'];
        }

        return null;
    }
}
?>