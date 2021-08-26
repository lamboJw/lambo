<?php


namespace system\kernel\Session;


class SessionDatabaseHandler implements \SessionHandlerInterface
{

    /**
     * @inheritDoc
     */
    public function close()
    {
        // TODO: Implement close() method.
    }

    /**
     * @inheritDoc
     */
    public function destroy($session_id)
    {
        // TODO: Implement destroy() method.
    }

    /**
     * @inheritDoc
     */
    public function gc($maxlifetime)
    {
        // TODO: Implement gc() method.
    }

    /**
     * @inheritDoc
     */
    public function open($save_path, $name)
    {
        // TODO: Implement open() method.
    }

    /**
     * @inheritDoc
     */
    public function read($session_id)
    {
        // TODO: Implement read() method.
    }

    /**
     * @inheritDoc
     */
    public function write($session_id, $session_data)
    {
        // TODO: Implement write() method.
    }
}