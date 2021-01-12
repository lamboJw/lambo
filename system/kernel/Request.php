<?php


namespace system\kernel;


class Request
{
    private \Swoole\Http\Request $request;

    public function __construct(\Swoole\Http\Request $request)
    {
        $this->request = $request;
    }

    public function request(...$keys)
    {
        $return = [];
        if (empty($keys)) {
            if (!empty($this->request->get)) $return = array_merge($return, $this->request->get);
            if (!empty($this->request->post)) $return = array_merge($return, $this->request->post);
            return $return;
        }
        foreach ($keys as $key) {
            $return[$key] = isset($this->request->get[$key]) ? $this->request->get[$key] : (isset($this->request->post[$key]) ? $this->request->post[$key] : null);
        }
        if (count($keys) == 1) {
            $return = $return[$keys[0]];
        }
        return $return;
    }

    public function get(...$keys)
    {
        if (empty($keys)) {
            return $this->request->get;
        }
        $return = [];
        foreach ($keys as $key) {
            $return[$key] = $this->request->get[$key] ?? null;
        }
        if (count($keys) == 1) {
            $return = $return[$keys[0]];
        }
        return $return;
    }

    public function post(...$keys)
    {
        if (empty($keys)) {
            return $this->request->post;
        }
        $return = [];
        foreach ($keys as $key) {
            $return[$key] = $this->request->post[$key] ?? null;
        }
        if (count($keys) == 1) {
            $return = $return[$keys[0]];
        }
        return $return;
    }

    public function files()
    {
        return $this->request->files;
    }

    public function tmpfiles()
    {
        return $this->request->tmpfiles;
    }

    public function all()
    {
        $return = [];
        $return['get'] = $this->request->get;
        $return['post'] = $this->request->post;
        $return['files'] = $this->request->files;
        $return['tmpfiles'] = $this->request->tmpfiles;
        return $return;
    }

    public function cookie($key = '')
    {
        if (!empty($key)) {
            return $this->request->cookie[$key] ?? null;
        } else {
            return $this->request->cookie;
        }
    }

    public function server($key = '')
    {
        if (!empty($key)) {
            return $this->request->server[$key] ?? null;
        } else {
            return $this->request->server;
        }
    }

    public function header($key = '')
    {
        if (!empty($key)) {
            return $this->request->header[$key] ?? null;
        } else {
            return $this->request->header;
        }
    }
}