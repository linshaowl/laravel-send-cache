<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\SendCaches;

interface SendCacheInterface
{
    /**
     * 缓存名称
     * @return string
     */
    public function name(): string;

    /**
     * 读取数据
     * @param string $key
     * @return array
     */
    public function get(string $key): array;

    /**
     * 设置数据
     * @param string $key
     * @param array $data
     * @return bool
     */
    public function set(string $key, array $data): bool;

    /**
     * 设置过期时间
     * @param string $key
     * @param int $ttl 秒
     * @return bool
     */
    public function expire(string $key, int $ttl): bool;

    /**
     * 判断是否存在
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool;

    /**
     * 删除指定键数据
     * @param string $key
     * @return bool
     */
    public function del(string $key): bool;

    /**
     * 锁定
     * @param string $key
     * @param int $ttl 秒
     * @return bool
     */
    public function lock(string $key, int $ttl): bool;

    /**
     * 解锁
     * @param string $key
     * @return bool
     */
    public function unlock(string $key): bool;
}
