<?php


namespace system\kernel\Session;


class SessionModel extends \system\kernel\Model
{
    protected string $keyName = 'session_id';
    public function __construct()
    {
        $this->db = (string)config('session.database_config', 'default');
        $this->tableName = (string)config('session.table', 'lambo_session');
        parent::__construct();
    }
}