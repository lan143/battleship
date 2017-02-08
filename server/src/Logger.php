<?php

class Logger
{
    private $output;
    private $log_level;
    private static $instance;

    private function __construct(){  }
    private function __clone()    {  }
    private function __wakeup()   {  }

    function __destruct()
    {
        fclose($this->output);
    }

    public static function getInstance()
    {
        if ( empty(self::$instance) )
            self::$instance = new self();

        return self::$instance;
    }

    public function Init($loglevel)
    {
        global $arConfig;
        $this->output = fopen($arConfig['logdir'].'/'.date('Y_m_d_H_i_s').'_'.$arConfig['logfile_name'], "w");
        $this->debug = fopen($arConfig['logdir'].'/'.date('Y_m_d_H_i_s').'_'.$arConfig['debug_logfile_name'], "w");
        $this->error = fopen($arConfig['logdir'].'/'.date('Y_m_d_H_i_s').'_'.$arConfig['error_logfile_name'], "w");

        $this->log_level = $loglevel;

        $this->outString("Log inited. Output file: ".$arConfig['logfile_name'].". Debug output: ".$arConfig['debug_logfile_name'].". Errors output: ".$arConfig['error_logfile_name'].". Log level: ".$loglevel);
    }

    public function outString($text)
    {
        if ($this->log_level <= 2)
            $this->out($text, "");
    }

    public function outError($text)
    {
        if ($this->log_level <= 1)
            $this->out($text, "ERROR");
    }

    public function outDebug($text)
    {
        if ($this->log_level == 0)
            $this->out($text, "DEBUG");
    }
    
    private function out($text, $mode)
    {
        $str = date('Y.m.d H:i:s').": ".(strlen($mode) == 0 ? $mode : $mode.": ")."".$text."\r\n";
        echo $str;

        switch ($mode)
        {
            case "DEBUG":
                fwrite($this->debug, $str);
                break;
            case "ERROR":
                fwrite($this->error, $str);
                break;
            default:
                fwrite($this->output, $str);
        }
    }
}

?>