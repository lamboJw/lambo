<?php


namespace system\kernel\Session;


use Exception;
use system\helpers\Singleton;
use system\kernel\BaseModel;
use Swoole\Process;

class SessionService
{
    use Singleton;

    protected bool $start_session = true;
    protected string $session_id_name = 'lambo_session';
    protected string $samesite = 'Lax';
    protected int $max_life_time = 86400;
    protected string $driver = 'file';
    protected string $table = 'lambo_session';
    protected string $database_config = 'default';

    public function __construct()
    {
        $session_config = config('session');
        foreach ($session_config as $key => $value) {
            if (isset($this->$key)) {
                $this->$key = $value;
            }
        }
    }

    public function prepare()
    {
        if (!$this->start_session) {
            return;
        }
        (new SessionManager())->driver_handler()->prepare();
    }

    public function gc()
    {
        if (!$this->start_session) {
            return;
        }
        $main_pid = posix_getpid();
        $session_gc_process = new Process(function (Process $session_gc_process) use ($main_pid) {
            $handler = (new SessionManager())->driver_handler();
            while (true) {
                if (!Process::kill($main_pid, 0)) {
                    $session_gc_process->exit();
                    break;
                }
                try {
                    $handler->gc($this->max_life_time);
                } catch (Exception $e) {
                    debug('ERROR', '捕获错误：' . swoole_last_error() . '， 错误信息：' . $e->getMessage());
                    break;
                }
                sleep(5);
            }
        });
        $session_gc_process->start();
    }

    public function save_session_id()
    {
        if (!$this->start_session) {
            return;
        }
        $expires = time() + $this->max_life_time;
        cookie($this->session_id_name, get_session_id(), $expires, '/', '', false, true, $this->samesite);
    }

    public function start_session()
    {
        $sessionManager = new SessionManager();
        return $sessionManager->load_session();
    }
}