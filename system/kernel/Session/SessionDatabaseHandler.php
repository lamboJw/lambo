<?php


namespace system\kernel\Session;


use system\kernel\BaseModel;
use system\kernel\Model;

class SessionDatabaseHandler implements \SessionHandlerInterface, SessionPrepareInterface
{
    protected Model $model;

    public function __construct()
    {
        $this->model = new SessionModel();
    }

    public function close()
    {
        return true;
    }

    /**
     * 删除指定session
     */
    public function destroy($session_id)
    {
        $this->model->del($session_id);
    }

    /**
     * 删除过期session
     */
    public function gc($maxlifetime)
    {
        $this->model->del(['add_time[<]' => date("Y-m-d H:i:s", time() - $maxlifetime)]);
    }

    public function open($save_path, $name)
    {
        return true;
    }

    /**
     * 获取session内容
     */
    public function read($session_id)
    {
        return $this->model->getInfo($session_id, 'session_data');
    }

    /**
     * 保存session内容
     */
    public function write($session_id, $session_data)
    {
        $data = compact('session_id', 'session_data');
        $this->model->updateOrInsert($session_id, $data);
    }

    /**
     * 检查是否存在数据表，若不存在则创建
     */
    public function prepare()
    {
        $table = config('session.table', 'lambo_session');
        $database_config = config('session.database_config', 'default');
        $sql = "select * from information_schema.`TABLES` where table_name = '{$table}';";
        $model = new BaseModel($database_config);
        $session_table = $model->query($sql)->fetch();
        if (empty($session_table)) {
            $create_table_sql = "CREATE TABLE `{$table}` (
                  `session_id` char(32) NOT NULL DEFAULT '',
                  `session_data` text NOT NULL,
                  `add_time` datetime DEFAULT NULL,
                  `edit_time` datetime DEFAULT NULL,
                  PRIMARY KEY (`session_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;";
            $model->query($create_table_sql);
        }
    }
}