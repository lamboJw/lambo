<?php

namespace system\kernel;
/**
 * Ftp操作
 */
class Ftp
{
    protected array $config;
    protected bool $connected = false;
    /**
     * @var resource|null
     */
    protected $conn = null;
    protected array $default_text_extension = ['txt', 'js', 'css', 'html', 'htm', 'php', 'log', 'xml'];

    public function __construct()
    {
        $this->config = (array)config('ftp.' . config('app.ftp_config_key', 'default'), []);
        $this->conn();
    }

    function __destruct()
    {
        if ($this->connected) ftp_close($this->conn);
    }

    protected function conn()
    {
        if (empty($this->config)) return;
        if ($this->conn = ftp_connect($this->config['host'], $this->config['port'])) {
            $this->connected = true;
            ftp_login($this->conn, $this->config['username'], $this->config['password']);
            //开启被动模式
            if ($this->config['passive']) {
                ftp_pasv($this->conn, true);
            }
        }
    }

    /**
     * 上传文件到ftp服务器
     * @param string $local_path 本地文件绝对路径
     * @param string $remote_path 远程文件绝对路径
     * @param string $mode 传输模式，auto：自动识别，text：纯文本，binary：二进制流
     * @return bool
     * @throws FtpException
     */
    public function upload($local_path, $remote_path, $mode = 'auto')
    {
        if (!$this->connected) return false;
        if (empty($local_path)) throw new FtpException('本地文件路径为空');
        if (empty($remote_path)) throw new FtpException('远程文件路径为空');
        if (!file_exists($local_path)) throw new FtpException('本地文件不存在');
        $re = $this->ftp_warning_handler('ftp_put', [$this->conn, $remote_path, $local_path, $this->get_mode($local_path, $mode)]);
        if (!$re && $this->config['debug']) {
            debug('DEBUG', '上传文件到ftp服务器失败：' . $local_path . ' --> ' . $remote_path . $this->get_warning_msg());
        }
        return $re;
    }

    protected function get_mode($path, $mode)
    {
        if ($mode == 'auto') {
            $path_info = pathinfo($path);
            if (!empty($path_info['extension']) && in_array($path_info['extension'], config('app.ftp_text_extension', $this->default_text_extension))) {
                $mode = 'text';
            } else {
                $mode = 'binary';
            }
        }
        return $mode === 'text' ? FTP_ASCII : FTP_BINARY;
    }

