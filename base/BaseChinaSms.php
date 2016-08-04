<?php
/**
 * Author: liasica
 * Email: magicrolan@qq.com
 * CreateTime: 16/8/4 下午12:57
 */
namespace liasica\chinasms;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

abstract class BaseChinaSms extends Component
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
     * 默认短信缓存时间
     * @var int
     */
    public $cacheTime = 300;
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
     * 获取缓存
     * @param $name
     * @return mixed
     */
    protected function getCache($name)
    {
        return Yii::$app->cache->get($this->cachePrefix . $name);
    }

    public function init()
    {
        if ($this->account === null) {
            throw new InvalidConfigException('账号不能为空');
        } elseif ($this->password === null) {
            throw new InvalidConfigException('密码不能为空');
        } elseif ($this->url === null) {
            throw new InvalidConfigException('请求地址不能为空');
        } elseif ($this->cachePrefix) {
            throw new InvalidConfigException('缓存前缀不能为空');
        }
    }

    /**
     * 设置缓存
     * @param      $name
     * @param      $value
     * @param null $duration
     * @return bool
     */
    protected function setCache($name, $value, $duration = null)
    {
        if ($duration === null) {
            $duration = $this->cacheTime;
        }

        return Yii::$app->cache->set($this->cachePrefix . $name, $value, $duration);
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
     * @param array $options
     * @return bool|mixed
     */
    public function http($options = [])
    {
        // 设置选项
        $options += [
                        CURLOPT_CONNECTTIMEOUT => 30,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_URL            => $this->url,
                        CURLOPT_TIMEOUT        => $this->second,
                    ] + (stripos($this->url, "https://") !== false ? [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSLVERSION     => CURL_SSLVERSION_TLSv1,
            ] : []) + (count($this->aHeader) > 0 ? [CURLOPT_HTTPHEADER => $this->aHeader] : []);
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
}