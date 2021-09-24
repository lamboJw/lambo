<?php


namespace system\kernel\Session;


use Co\System;

class SessionFileHandler implements \SessionHandlerInterface, SessionPrepareInterface
{

    protected string $path;

    public function __construct()
    {
        $this->path = (string)config('session.file_path', 'storage/session');
        dir_exists($this->path);
    }

    public function close()
    {
        return true;
    }

    /**
     * @param string $session_id
     * @return bool
     */
    public function destroy($session_id)
    {
        $file_path = $this->path . '/' . $session_id;
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
        return true;
    }

    /**
     * 垃圾回收
     * @param int $maxlifetime 文件最大存活时间
     */
    public function gc($maxlifetime)
    {
        $file_list = get_dir_files($this->path, '');
        $del_time = time() - $maxlifetime;
        foreach ($file_list as $file) {
            if(is_array($file)) continue;
            $ctime = filectime($file);
            if ($ctime <= $del_time) {
                @unlink($file);
            }
        }
    }

    public function open($save_path, $name)
    {
        return true;
    }

    /**
     * 读取session文件，没有则返回null
     * @param string $session_id
     * @return mixed|null
     */
    public function read($session_id)
    {
        $file_path = $this->path . '/' . $session_id;
        if (file_exists($file_path)) {
            $file = fopen($file_path, 'r+');
            return System::fread($file);
        } else {
            return null;
        }
    }

    /**
     * 保存session文件
     * @param string $session_id
     * @param string $session_data
     * @return mixed
     */
    public function write($session_id, $session_data)
    {
        $file_path = $this->path . '/' . $session_id;
        $file = fopen($file_path, 'w');
        return System::fwrite($file, $session_data);
    }

    public function prepare()
    {

    }
}