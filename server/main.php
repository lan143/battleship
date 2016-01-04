<?php
ini_set('mbstring.func_overload', "2");
ini_set('mbstring.internal_encoding', "UTF-8");

include_once("config.php");
include_once("Core/Logger.php");
include_once("Core/Websocket.php");

set_time_limit(0);

Logger::getInstance()->Init($arConfig['loglevel']);

Logger::getInstance()->outString("-----------------------------");
Logger::getInstance()->outString("|        Game server        |");
Logger::getInstance()->outString("|    devel fuck the shit    |");
Logger::getInstance()->outString("-----------------------------");

$websocket = new Websocket($arConfig['listen_host'], $arConfig['listen_port']);
$websocket->DoWork();
?>