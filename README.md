[![Latest Stable Version][image-1]][1] [![Total Downloads][image-2]][2] [![Latest Unstable Version][image-3]][3] [![License][image-4]][4] [![Monthly Downloads][image-5]][5] [![Daily Downloads][image-6]][6] [![Daily Downloads][image-7]][7]

[1]:	https://packagist.org/packages/liasica/yii2-chinasms
[2]:	https://packagist.org/packages/liasica/yii2-chinasms
[3]:	https://packagist.org/packages/liasica/yii2-chinasms
[4]:	https://packagist.org/packages/liasica/yii2-chinasms
[5]:	https://packagist.org/packages/liasica/yii2-chinasms
[6]:	https://packagist.org/packages/liasica/yii2-chinasms
[7]:    https://packagist.org/packages/liasica/yii2-chinasms

[image-1]:	https://poser.pugx.org/liasica/yii2-chinasms/v/stable
[image-2]:	https://poser.pugx.org/liasica/yii2-chinasms/downloads
[image-3]:	https://poser.pugx.org/liasica/yii2-chinasms/v/unstable
[image-4]:	https://poser.pugx.org/liasica/yii2-chinasms/license
[image-5]:	https://poser.pugx.org/liasica/yii2-chinasms/d/monthly
[image-6]:	https://poser.pugx.org/liasica/yii2-chinasms/d/daily
[image-7]:  https://poser.pugx.org/liasica/yii2-chinasms/composerlock

安装
---
`composer update "liasica\yii2-chinasms:dev-master"`

使用方法
----

### 华信

- 配置
```php
'huaxin' => [
    'class'       => 'liasica\chinasms\Huaxin',
    'account'     => '账号',
    'password'    => '发送密码',
    'cachePrefix' => 'cache_chinasms_huaxin_',
    'useJsonUrl'  => true,
]
```

- 使用

```php
$huaxin = \Yii::$app->huaxin;
$ret    = $huaxin->smsPostSend($sms_type, $phone, $code);
$limit  = $huaxin->smsSendRateLimit($sms_type, $phone);
var_dump($ret);
var_dump($limit);
```

```php
$huaxin = \Yii::$app->huaxin;
$ret    = $huaxin->smsPostSend($sms_type, $phone, $code, true);
var_dump($ret);
```
