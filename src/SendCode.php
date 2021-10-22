<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\SendCaches;

use Lswl\SendCaches\Exceptions\ErrorCodeInterface;
use Lswl\SendCaches\Exceptions\SendCacheException;

abstract class SendCode
{
    /**
     * 缓存实例
     * @var SendCacheInterface
     */
    protected $cache;

    /**
     * 缓存工具
     * @var SendCodeCacheHandler
     */
    protected $cacheHandler;

    /**
     * 有效期(秒)
     * @var int
     */
    protected $expire = 1800;

    /**
     * 锁定时间(秒)
     * @var int
     */
    protected $lock = 5;

    /**
     * 类型
     * @var string
     */
    protected $type = '';

    /**
     * 接收者
     * @var string
     */
    protected $to;

    /**
     * 验证码
     * @var string
     */
    protected $code;

    /**
     * 使用缓存
     * @var bool
     */
    protected $useCache = true;

    public function __construct(SendCacheInterface $cache)
    {
        $this->cache = $cache;
        $this->cacheHandler = new SendCodeCacheHandler($cache);
    }

    /**
     * 发送操作
     * @return bool
     */
    abstract protected function sendHandler(): bool;

    /**
     * 发送间隔
     * @param array $interval
     * @return $this
     */
    public function interval(array $interval)
    {
        $this->cacheHandler->interval($interval);
        return $this;
    }

    /**
     * 有效期
     * @param int $sec
     * @return $this
     */
    public function expire(int $sec)
    {
        $this->expire = $sec;
        $this->cacheHandler->expire($sec);
        return $this;
    }

    /**
     * 锁定时间
     * @param int $sec
     * @return $this
     */
    public function lock(int $sec)
    {
        $this->lock = $sec;
        return $this;
    }

    /**
     * 类型
     * @param string $type
     * @return $this
     */
    public function type(string $type)
    {
        $this->type = $type;
        $this->cacheHandler->type($type);
        return $this;
    }

    /**
     * 接收者
     * @param string $to
     * @return $this
     */
    public function to(string $to)
    {
        $this->to = $to;
        $this->cacheHandler->to($to);
        return $this;
    }

    /**
     * 验证码
     * @param string $code
     * @return $this
     */
    public function code(string $code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * 使用缓存
     * @param bool $use
     * @return $this
     */
    public function useCache(bool $use)
    {
        $this->useCache = $use;
        return $this;
    }

    /**
     * 发送
     * @param bool $debug
     * @return int 间隔时间
     * @throws SendCacheException
     */
    public function send(bool $debug = false): int
    {
        // 发送验证
        $this->sendValidate();

        // 缓存发送检测
        $this->useCache && $this->cacheHandler->sendCheck();

        // 获取锁定 key
        $lockKey = $this->getLockKey();
        // 验证是否被锁定
        if (!$this->cache->lock($lockKey, $this->lock)) {
            throw new SendCacheException(
                'Send frequently, please try again later',
                ErrorCodeInterface::SEND_FREQUENTLY
            );
        }

        // 发送
        if (!$debug && !$this->sendHandler()) {
            // 解除锁定
            $this->cache->unlock($lockKey);
            throw new SendCacheException('Send failure, please try again later', ErrorCodeInterface::SEND_FAILURE);
        }

        // 设置发送成功缓存
        $this->useCache && $this->cacheHandler->send($this->code);
        // 间隔时间
        $interval = $this->useCache ? $this->cacheHandler->sendInterval() : 0;

        // 解除锁定
        $this->cache->unlock($lockKey);

        return $interval;
    }

    /**
     * 发送验证
     * @throws SendCacheException
     */
    protected function sendValidate()
    {
        if (is_null($this->to)) {
            throw new SendCacheException('Sending failed. Parameter to must', ErrorCodeInterface::PARAMETER_TO_MUST);
        }
        if (is_null($this->code)) {
            throw new SendCacheException(
                'Sending failed. Parameter code must',
                ErrorCodeInterface::PARAMETER_CODE_MUST
            );
        }
    }

    /**
     * 获取锁定密钥
     * @return string
     */
    protected function getLockKey(): string
    {
        return sprintf('%s:lock:%s', $this->cache->name(), md5($this->to));
    }
}
