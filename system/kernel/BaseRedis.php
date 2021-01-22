<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://simps.io
 * @document https://doc.simps.io
 * @license  https://github.com/simple-swoole/simps/blob/master/LICENSE
 */
namespace system\kernel;
/**
 * Class BaseRedis
 * @package system\kernel
 * @method array keys($patten)
 * @method string get($key)
 * @method bool set($key, $string)
 * @method bool select($index)
 */
class BaseRedis
{
    protected Redis $pool;

    protected \Redis $connection;

    public function __construct($config = null)
    {
        if (! empty($config)) {
            $this->pool = Redis::getInstance($config);
        } else {
            $this->pool = Redis::getInstance();
        }
    }

    public function __call($name, $arguments)
    {
        $this->connection = $this->pool->getConnection();
        try {
            $data = $this->connection->{$name}(...$arguments);
        } catch (\RedisException $e) {
            $this->pool->close(null);
            throw $e;
        }

        $this->pool->close($this->connection);

        return $data;
    }

    public function brPop($keys, $timeout)
    {
        $this->connection = $this->pool->getConnection();

        if ($timeout === 0) {
            // TODO Need to optimize...
            $timeout = 99999999999;
        }

        $this->connection->setOption(\Redis::OPT_READ_TIMEOUT, (string) $timeout);

        $data = [];

        try {
            $start = time();
            $data = $this->connection->brPop($keys, $timeout);
        } catch (\RedisException $e) {
            $end = time();
            if ($end - $start < $timeout) {
                $this->pool->close(null);
                throw $e;
            }
        }

        $this->connection->setOption(\Redis::OPT_READ_TIMEOUT, (string) $this->pool->getConfig()['time_out']);

        $this->pool->close($this->connection);

        return $data;
    }

    public function blPop($keys, $timeout)
    {
        $this->connection = $this->pool->getConnection();

        if ($timeout === 0) {
            // TODO Need to optimize...
            $timeout = 99999999999;
        }

        $this->connection->setOption(\Redis::OPT_READ_TIMEOUT, (string) $timeout);

        $data = [];

        try {
            $start = time();
            $data = $this->connection->blPop($keys, $timeout);
        } catch (\RedisException $e) {
            $end = time();
            if ($end - $start < $timeout) {
                $this->pool->close(null);
                throw $e;
            }
        }

        $this->connection->setOption(\Redis::OPT_READ_TIMEOUT, (string) $this->pool->getConfig()['time_out']);

        $this->pool->close($this->connection);

        return $data;
    }

    public function subscribe($channels, $callback)
    {
        $this->connection = $this->pool->getConnection();

        $this->connection->setOption(\Redis::OPT_READ_TIMEOUT, '-1');

        try {
            $data = $this->connection->subscribe($channels, $callback);
        } catch (\RedisException $e) {
            $this->pool->close(null);
            throw $e;
        }

        $this->connection->setOption(\Redis::OPT_READ_TIMEOUT, (string) $this->pool->getConfig()['time_out']);

        $this->pool->close($this->connection);

        return $data;
    }

    public function brpoplpush($srcKey, $dstKey, $timeout)
    {
        $this->connection = $this->pool->getConnection();

        $this->connection->setOption(\Redis::OPT_READ_TIMEOUT, (string) $timeout);

        try {
            $start = time();
            $data = $this->connection->brpoplpush($srcKey, $dstKey, $timeout);
        } catch (\RedisException $e) {
            $end = time();
            if ($end - $start < $timeout) {
                throw $e;
            }
            $data = false;
        }

        $this->connection->setOption(\Redis::OPT_READ_TIMEOUT, (string) $this->pool->getConfig()['time_out']);

        $this->pool->close($this->connection);

        return $data;
    }

    public function __destruct(){
        if(config('app', 'enable_redis_pool')){
            $redis_config_key = config('app', 'redis_config_key');
            $config = config('redis', $redis_config_key);
            $this->connection = $this->pool->getConnection();
            try{
                $this->connection->select($config['db_index'] ?? 0);
            } catch (\RedisException $e) {
                $this->pool->close(null);
                throw $e;
            }
            $this->pool->close($this->connection);
        }
    }
}
