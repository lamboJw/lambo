<?php


namespace system\kernel\Session;


use system\kernel\Database\Redis;

class SessionRedisHandler implements \SessionHandlerInterface, SessionPrepareInterface
{
    protected Redis $redis;
    protected string $prefix = 'lambo_session:';
    protected int $max_life_time = -1;

    public function __construct()
    {
        $this->redis = new Redis();
        $this->max_life_time = (int)config('session.max_life_time');
        $this->prefix = (string)config('session.table') . ':';
    }

    public function close()
    {
        return true;
    }

    /**
     * 删除指定session
     * @param $session_id
     * @return bool
     */
    public function destroy($session_id)
    {
        return $this->redis->del($this->prefix . $session_id);
    }

    /**
     * redis自动过期，所以直接返回true
     * @param $maxlifetime
     * @return bool
     */
    public function gc($maxlifetime)
    {
        return true;
    }

    public function open($save_path, $name)
    {
        return true;
    }

    /**
     * 获取指定session内容
     * @param $session_id
     * @return string
     */
    public function read($session_id)
    {
        return $this->redis->get($this->prefix . $session_id);
    }

    /**
     * 写入session内容
     * @param $session_id
     * @param $session_data
     * @return bool
     */
    public function write($session_id, $session_data)
    {
        return $this->redis->setex($this->prefix . $session_id, $this->max_life_time, $session_data);
    }

    public function prepare()
    {

    }
}