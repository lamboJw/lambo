<?php


namespace system\kernel;


use system\helpers\Singleton;

class Log
{
    use Singleton;
    /**
     * Path to save log files
     *
     * @var string
     */
    protected $_log_path;

    /**
     * File permissions
     *
     * @var    int
     */
    protected $_file_permissions = 0644;

    /**
     * 限制最高记录日志等级
     *
     * @var int
     */
    protected int $_threshold = 1;

    /**
     * 日志文件名时间格式
     *
     * @var string
     */
    protected $_date_fmt = 'Y-m-d H:i:s';

    /**
     * 日志文件扩展名
     *
     * @var    string
     */
    protected $_file_ext;

    /**
     * 是否可以写日志
     *
     * @var bool
     */
    protected bool $_enabled = TRUE;

    /**
     * 所有默认的日志等级，不在次数组内的不受限制
     *
     * @var array
     */
    protected array $_levels = array('ERROR' => 1, 'DEBUG' => 2, 'NOTICE' => 3, 'INFO' => 4, 'ALL' => 5);

    /**
     * mbstring.func_overload flag
     *
     * @var bool
     */
    protected static bool $func_overload;

    // --------------------------------------------------------------------

    /**
     * Class constructor
     *
     * @return    void
     */
    public function __construct()
    {
        $config = config('app');

        isset(self::$func_overload) or self::$func_overload = (extension_loaded('mbstring') && ini_get('mbstring.func_overload'));

        $this->_log_path = ($config['log_path'] !== '') ? $config['log_path'] : STATIC_PATH . '/logs';
        $this->_file_ext = !empty($config['log_file_extension']) ? ltrim($config['log_file_extension'], '.') : 'txt';

        file_exists($this->_log_path) or mkdir($this->_log_path, $this->_file_permissions, TRUE);

        if (!is_dir($this->_log_path) or !is_really_writable($this->_log_path)) {
            echo "log disable\n";
            $this->_enabled = FALSE;
        }

        if (is_numeric($config['log_level'])) {
            $this->_threshold = (int)$config['log_level'];
        }

        if (!empty($config['log_date_format'])) {
            $this->_date_fmt = $config['log_date_format'];
        }

        if (!empty($config['log_file_permissions']) && is_int($config['log_file_permissions'])) {
            $this->_file_permissions = $config['log_file_permissions'];
        }
    }

    // --------------------------------------------------------------------

    /**
     * 写日志文件
     *
     * 可以使用全局方法log_message()调用
     *
     * @param string $level 记录日志等级，如果不属于常规日志等级，即为自定义日志，不受日志等级限制
     * @param string $msg 日志信息
     * @return    bool
     */
    public function write_log($level, $msg)
    {
        if ($this->_enabled === FALSE) {
            return FALSE;
        }

        $level = strtoupper($level);

        if (isset($this->_levels[$level]) && ($this->_levels[$level] > $this->_threshold)) {
            return FALSE;
        }

        $filepath = $this->_log_path . '/' . $level . '-' . date('Y-m-d') . '.txt';
        $message = '';

        if (!file_exists($filepath)) {
            $message .= "<" . "?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?" . ">\n\n";
        }

        if (!$fp = @fopen($filepath, 'a+')) {
            return FALSE;
        }

        $message .= $this->_format_line($level, $msg);
        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);

        //修改文件权限为可写
        if (octdec(substr(sprintf('%o', fileperms($filepath)), -4)) != $this->_file_permissions) {
            @chmod($filepath, $this->_file_permissions);
        }
        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Format the log line.
     *
     * This is for extensibility of log formatting
     * If you want to change the log format, extend the CI_Log class and override this method
     *
     * @param string $level The error level
     * @param string $message The log message
     * @return    string    Formatted log line with a new line character at the end
     */
    protected function _format_line($level, $message)
    {
        $message = is_string($message) ? $message : var_export($message, true);
        return $level . ' - ' . date($this->_date_fmt) . ' --> ' . $message . PHP_EOL;
    }

    // --------------------------------------------------------------------

    /**
     * Byte-safe strlen()
     *
     * @param string $str
     * @return    int
     */
    protected static function strlen($str)
    {
        return (self::$func_overload)
            ? mb_strlen($str, '8bit')
            : strlen($str);
    }

    // --------------------------------------------------------------------

    /**
     * Byte-safe substr()
     *
     * @param string $str
     * @param int $start
     * @param int $length
     * @return    string
     */
    protected static function substr($str, $start, $length = NULL)
    {
        if (self::$func_overload) {
            // mb_substr($str, $start, null, '8bit') returns an empty
            // string on PHP 5.3
            isset($length) or $length = ($start >= 0 ? self::strlen($str) - $start : -$start);
            return mb_substr($str, $start, $length, '8bit');
        }

        return isset($length)
            ? substr($str, $start, $length)
            : substr($str, $start);
    }
}