<?php
/**
 * 自旋锁，依赖apcu扩展
 */

namespace system\helpers;


class Spinlock
{
    private array $locks = [];
    private int $lock_timeout;

    /**
     * Spinlock constructor.
     * @param int $timeout 超时时间，0为永不超时（单位：毫秒）
     */
    public function __construct(int $timeout = 200)
    {
        $this->lock_timeout = $timeout;
    }

    private function add($key)
    {
        return apcu_add('slock:' . $key, 1, 5);
    }

    private function del($key)
    {
        return apcu_delete('slock:' . $key);
    }

    private function wait()
    {
        $wait_time = mt_rand(10000, 50000);
        usleep($wait_time);
        return $wait_time;
    }

    public function lock($key)
    {
        if (isset($this->locks[$key])) {
            throw new \Exception('Detected Spinlock deadlock.');
        }
        $total_wait_time = 0;
        $timeout = bcmul($this->lock_timeout, 1000, 0);
        while ($this->add($key) === false) {
            if ($this->lock_timeout > 0 && $total_wait_time > $timeout) {
                throw new \Exception('Detected Spinlock timeout. pid:' . posix_getpid() . '; key:' . $key);
            }
            $wait_time = $this->wait();
            $total_wait_time = bcadd($total_wait_time, $wait_time, 0);
        }
        $this->locks[$key] = 1;
        return $key;
    }

    public function release($key)
    {
        if ($key == false) return false;
        $this->del($key);
        unset($this->locks[$key]);
        return true;
    }
}