<?php


namespace system\kernel\Session;


class SessionEmptyHandler implements SessionPrepareInterface, \SessionHandlerInterface
{

    /**
     * @inheritDoc
     */
    public function prepare()
    {
        return true;
    }

    public function close()
    {
        return false;
    }

    public function destroy($session_id)
    {
        return true;
    }

    public function gc($maxlifetime)
    {
        return true;
    }

    public function open($save_path, $name)
    {
        return true;
    }

    public function read($session_id)
    {
        return null;
    }

    public function write($session_id, $session_data)
    {
        return true;
    }
}