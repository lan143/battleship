<?php

require "User.php";

class UserMgr
{
    private $users;

    public function __construct()
    {
        $this->users = array();
    }
    
    public function AddUser($connection_id)
    {
        $this->users[$connection_id] = new User($connection_id);
    }
    
    public function RemoveUser($connection_id)
    {
        if (isset($this->users[$connection_id]))
            unset($this->users[$connection_id]);
    }
    
    public function GetUser($connection_id)
    {
        if (isset($this->users[$connection_id]))
            return $this->users[$connection_id];
        else
            return NULL;
    }
    
    public function GetUsersCount()
    {
        return count($this->users);
    }
}

?>