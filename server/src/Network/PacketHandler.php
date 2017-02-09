<?php
namespace Battleship\Network;

class PacketHandler
{
    static function cmsg_request_field($data, ClientSession $session)
    {
        $user = $context->GetUserMgr()->GetUser($id);

        if ($user)
        {
            $user->GenerateField($context);
        }
    }

    static function cmsg_join_queue($data, ClientSession $session)
    {
        if (!QueueMgr::getInstance()->IsInited())
            QueueMgr::getInstance()->Init($context);

        QueueMgr::getInstance()->JoinQueue($id);
    }
    
    static function cmsg_leave_queue($data, ClientSession $session)
    {
        QueueMgr::getInstance()->LeaveQueue($id);
    }
    
    static function cmsg_player_move($data, ClientSession $session)
    {
        $game = $context->GetUserMgr()->GetUser($id)->GetGame();
        
        if ($game)
            $game->PlayerMove($data, $id);
    }
    
    static function cmsg_leave_game($data, ClientSession $session)
    {
        $game = $context->GetUserMgr()->GetUser($id)->GetGame();
        
        if ($game)
            $game->PlayerLeave($id);
    }
    
    static function cmsg_ping($data, ClientSession $session)
    {
        $packet = array(
            'opcode' => 'smsg_pong',
            'data' => array(
            )
        );
        
        $context->Send($packet, $id);
    }
    
    static function cmsg_online($data, ClientSession $session)
    {
        $packet = array(
            'opcode' => 'smsg_online',
            'data' => array(
                'online' => $context->GetUserMgr()->GetUsersCount()
            )
        );
        
        $context->Send($packet, $id);
    }
    
    static function cmsg_game_chat_message($data, ClientSession $session)
    {
        $game = $context->GetUserMgr()->GetUser($id)->GetGame();
        
        if ($game)
        {
            $game->ChatMessage($data->{'message'}, $id);
        }
    }
}

?>
