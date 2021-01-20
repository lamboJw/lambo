<?php


namespace system\kernel;


use system\helpers\Singleton;

class Debug
{
    use Singleton;

    protected array $_levels = array('ERROR' => 1, 'DEBUG' => 2, 'NOTICE' => 3, 'INFO' => 4, 'ALL' => 5);

    public function debug($level, $msg)
    {
        $level = strtoupper($level);
        if (!config('app', 'debug') || !in_array($level, array_keys($this->_levels)) || $this->_levels[$level] > config('app', 'debug_level')) {
            return false;
        }
        $old = app()->ob_get_all();
        echo $level . ' - ' . date("Y-m-d H:i:s") . ' --> ' . var_export($msg, true) . "\n";
        app()->ob_restore_all($old);
        log_message($level, $msg);
    }
}
