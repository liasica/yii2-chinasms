<?php
/**
 * Author: liasica
 * Email: magicrolan@qq.com
 * CreateTime: 16/8/4 下午12:57
 */
namespace liasica\chinasms\base;

use Yii;
use yii\base\InvalidConfigException;

abstract class BaseChinaSms extends Payload
{
    /**
     * 请求URL
     * @var string
     */
    public $url;
    /**
     * 请求账号
     * @var string
     */
    protected $account;
    /**
     * 请求密码
     * @var string
     */
    protected $password;
    /**
     * 缓存前缀
     * @var string
     */
    public $cachePrefix;
    /**
     * 默认短信缓存时间、短信有效期
     * @var int
     */
    public $cacheTime = 300;
    /**
     * 发送时间间隔
     * @var int
     */
    public $sendRate = 120;
    /**
     * 距离下次发送剩余时间
     * @var int
     */
    public $sendRateLimitTime = 0;
    /**
     * http请求超时时间
     * @var int
     */
    public $second = 30;
    /**
     * 请求头
     * @var array
     */
    public $aHeader;
    /**
     * 状态码
     * @var array
     */
    public $statusCodes;
    /**
     * 短信模板
     * @var null
     */
    public $tpl = null;

    /**
     * 设置账号
     * @param string $account
     */
    public function setAccount(string $account)
    {
        $this->account = $account;
    }

    /**
     * 设置密码
     * @param string $password
     */
    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    /**
     * 设置状态码
     */
    public function setStatusCodes()
    {
        $this->statusCodes = $this->_statusCodes();
    }

    /**
     * 设置缓存
     * @param      $key
     * @param      $value
     * @param null $duration
     * @return bool
     */
    protected function setCache($key, $value, $duration = null)
    {
        if ($duration === null) {
            $duration = $this->cacheTime;
        }
        return Yii::$app->cache->set($this->cachePrefix . $key, $value, $duration);
    }

    /**
     * 获取缓存
     * @param $key
     * @return mixed
     */
    public function getCache($key)
    {
        return Yii::$app->cache->get($this->cachePrefix . $key);
    }

    /**
     * 删除缓存
     * @param $key
     */
    public function delCache($key)
    {
        Yii::$app->cache->delete($this->cachePrefix . $key);
    }

    /**
     * 初始化
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        if ($this->account === null) {
            throw new InvalidConfigException('账号不能为空');
        } elseif ($this->password === null) {
            throw new InvalidConfigException('密码不能为空');
        } elseif ($this->url === null) {
            throw new InvalidConfigException('请求地址不能为空');
        } elseif ($this->cachePrefix === null) {
            throw new InvalidConfigException('缓存前缀不能为空');
        }
    }

    /**
     * 组装URL
     * @param       $url
     * @param array $options
     * @return string
     */
    protected function httpBuildQuery($url, array $options)
    {
        $urlArr = explode('/', $this->url);
        if (!empty($options)) {
            $url .= (stripos(end($urlArr), '?') === null ? '&' : '?') . http_build_query($options);
        }
        return $url;
    }

    /**
     * http请求
     * @param string $url
     * @param array  $options
     * @return bool|mixed
     */
    public function http(string $url, $options = [])
    {
        // 设置选项
        $options = [
                       CURLOPT_CONNECTTIMEOUT => 30,
                       CURLOPT_RETURNTRANSFER => true,
                       CURLOPT_URL            => $url,
                       CURLOPT_TIMEOUT        => $this->second,
                   ] + (stripos($this->url, "https://") !== false ? [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSLVERSION     => CURL_SSLVERSION_TLSv1,
            ] : []) + $options + (count($this->aHeader) > 0 ? [CURLOPT_HTTPHEADER => $this->aHeader] : []);
        // curl 初始化
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        //执行并获取HTML文档内容
        $content = curl_exec($ch);
        // 获取网络状态码
        $status = curl_getinfo($ch);
        //释放curl句柄
        curl_close($ch);
        if (isset($status['http_code']) && $status['http_code'] == 200) {
            return json_decode($content, true) ?: $content;
        }
        // 失败记录日志
        Yii::error([
            'url'     => $this->url,
            'result'  => $content,
            'status'  => $status,
            'options' => json_encode($options),
        ], __METHOD__);
        return false;
    }

