<?php

declare(strict_types=1);

namespace system\kernel;

use RuntimeException;
use Swoole\Database\RedisConfig;
use Swoole\Database\RedisPool;

class Redis
{
    protected RedisPool $pools;
    protected \Redis $class;
    protected $config = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'auth' => '',
        'db_index' => 0,
        'time_out' => 1,
        'size' => 64,
    ];

    private static Redis $instance;

    private function __construct(array $config)
    {
        if (config('app', 'enable_redis_pool')) {
            if (empty($this->pools)) {
                $this->config = array_replace_recursive($this->config, $config);
                $this->pools = new RedisPool(
                    (new RedisConfig())
                        ->withHost($this->config['host'])
                        ->withPort($this->config['port'])
                        ->withAuth($this->config['auth'])
                        ->withDbIndex($this->config['db_index'])
                        ->withTimeout($this->config['time_out']),
                    $this->config['size']
                );
            }
        } else {
            $this->config = array_replace_recursive($this->config, $config);
            $redis_config = (new RedisConfig())
                ->withHost($this->config['host'])
                ->withPort($this->config['port'])
                ->withAuth($this->config['auth'])
                ->withDbIndex($this->config['db_index'])
                ->withTimeout($this->config['time_out']);
            $redis = new \Redis();
            $arguments = [
                $redis_config->getHost(),
                $redis_config->getPort(),
            ];
            if ($redis_config->getTimeout() !== 0.0) {
                $arguments[] = $redis_config->getTimeout();
            }
            if ($redis_config->getRetryInterval() !== 0) {
                /* reserved should always be NULL */
                $arguments[] = null;
                $arguments[] = $redis_config->getRetryInterval();
            }
            if ($redis_config->getReadTimeout() !== 0.0) {
                $arguments[] = $redis_config->getReadTimeout();
            }
            $redis->connect(...$arguments);
            if ($redis_config->getAuth()) {
                $redis->auth($redis_config->getAuth());
            }
            if ($redis_config->getDbIndex() !== 0) {
                $redis->select($redis_config->getDbIndex());
            }
            $this->class = $redis;
        }
    }

    public static function getInstance($config = null)
    {
        if (config('app', 'enable_redis_pool')) {
            if (empty(self::$instance)) {
                if (empty($config)) {
                    throw new RuntimeException('redis config empty');
                }
                if (empty($config['size'])) {
                    throw new RuntimeException('the size of redis connection pools cannot be empty');
                }
                self::$instance = new static($config);
            }

            return self::$instance;
        } else {
            $redis_config_key = config('app', 'redis_config_key');
            $config = config('redis', $redis_config_key);
            return new self($config);
        }
    }

    public function getConnection()
    {
        if (config('app', 'enable_redis_pool')) {
            return $this->pools->get();
        } else {
            return $this->class;
        }
    }

    public function close($connection = null)
    {
        if (config('app', 'enable_redis_pool')) {
            $this->pools->put($connection);
        }else{
            $this->class->close();
        }
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
