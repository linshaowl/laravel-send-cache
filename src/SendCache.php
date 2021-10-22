<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\SendCaches;

use Illuminate\Redis\Connections\PhpRedisConnection;
use Lswl\Support\Helper\RedisConnectionHelper;

abstract class SendCache implements SendCacheInterface
{
    /**
     * @var PhpRedisConnection
     */
    protected $connection;

    public function __construct()
    {
        $this->connection = RedisConnectionHelper::getPhpRedis();
    }

    /**
     * {@inheritdoc}
     */
    abstract public function name(): string;

    /**
     * {@inheritdoc}
     */
    public function get(string $key): array
    {
        return $this->connection->hGetAll($key);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, array $data): bool
    {
        return $this->connection->hMSet($key, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function expire(string $key, int $ttl): bool
    {
        return $this->connection->expire($key, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return !!$this->connection->exists($key);
    }

    /**
     * {@inheritdoc}
     */
    public function del(string $key): bool
    {
        return !!$this->connection->del($key);
    }

    /**
     * {@inheritdoc}
     */
    public function lock(string $key, int $ttl): bool
    {
        return $this->connection->command('set', [
            $key,
            1,
            [
                'nx',
                'ex' => $ttl,
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unlock(string $key): bool
    {
        return $this->del($key);
    }
}