    /**
     * http post 请求
     * @param array $postData
     * @param array $options
     * @return bool|mixed
     */
    public function httpPost(array $postData, array $options = [])
    {
        $options += [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => $postData,
        ];
        return $this->http($this->url, $options);
    }

    /**
     * http get请求
     * @param array $options
     * @return bool|mixed
     */
    public function httpGet(array $options = [])
    {
        return $this->http($this->httpBuildQuery($this->url, $options));
    }

    /**
     * http raw数据post请求
     * @param string|array $postOptions
     * @param array        $options
     * @return bool|mixed
     */
    public function httpRaw($postOptions, array $options = [])
    {
        $options += [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => is_array($postOptions) ? json_encode($postOptions,
                JSON_UNESCAPED_UNICODE) : $postOptions,
        ];
        return $this->http($this->url, $options);
    }

    /**
     * 比对短信验证码 | 判断是否可以再次发送短信 true为有效短信时间, 本次不能发送
     * @param string $type
     * @param        $phone
     * @param        $code
     * @return bool
     */
    public function validationSms(string $type, $phone, $code = null)
    {
        $key   = $type . $phone;
        $cache = $this->getCache($key);
        if ($cache) {
            if ($code == null) {
                // 若不携带验证码 判断是否发送过处于有效期内的验证码 以及发送频率是否处于限制状态
                return $this->smsExpired($type, $phone) && $this->smsSendRateLimit($type, $phone);
            } else {
                list($_code, $sendTime) = explode(',', $cache);
                if ($this->smsExpired($type, $phone)) {
                    // 判断认证码是否相等
                    if ($_code == $code) {
                        $this->delCache($key);
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    // 短信认证码已失效, 删除缓存
                    $this->delCache($key);
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * 判断短信有效期
     * @param string $type
     * @param        $phone
     * @return bool
     */
    protected function smsExpired(string $type, $phone)
    {
        $key   = $type . $phone;
        $cache = $this->getCache($key);
        if ($cache) {
            list($code, $sendTime) = explode(',', $cache);
            // 比对发送时间
            if ($sendTime + $this->cacheTime < time()) {
                $this->delCache($key);
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 短信频率是否限制状态
     * @param string $type
     * @param        $phone
     * @return bool
     */
    public function smsSendRateLimit(string $type, $phone)
    {
        $key   = $type . $phone;
        $cache = $this->getCache($key);
        if ($cache) {
            list($code, $sendTime) = explode(',', $cache);
            if (time() - $sendTime < $this->sendRate) {
                $this->sendRateLimitTime = $this->sendRate - (time() - $sendTime);
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * 解析短信反馈
     * @param       $result
     * @param array $postData 发送内容
     * @return bool
     */
    abstract function _parseResult($result, array $postData);

    /**
     * 获取手机
     * @param array $postData
     * @return string
     */
    abstract function _getPhone(array $postData);

    /**
     * 设置状态码
     * @return mixed
     */
    abstract function _statusCodes();

    /**
     * 发送数据
     * @param  $phone
     * @param  $code
     * @return array
     */
    abstract function _getPostData($phone, $code);

    /**
     * 发送短信
     * @param string $type
     * @param        $phone
     * @param string $code
     * @param bool   $checkValidSms 检查上次发送的认证码
     * @param array  $options
     * @return bool
     */
    public function smsPostSend(string $type, $phone, $code, $checkValidSms = false, array $options = [])
    {
        $postData = $this->_getPostData($phone, $code);
        if ($checkValidSms && $this->validationSms($type, $phone)) {
            return false;
        }
        $result = $this->httpPost($postData, $options);
        $key    = $type . $phone;
        // 设置缓存
        $this->setCache($key, $code . ',' . time(), $this->cacheTime);
        // 发送完成后解析结果
        return $this->_parseResult($result, $postData);
    }
}
