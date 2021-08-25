<?php

declare(strict_types=1);

namespace system\kernel;
/**
 * 部分redis命令，方便ide自动提示
 * @package system\kernel
 * @method bool select($index)
 * @method bool del($key)
 * @method bool exists($key)
 * @method bool expire($key, $second)
 * @method bool expireat($key, $timestamp)
 * @method array keys($pattern)
 * @method int ttl($key)
 * @method string get($key)
 * @method bool set($key, $value)
 * @method bool setex($key, $second, $value)
 * @method bool setnx($key, $value)
 * @method bool incr($key)
 * @method bool incrby($key, $increment)
 * @method bool decr($key)
 * @method bool decrby($key, $decrement)
 * @method bool hdel($key, $field1, ...$otherFields)
 * @method bool hexists($key, $field)
 * @method string hget($key, $field)
 * @method array hgetall($key)
 * @method bool hincrby($key, $field, $increment)
 * @method array hkeys($key)
 * @method int hlen($key)
 * @method array hmget($key, array $hashKeys)
 * @method bool hmset($key, array $hashKeys)
 * @method bool hset($key, $field, $value)
 * @method string lindex($key, $index)
 * @method int llen($key)
 * @method string lpop($key)
 * @method bool lpush($key, ...$value1)
 * @method array lrange($key, $start, $end)
 * @method bool lrem($key, $value, $count)
 * @method string rpop($key)
 * @method bool rpush($key, ...$value1)
 * @method bool|int sadd($key, ...$value1)
 * @method int scard($key)
 * @method array sdiff($key1, ...$otherKeys)
 * @method array sinter($key1, ...$otherKeys)
 * @method array sunion($key1, ...$otherKeys)
 * @method bool sismember($key, $value)
 * @method array smembers($key)
 * @method int srem($key, ...$member1)
 * @method array|bool sscan($key, &$iterator, $pattern = null, $count = 0)
 * @method int zadd($key, $options, $score1, $value1 = null, $score2 = null, $value2 = null, $scoreN = null, $valueN = null)
 * @method int zcard($key)
 * @method int zcount($key, $start, $end)
 * @method float zincrby($key, $value, $member)
 * @method array zrange($key, $start, $end, $withscores = null)
 * @method int|bool zrevrank($key, $member)
 * @method int|bool zrank($key, $member)
 * @method bool publish($channel, $message)
 * @method bool unsubscribe($channel)
 */
class Redis
{
    protected BaseRedis $pool;

    protected \Redis $connection;

    public function __construct($config = null)
    {
        if (!empty($config)) {
            $this->pool = BaseRedis::getInstance($config);
        } else {
            $this->pool = BaseRedis::getInstance();
        }
        $this->connection = $this->pool->getConnection();
    }

    public function __call($name, $arguments)
    {
        try {
            $data = $this->connection->{$name}(...$arguments);
        } catch (\RedisException $e) {
            $this->pool->close(null);
            throw $e;
        }

        return $data;
    }

    public function brPop($keys, $timeout)
    {
        if ($timeout === 0) {
            // TODO Need to optimize...
            $timeout = 99999999999;
        }

        $this->connection->setOption(\Redis::OPT_READ_TIMEOUT, (string)$timeout);

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

        $this->connection->setOption(\Redis::OPT_READ_TIMEOUT, (string)$this->pool->getConfig()['time_out']);

        return $data;
    }

    public function blPop($keys, $timeout)
    {
        if ($timeout === 0) {
            // TODO Need to optimize...
            $timeout = 99999999999;
        }

        $this->connection->setOption(\Redis::OPT_READ_TIMEOUT, (string)$timeout);

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

        $this->connection->setOption(\Redis::OPT_READ_TIMEOUT, (string)$this->pool->getConfig()['time_out']);

        return $data;
    }

    public function subscribe($channels, $callback)
    {
        $this->connection->setOption(\Redis::OPT_READ_TIMEOUT, '-1');

        try {
            $data = $this->connection->subscribe($channels, $callback);
        } catch (\RedisException $e) {
            $this->pool->close(null);
            throw $e;
        }

        $this->connection->setOption(\Redis::OPT_READ_TIMEOUT, (string)$this->pool->getConfig()['time_out']);

        return $data;
    }

    public function brpoplpush($srcKey, $dstKey, $timeout)
    {
        $this->connection->setOption(\Redis::OPT_READ_TIMEOUT, (string)$timeout);

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

        $this->connection->setOption(\Redis::OPT_READ_TIMEOUT, (string)$this->pool->getConfig()['time_out']);

        return $data;
    }

    public function __destruct()
    {
        if (config('app.enable_redis_pool')) {
            $redis_config_key = config('app.redis_config_key');
            $config = config("redis.{$redis_config_key}");
            try {
                $this->connection->select($config['db_index'] ?? 0);
            } catch (\RedisException $e) {
                $this->pool->close(null);
                throw $e;
            }
        }
        $this->pool->close($this->connection);
    }
}
