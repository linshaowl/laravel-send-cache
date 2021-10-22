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

class SendCodeCacheHandler
{
    /**
     * @var SendCacheInterface
     */
    protected $cache;

    /**
     * @var string
     */
    protected $codeCacheKey = '%s:code_cache:%s';

    /**
     * @var string
     */
    protected $numCacheKey = '%s:num_cache:%s';

    /**
     * 发送间隔时间
     * @var array
     */
    protected $interval = [
        1 => 60,
        2 => 180,
        3 => 600,
    ];

    /**
     * 有效期(秒)
     * @var int
     */
    protected $expire = 1800;

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

    public function __construct(SendCacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * 发送间隔
     * @param array $interval
     * @return $this
     */
    public function interval(array $interval)
    {
        $this->interval = $interval;
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
        return $this;
    }

    /**
     * 发送检测
     * @throws SendCacheException
     * @return void
     */
    public function sendCheck()
    {
        $data = $this->getNumCache();
        $diff = time() - $data['time'];
        $interval = $this->getInterval($data['num']);

        if ($interval > $diff) {
            $waitInterval = $interval - $diff;
            throw new SendCacheException(
                sprintf(
                    'Send failed, please try again after %d seconds',
                    $waitInterval
                ),
                ErrorCodeInterface::WAIT_INTERVAL,
                [
                    'to' => $this->to,
                    'interval' => $waitInterval,
                ]
            );
        }
    }

    /**
     * 发送
     * @param string $code
     * @return void
     */
    public function send(string $code)
    {
        // 当前时间
        $time = time();
        // 设置code缓存
        $this->setCodeCache($code, $time);
        // 设置次数缓存
        $this->setNumCache($time);
    }

    /**
     * 使用验证码
     * @return bool
     */
    public function useCode(): bool
    {
        // 获取验证码缓存标识
        $key = $this->getCodeCacheKey();
        if (!$this->cache->exists($key)) {
            return true;
        }

        // 获取验证码缓存数据
        $data = $this->getCodeCache();
        if (!empty($data['code'])) {
            $this->cache->del($key);
        }

        return true;
    }

    /**
     * 验证
     * @param string $code
     * @throws SendCacheException
     * @return void
     */
    public function verify(string $code)
    {
        // 获取验证码缓存数据
        $data = $this->getCodeCache();

        if (empty($data['code'])) {
            throw new SendCacheException('Invalid verification code', ErrorCodeInterface::INVALID_CODE);
        } elseif ($data['code'] != $code) {
            throw new SendCacheException('The verification code is not correct', ErrorCodeInterface::CODE_NOT_CORRECT);
        }
    }

    /**
     * 发送间隔
     * @return int
     */
    public function sendInterval(): int
    {
        $data = $this->getNumCache();
        return (int)$this->getInterval($data['num']);
    }

    /**
     * 设置验证码缓存
     * @param string $code
     * @param int $time
     * @return bool
     */
    protected function setCodeCache(string $code, int $time): bool
    {
        // 发送验证码
        $key = $this->getCodeCacheKey();
        $res = $this->cache->set($key, [
            'code' => $code,
            'time' => $time,
        ]);

        // 设置验证码过期时间
        if ($res) {
            $this->cache->expire($key, $this->expire);
        }

        return $res;
    }

    /**
     * 获取验证码缓存
     * @return array
     */
    protected function getCodeCache(): array
    {
        $data = $this->cache->get($this->getCodeCacheKey());
        return !empty($data) ? $data : [
            'code' => 0,
            'time' => 0,
        ];
    }

    /**
     * 设置次数缓存
     * @param int $time
     * @return bool
     */
    protected function setNumCache(int $time): bool
    {
        $key = $this->getNumCacheKey();
        $exists = $this->cache->exists($key);

        // 保存数据
        $data = $this->getNumCache();
        $data['num']++;
        $data['time'] = $time;

        // 设置发送次数
        $res = $this->cache->set($key, $data);

        // 不存在时设置缓存
        if (!$exists && $res) {
            $this->cache->expire($key, $this->getExpireTime());
        }

        return $res;
    }

    /**
     * 获取次数缓存
     * @return array
     */
    protected function getNumCache(): array
    {
        $data = $this->cache->get($this->getNumCacheKey());
        return !empty($data) ? $data : [
            'num' => 0,
            'time' => 0,
        ];
    }

    /**
     * 获取间隔时间
     * @param int $num
     * @return int|mixed
     */
    protected function getInterval(int $num)
    {
        // 当前次数间隔时间存在
        if (!empty($this->interval[$num])) {
            return $this->interval[$num];
        }

        // 判断最大值
        if ($num >= array_search(max($this->interval), $this->interval, true)) {
            return max($this->interval);
        }

        $interval = 60;
        foreach ($this->interval as $k => $v) {
            if ($k > $num) {
                break;
            }
            $interval = $v;
        }
        return $interval;
    }

    /**
     * 获取过期时间
     * @return int
     */
    protected function getExpireTime(): int
    {
        return intval(strtotime(date('Y-m-d 00:00:00', strtotime('+1 day')))) - time();
    }

    /**
     * 获取验证码缓存标识
     * @return string
     */
    protected function getCodeCacheKey(): string
    {
        return sprintf(
            $this->codeCacheKey,
            $this->cache->name(),
            !empty($this->type) ? $this->to . ':' . $this->type : $this->to
        );
    }

    /**
     * 获取发送数量缓存标识
     * @return string
     */
    protected function getNumCacheKey(): string
    {
        return sprintf(
            $this->numCacheKey,
            $this->cache->name(),
            !empty($this->type) ? $this->to . ':' . $this->type : $this->to
        );
    }
}
