<?php
defined('SYSTEM_PATH') or exit('No direct script access allowed');


if (!function_exists('is_php')) {
    /**
     * Determines if the current version of PHP is equal to or greater than the supplied value
     *
     * @param string
     * @return    bool    TRUE if the current version is $version or higher
     */
    function is_php($version)
    {
        static $_is_php;
        $version = (string)$version;

        if (!isset($_is_php[$version])) {
            $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
        }

        return $_is_php[$version];
    }
}

if (!function_exists('is_really_writable')) {
    /**
     * Tests for file writability
     *
     * is_writable() returns TRUE on Windows servers when you really can't write to
     * the file, based on the read-only attribute. is_writable() is also unreliable
     * on Unix servers if safe_mode is on.
     *
     * @link    https://bugs.php.net/bug.php?id=54709
     * @param string
     * @return    bool
     */
    function is_really_writable($file)
    {
        // If we're on a Unix server with safe_mode off we call is_writable
        if (DIRECTORY_SEPARATOR === '/' && (is_php('5.4') or !ini_get('safe_mode'))) {
            return is_writable($file);
        }

        /* For Windows servers and safe_mode "on" installations we'll actually
         * write a file then read it. Bah...
         */
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === FALSE) {
                return FALSE;
            }

            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return TRUE;
        } elseif (!is_file($file) or ($fp = @fopen($file, 'ab')) === FALSE) {
            return FALSE;
        }

        fclose($fp);
        return TRUE;
    }
}

if (!function_exists('get_dir_files')) {
    function get_dir_files($path)
    {
        $file_list = [];
        $path = rtrim($path, '/') . '/';
        $arr = scandir($path);      //扫描目录下所有文件和文件夹
        foreach ($arr as $item) {
            if (in_array($item, ['.', '..'])) {     //过滤无用文件
                continue;
            }
            $file_path = $path . $item;       //当前操作目录
            if (is_dir($path . $item)) {    //如果是文件夹，继续扫描内容
                get_dir_files($file_path);
            } else {                                //是文件
                $file_info = pathinfo($file_path);  //获取扩展名
                if (!isset($file_info['extension'])) {
                    continue;
                }
                $ext = strtolower($file_info['extension']);
                if ($ext == 'php') {   //如果是php文件，就插入文件路径到file_list
                    $file_list[$file_info['filename']] = $file_path;
                }
            }
        }
        return $file_list;
    }
}


if (!function_exists('config_all')) {
    function &config_all()
    {
        static $_config = [];

        if (empty($_config)) {
            $files = get_dir_files(CONFIG_PATH);
            foreach ($files as $file_name => $file_path) {
                $config = [];
                require_once $file_path;
                $_config[$file_name] = $config;
            }
        }
        return $_config;
    }
}

if (!function_exists('config')) {
    function config($name, $item = '', $default = null)
    {
        $config_all = &config_all();
        if (!empty($item) || $item === 0) {
            return $config_all[$name][$item] ?? $default;
        } else {
            return $config_all[$name] ?? $default;
        }
    }
}

if (!function_exists('load_class')) {
    function load_class($class)
    {
        $path = ROOT_PATH . '/' . str_replace('\\', '/', $class) . '.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
}
spl_autoload_register('load_class');


if (!function_exists('app')) {
    function app()
    {
        return \system\kernel\Application::getInstance();
    }
}

if (!function_exists('request')) {
    function request(...$keys)
    {
        if (empty($keys)) {
            return app()->request();
        } else {
            return app()->request()->request(...$keys);
        }
    }
}


if (!function_exists('response')) {
    function response($data = '')
    {
        if (empty($data)) {
            return app()->response();
        } else {
            return app()->response()->write($data);
        }
    }
}

if (!function_exists('ws_response')) {
    function ws_response($data = '', $opcode = WEBSOCKET_OPCODE_TEXT, $flags = true)
    {
        if (empty($data)) {
            return app()->ws_response();
        } else {
            return app()->ws_response()->push($data, $opcode, $flags);
        }
    }
}

if (!function_exists('server')) {
    function server($key = '')
    {
        return app()->request()->server($key);
    }
}

if (!function_exists('api_json')) {
    function api_json($code = 1, $msg = 'success', $data = [])
    {
        return json_encode(compact('code', 'msg', 'data'));
    }
}

if (!function_exists('api_response')) {
    function api_response($code = 1, $msg = 'success', $data = [])
    {
        response()->json(compact('code', 'msg', 'data'));
    }
}

if (!function_exists('log_message')) {
    function log_message($level, $message)
    {
        \system\kernel\Log::getInstance()->write_log($level, $message);
    }
}

if (!function_exists('debug')) {
    function debug($level, $message)
    {
        \system\kernel\Debug::getInstance()->debug($level, $message);
    }
}

if (!function_exists('server_type')) {
    function server_type()
    {
        return config('app', 'server_type');
    }
}
