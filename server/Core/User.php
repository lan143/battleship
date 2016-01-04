<?
class User
{
    private $current_game;
    private $connection_id;
    private $websocket_inited;
    private $field;

    public function __construct($connection_id)
    {
        $this->current_game = NULL;
        $this->connection_id = $connection_id;
        $this->websocket_inited = false;
    }

    function __destruct()
    {
        if ($this->current_game)
            $this->current_game->PlayerLeave($this->connection_id);
    }
    
    public function IsWebsocketInited()
    {
        return $this->websocket_inited;
    }
    
    public function InitWebSocketConnection()
    {
        $this->websocket_inited = true;
    }

    public function SetGame($game)
    {
        $this->current_game = $game;
    }
    
    public function GetGame()
    {
        return $this->current_game;
    }
    
    public function GenerateField($context)
    {
        $this->field = new Field($context, $this->connection_id);
    }
    
    public function GetField()
    {
        return $this->field;
    }
}
?>