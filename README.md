## 安装配置

使用以下命令安装：
```
$ composer require lswl/laravel-send-cache
```

## 快速使用

**发送示例:**

```php
/**
1. 实现缓存类 `\Lswl\SendCaches\SendCache` 方法
2. 实现操作类 `\Lswl\SendCaches\SendCode` 方法
3. 调用发送
*/

// 缓存类实现
use Lswl\SendCaches\SendCache;

class EmailSendCache extends SendCache
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'email';
    }
}

// 操作类实现
use Lswl\SendCaches\SendCode;
class EmailSendCode extends SendCode
{
    /**
     * {@inheritdoc}
     */
    protected function sendHandler(): bool
    {
        // 集体发送操作
        return true;
    }
}

// 实例化邮件发送
$email = new EmailSendCode(new EmailSendCode());
// 返回间隔时间,错误会抛出 `SendCacheException` 异常
$interval = $email
    ->to('1@qq.com')
    ->code(123456)
    ->send();
```

**验证示例:**

```php
use Lswl\SendCaches\SendCodeCacheHandler;

// 缓存操作
$cacheHandler = new SendCodeCacheHandler(new EmailSendCache());

// 验证,错误会抛出 `SendCacheException` 异常
$cacheHandler->to('1@qq.com')
    ->verify(123456);

// 验证后使用验证码
$cacheHandler->useCode();
```
