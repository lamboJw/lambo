<?php


namespace system\kernel\Session;


use SessionHandlerInterface;

class SessionStore
{
    /**
     * session包含的所有内容
     * @var array
     */
    protected array $attributes = [];

    /**
     * @var string
     */
    protected string $session_id;

    protected SessionHandlerInterface $handler;

    protected string $session_id_name;

    public function __construct($handler)
    {
        $this->session_id_name = (string)config('session.session_id_name', 'lambo_session');
        $this->get_session_id();
        $this->handler = $handler;
        $this->read_session();
    }

    protected function create_session_id()
    {
        $rand_num = mt_rand(10000, 99999);
        $time = microtime();
        return md5(sha1($time . $rand_num . config('session.encrypt_key', 'LSNBDxH6zSyB4hrovEj9')));
    }

    protected function get_session_id()
    {
        $this->session_id = (string)request()->cookie($this->session_id_name);
        if (empty($this->session_id)) {
            $this->session_id = (string)request($this->session_id_name);
            if (empty($this->session_id)) {
                $this->session_id = $this->create_session_id();
            }
        }
    }

    public function get_sid()
    {
        return $this->session_id;
    }

    protected function read_session()
    {
        $data = $this->handler->read($this->session_id);
        if (!empty($data)) {
            $this->attributes = @unserialize($data);
        }
    }

    public function get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function set(string $key, $value)
    {
        $this->attributes[$key] = $value;
        return true;
    }

    protected function save(): bool
    {
        return $this->handler->write($this->session_id, @serialize($this->attributes));
    }

    public function __destruct()
    {
        $this->save();
    }
}