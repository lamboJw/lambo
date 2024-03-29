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
    function get_dir_files($path, $ext = 'php')
    {
        $file_list = [];
        $ext = strtolower($ext);
        $path = rtrim($path, '/') . '/';
        $arr = scandir($path);      //扫描目录下所有文件和文件夹
        foreach ($arr as $item) {
            if (in_array($item, ['.', '..'])) {     //过滤无用文件
                continue;
            }
            $file_path = $path . $item;       //当前操作目录
            if (is_dir($file_path)) {    //如果是文件夹，继续扫描内容
                $file_list[$item] = get_dir_files($file_path, $ext);
            } else {                                //是文件
                $file_info = pathinfo($file_path);  //获取扩展名
                if (!isset($file_info['extension'])) {
                    $file_info['extension'] = '';
                }
                $extension = strtolower($file_info['extension']);
                if (empty($ext) || $extension == $ext) {
                    $file_list[] = $file_path;
                }
            }
        }
        return $file_list;
    }
}

if (!function_exists('dir_exists')) {
    function dir_exists($path)
    {
        $root = rtrim(ROOT_PATH, '/') . '/';
        $path = str_replace($root, '', $path);
        $path_arr = explode('/', $path);
        $cur_path = $root;
        foreach ($path_arr as $item) {
            $cur_path .= $item . '/';
            if (!is_dir($cur_path)) {
                mkdir($cur_path);
            }
        }
        return true;
    }
}

if (!function_exists('config_all')) {
    function &config_all()
    {
        static $_config = [];

        if (empty($_config)) {
            $files = get_dir_files(CONFIG_PATH);
            foreach ($files as $file_path) {
                if (is_array($file_path)) continue;
                $config = [];
                require_once $file_path;
                $file_info = pathinfo($file_path);
                $_config[$file_info['filename']] = $config;
            }
        }
        return $_config;
    }
}

if (!function_exists('config')) {
    function config($name, $default = null)
    {
        $config = config_all();
        if (empty($name)) {
            return null;
        }
        $name_arr = explode('.', $name);
        foreach ($name_arr as $item) {
            if ($config == null) {
                break;
            }
            $config = $config[$item] ?? null;
        }
        return $config == null ? $default : $config;
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

if (!function_exists('library')) {
    function library($name)
    {
        static $_library = [];
        if (!isset($_library[$name])) {
            $class = "\\app\\libraries\\{$name}";
            $_library[$name] = new $class();
        }
        return $_library[$name];
    }
}
if (!function_exists('helper')) {
    function helper($name)
    {
        static $_helper = [];
        if (!isset($_helper[$name])) {
            $class = "\\app\\helpers\\{$name}";
            $_helper[$name] = new $class();
        }
        return $_helper[$name];
    }
}

if (!function_exists('autoload_lib_and_helper')) {
    function autoload_lib_and_helper()
    {
        $autoload_config = config('autoload');
        if (!empty($autoload_config['libraries'])) {
            foreach ($autoload_config['libraries'] as $class) {
                library($class);
            }
        }
        if (!empty($autoload_config['helpers'])) {
            foreach ($autoload_config['helpers'] as $class) {
                helper($class);
            }
        }
    }
}

if (!function_exists('app')) {
    function app(): \system\kernel\Application
    {
        return \system\kernel\Application::getInstance();
    }
}

if (!function_exists('request')) {
    /**
     * @param mixed ...$keys
     * @return array|mixed|null|\system\kernel\HttpServer\Request
     */
    function request(...$keys)
    {
        if (!\system\kernel\Application::isInstantiated()) {
            throw new RuntimeException('Application未实例化');
        }
        if (empty($keys)) {
            return app()->request();
        } else {
            return app()->request()->request(...$keys);
        }
    }
}


if (!function_exists('response')) {
    /**
     * @param string $data
     * @return mixed|\system\kernel\HttpServer\Response
     */
    function response($data = '')
    {
        if (!\system\kernel\Application::isInstantiated()) {
            throw new RuntimeException('Application未实例化');
        }
        if (empty($data)) {
            return app()->response();
        } else {
            return app()->response()->write($data);
        }
    }
}

if (!function_exists('ws_response')) {
    function ws_response(): \system\kernel\WebsocketServer\WebsocketResponseBase
    {
        if (!\system\kernel\Application::isInstantiated()) {
            throw new RuntimeException('Application未实例化');
        }
        return app()->ws_response();
    }
}

if (!function_exists('server')) {
    function server($key = '')
    {
        if (!\system\kernel\Application::isInstantiated()) {
            throw new RuntimeException('Application未实例化');
        }
        return app()->request()->server($key);
    }
}

if (!function_exists('api_json')) {
    function api_json($code = 1, $msg = 'success', $data = []): string
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

if (!function_exists('session')) {
    function session($key, $value = null)
    {
        if ($value === null) {
            return app()->session()->get($key);
        } else {
            return app()->session()->set($key, $value);
        }
    }
}

if (!function_exists('get_session_id')) {
    function get_session_id()
    {
        return app()->session()->get_sid();
    }
}

if (!function_exists('session_service')) {
    function session_service(): \system\kernel\Session\SessionService
    {
        return \system\kernel\Session\SessionService::getInstance();
    }
}

if (!function_exists('cookie')) {
    function cookie($key, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null, $priority = null)
    {
        if ($value === null) {
            return app()->request()->cookie($key);
        } else {
            app()->response()->cookie($key, $value, $expires, $path, $domain, $secure, $httponly, $samesite, $priority);
        }
    }
}

if (!function_exists('log_message')) {
    function log_message($level, $message)
    {
        \system\helpers\Log::getInstance()->write_log($level, $message);
    }
}

if (!function_exists('debug')) {
    function debug($level, $message)
    {
        \system\helpers\Debug::getInstance()->debug($level, $message);
    }
}

if (!function_exists('view')) {
    function view($view, $data = [])
    {
        response(\system\kernel\HttpServer\View::getInstance()->make($view, $data)->render());
    }
}

if (!function_exists('ftp')) {
    function ftp(): \system\kernel\Ftp
    {
        return app()->singleton('ftp', \system\kernel\Ftp::class);
    }
}