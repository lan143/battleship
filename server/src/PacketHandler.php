<?php

require_once "Battleship/QueueMgr.php";

class PacketHandler
{
    static function cmsg_request_field($data, $id, $context)
    {
        $user = $context->GetUserMgr()->GetUser($id);

        if ($user)
        {
            $user->GenerateField($context);
        }
    }

    static function cmsg_join_queue($data, $id, $context)
    {
        if (!QueueMgr::getInstance()->IsInited())
            QueueMgr::getInstance()->Init($context);

        QueueMgr::getInstance()->JoinQueue($id);
    }
    
    static function cmsg_leave_queue($data, $id, $context)
    {
        QueueMgr::getInstance()->LeaveQueue($id);
    }
    
    static function cmsg_player_move($data, $id, $context)
    {
        $game = $context->GetUserMgr()->GetUser($id)->GetGame();
        
        if ($game)
            $game->PlayerMove($data, $id);
    }
    
    static function cmsg_leave_game($data, $id, $context)
    {
        $game = $context->GetUserMgr()->GetUser($id)->GetGame();
        
        if ($game)
            $game->PlayerLeave($id);
    }
    
    static function cmsg_ping($data, $id, $context)
    {
        $packet = array(
            'opcode' => 'smsg_pong',
            'data' => array(
            )
        );
        
        $context->Send($packet, $id);
    }
    
    static function cmsg_online($data, $id, $context)
    {
        $packet = array(
            'opcode' => 'smsg_online',
            'data' => array(
                'online' => $context->GetUserMgr()->GetUsersCount()
            )
        );
        
        $context->Send($packet, $id);
    }
    
    static function cmsg_game_chat_message($data, $id, $context)
    {
        $game = $context->GetUserMgr()->GetUser($id)->GetGame();
        
        if ($game)
        {
            $game->ChatMessage($data->{'message'}, $id);
        }
    }
}

?>