    /**
     * 创建远程目录，父目录必须有ftp登录用户的权限，否则创建失败
     * @param string $remote_path 远程目录绝对路径
     * @param string $permission 目录权限，默认744
     * @return bool
     * @throws FtpException
     */
    public function create_remote_path($remote_path, $permission = '744')
    {
        if (!$this->connected) return false;
        if (empty($remote_path)) throw new FtpException('远程目录为空');
        if (empty($permission)) throw new FtpException('目录权限为空');
        $path_arr = explode('/', trim($remote_path, '/'));
        if ($path_arr[0] === '') return true;
        @ftp_chdir($this->conn, '/');
        foreach ($path_arr as $path) {
            if (!@ftp_chdir($this->conn, $path)) {
                if (!$this->mkdir($path) || !$this->chmod($permission, $path) || !$this->chdir($path)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 下载远程文件
     * @param string $remote_path 远程文件绝对路径
     * @param string $local_path 本地文件绝对路径
     * @param string $mode 传输模式，auto：自动识别，text：纯文本，binary：二进制流
     * @return bool
     * @throws FtpException
     */
    public function download($remote_path, $local_path, $mode = 'auto')
    {
        if (!$this->connected) return false;
        if (empty($local_path)) throw new FtpException('本地文件路径为空');
        if (empty($remote_path)) throw new FtpException('远程文件路径为空');
        if (ftp_mdtm($this->conn, $remote_path) === -1) throw new FtpException('远程文件不存在');
        $re = $this->ftp_warning_handler('ftp_get', [$this->conn, $local_path, $remote_path, $this->get_mode($local_path, $mode)]);
        if (!$re && $this->config['debug']) {
            debug('DEBUG', '从ftp服务器下载文件到本地失败：' . $remote_path . ' --> ' . $local_path . $this->get_warning_msg());
        }
        return $re;
    }

    /**
     * 重命名/移动文件
     * @param string $old_name 原文件路径
     * @param string $new_name 新文件路径
     * @return bool
     * @throws FtpException
     */
    public function rename($old_name, $new_name)
    {
        if (!$this->connected) return false;
        if (empty($old_name)) throw new FtpException('旧文件路径为空');
        if (empty($new_name)) throw new FtpException('新文件路径为空');
        if (ftp_mdtm($this->conn, $old_name) === -1) throw new FtpException('远程文件不存在');
        $re = $this->ftp_warning_handler('ftp_rename', [$this->conn, $old_name, $new_name]);
        if (!$re && $this->config['debug']) {
            debug('DEBUG', '重命名ftp服务器文件失败：' . $old_name . ' --> ' . $new_name . $this->get_warning_msg());
        }
        return $re;
    }

    /**
     * 删除文件
     * @param string $remote_path 远程文件路径
     * @return bool
     * @throws FtpException
     */
    public function delete_file($remote_path)
    {
        if (!$this->connected) return false;
        if (empty($remote_path)) throw new FtpException('远程文件路径为空');
        $re = $this->ftp_warning_handler('ftp_delete', [$this->conn, $remote_path]);
        if (!$re && $this->config['debug']) {
            debug('DEBUG', '删除ftp服务器文件失败：' . $remote_path . $this->get_warning_msg());
        }
        return $re;
    }

    /**
     * 切换当前目录
     * @param string $path 要切换的目录
     * @return bool
     * @throws FtpException
     */
    public function chdir($path)
    {
        if (!$this->connected) return false;
        if (empty($path)) throw new FtpException('要切换的目录为空');
        $re = $this->ftp_warning_handler('ftp_chdir', [$this->conn, $path]);
        if (!$re && $this->config['debug']) {
            $pwd = ftp_pwd($this->conn);
            debug('DEBUG', '切换ftp服务器当前目录失败：' . $pwd . ' --> ' . $path . $this->get_warning_msg());
        }
        return $re;
    }

    /**
     * 创建目录
     * @param string $dir 目录名称
     * @return bool
     * @throws FtpException
     */
    public function mkdir($dir)
    {
        if (!$this->connected) return false;
        if (empty($dir)) throw new FtpException('目录名称为空');
        $re = $this->ftp_warning_handler('ftp_mkdir', [$this->conn, $dir]);
        if (!$re && $this->config['debug']) {
            debug('DEBUG', '创建ftp服务器目录失败：' . $dir . $this->get_warning_msg());
        }
        return $re;
    }

    /**
     * 删除目录
     * @param string $dir 目录名称
     * @return bool
     * @throws FtpException
     */
    public function rmdir($dir)
    {
        if (!$this->connected) return false;
        if (empty($dir)) throw new FtpException('目录名称为空');
        $re = $this->ftp_warning_handler('ftp_rmdir', [$this->conn, $dir]);
        if (!$re && $this->config['debug']) {
            debug('DEBUG', '删除ftp服务器目录失败：' . $dir . $this->get_warning_msg());
        }
        return $re;
    }

    /**
     * 修改目录/文件权限
     * @param string $mode 权限
     * @param string $filename 目录/文件路径
     * @return bool
     * @throws FtpException
     */
    public function chmod($mode, $filename)
    {
        if (!$this->connected) return false;
        if (empty($filename)) throw new FtpException('文件路径为空');
        $mode = octdec(str_pad($mode, 4, '0', STR_PAD_LEFT));
        $re = $this->ftp_warning_handler('ftp_chmod', [$this->conn, $mode, $filename]);
        if (!$re && $this->config['debug']) {
            debug('DEBUG', '修改ftp服务器文件权限失败：' . $filename . ' --> ' . $mode . $this->get_warning_msg());
        }
        return $re;
    }

    /**
     * 切换到父目录
     * @return bool
     */
    public function cdup()
    {
        if (!$this->connected) return false;
        $re = $this->ftp_warning_handler('ftp_cdup', [$this->conn]);
        if (!$re && $this->config['debug']) {
            $pwd = @ftp_pwd($this->conn);
            debug('DEBUG', '切换ftp服务器当前目录到父目录失败，当前目录：' . $pwd . $this->get_warning_msg());
        }
        return $re;
    }

    /**
     * 将本地目录的内容克隆到远程目录，包括子目录。
     * @param string $local_path 本地目录绝对路径
     * @param string $remote_path 远程目录绝对路径
     * @return bool
     * @throws FtpException
     */
    public function clone_dir($local_path, $remote_path)
    {
        if (!$this->connected) return false;
        if (empty($local_path)) throw new FtpException('本地目录为空');
        if (empty($remote_path)) throw new FtpException('远程目录为空');
        if (!is_dir($local_path)) throw new FtpException($local_path . '不是目录');
        if (!@ftp_chdir($this->conn, $remote_path) && !$this->create_remote_path($remote_path))
            throw new FtpException('远程目录不存在且创建失败：' . $remote_path);
        $local_path = rtrim($local_path, '/') . '/';
        $remote_path = rtrim($remote_path, '/') . '/';
        $file_arr = scandir($local_path);
        foreach ($file_arr as $file) {
            if (in_array($file, ['.', '..'])) {     //过滤无用文件
                continue;
            }
            $file_path = $local_path . $file;       //当前操作目录
            $remote_file_path = $remote_path . $file;
            if (is_dir($file_path)) {
                $this->clone_dir($file_path, $remote_file_path);
            } else {
                $this->upload($file_path, $remote_file_path);
            }
        }
        return true;
    }

    protected string $error = '';

    protected function ftp_warning_handler($func, array $params = []): bool
    {
        try {
            @trigger_error('ftp_flag', E_USER_WARNING);
            $re = @call_user_func_array($func, $params);
            $error = error_get_last();
            if ($error['message'] !== 'ftp_flag' && $error['type'] !== E_USER_WARNING) {
                throw new \Exception($error['message']);
            }
            return $re;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    protected function get_warning_msg()
    {
        return !empty($this->error) ? ("，错误信息：{$this->error}") : '';
    }
}

class FtpException extends \Exception
{

}