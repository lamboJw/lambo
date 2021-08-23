<?php

declare(strict_types=1);
/**
 * 根据配置，生成连接池或PDO实例
 */

namespace system\kernel;

use RuntimeException;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;

class PDO
{
    protected $pools;
    protected \PDO $class;
    /**
     * @var array
     */
    protected $config = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'test',
        'username' => 'root',
        'password' => 'root',
        'charset' => 'utf8mb4',
        'unixSocket' => null,
        'options' => [],
        'size' => 64,
    ];

    private static array $instance;

    private function __construct(array $config)
    {
        if (config('app.enable_mysql_pool')) {
            if (empty($this->pools)) {
                $this->config = array_replace_recursive($this->config, $config);
                $this->pools = new PDOPool(
                    (new PDOConfig())
                        ->withHost($this->config['host'])
                        ->withPort($this->config['port'])
                        ->withUnixSocket($this->config['unixSocket'])
                        ->withDbName($this->config['database'])
                        ->withCharset($this->config['charset'])
                        ->withUsername($this->config['username'])
                        ->withPassword($this->config['password'])
                        ->withOptions($this->config['options']),
                    $this->config['size']
                );
            }
        } else {
            $this->config = array_replace_recursive($this->config, $config);
            $pdo_config = (new PDOConfig())
                ->withHost($this->config['host'])
                ->withPort($this->config['port'])
                ->withUnixSocket($this->config['unixSocket'])
                ->withDbName($this->config['database'])
                ->withCharset($this->config['charset'])
                ->withUsername($this->config['username'])
                ->withPassword($this->config['password'])
                ->withOptions($this->config['options']);
            $this->class = new \PDO(
                "{$pdo_config->getDriver()}:" .
                (
                $pdo_config->hasUnixSocket() ?
                    "unix_socket={$pdo_config->getUnixSocket()};" :
                    "host={$pdo_config->getHost()};" . "port={$pdo_config->getPort()};"
                ) .
                "dbname={$pdo_config->getDbname()};" .
                "charset={$pdo_config->getCharset()}",
                $pdo_config->getUsername(),
                $pdo_config->getPassword(),
                $pdo_config->getOptions()
            );
        }
    }

    public static function getInstance($db, $config = null)
    {
        if (config('app.enable_mysql_pool')) {
            if (empty(self::$instance[$db])) {
                if (empty($config)) {
                    throw new RuntimeException('pdo config empty');
                }
                if (empty($config['size'])) {
                    throw new RuntimeException('the size of database connection pools cannot be empty');
                }
                self::$instance[$db] = new static($config);
            }
            return self::$instance[$db];
        } else {
            $config = config("database.{$db}", []);
            return new self($config);
        }
    }

    public function getConnection()
    {
        if (config('app.enable_mysql_pool')) {
            return $this->pools->get();
        } else {
            return $this->class;
        }
    }

    public function close($connection = null)
    {
        if (config('app.enable_mysql_pool')) {
            $this->pools->put($connection);
        }
    }
}
