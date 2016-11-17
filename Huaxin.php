<?php
/**
 * Author: liasica
 * Email: magicrolan@qq.com
 * CreateTime: 16/8/4 下午1:04
 */
namespace liasica\chinasms;

use liasica\chinasms\base\BaseChinaSms;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;

class Huaxin extends BaseChinaSms
{
    private $textUrl = 'http://dx.ipyy.net/sms.aspx';
    private $encryptUrl = 'http://dx.ipyy.net/ensms.ashx';
    private $jsonUrl = 'http://dx.ipyy.net/smsJson.aspx';
    /**
     * 返回json
     * @var bool
     */
    public $useJsonUrl = true;
    /**
     * 加密传输, 返回json
     * @var bool
     */
    public $useEncryptUrl = false;
    /**
     * 返回文本
     * @var bool
     */
    public $useTextUrl = false;

    public function init()
    {
        if ($this->useJsonUrl) {
            $this->url = $this->jsonUrl;
        } elseif ($this->useEncryptUrl) {
            $this->url = $this->encryptUrl;
        } elseif ($this->useTextUrl) {
            $this->url = $this->textUrl;
        } elseif (!$this->useJsonUrl && !$this->useTextUrl) {
            throw new InvalidConfigException('发送方式配置错误');
        }
        parent::init();
    }

    /**
     * 设置状态码
     * @return mixed
     */
    function _statusCodes()
    {
        return [
            1    => '操作成功',
            1001 => '参数错误',
            1002 => '用户名为空',
            1003 => '密码为空',
            1004 => '用户名错误',
            1005 => '密码错误',
            1006 => 'IP绑定错误',
            1007 => '帐户已停用',
            1008 => 'UserId参数错误，该值必需要是数字，由供应商提供。',
            1009 => 'Text64参数错误，错误的可能有：不是有效的base64编码，Des解密失败，解析json时出错。',
            1010 => '时间戳错误，可能是格式不对，或是时间偏差太大（应该在5分钟以内）。',
            2001 => '内容为空',
            1103 => '手机号码为空',
            1104 => '扩展错误',
            2015 => '内容太长',
            1106 => '没有发送通道',
            2107 => '敏感词汇',
            1108 => '错误的手机号码',
            1109 => '黑名单的手机号码',
            1110 => '没有通道的手机号码',
            1111 => '额度不足',
            1112 => '没有配置产品',
            2113 => '需要签名',
            2114 => '签名错误',
            3001 => '主题为空',
            9999 => '系统内部错误',
        ];
    }

    /**
     * 解析短信反馈
     * @param       $result
     * @param array $postData
     * @return bool
     */
    function _parseResult($result, array $postData)
    {
        if ($this->useJsonUrl) {
            if (!is_array($result)) {
                throw new InvalidParamException('参数错误');
            } else {
                \Yii::info([
                    'result'   => $result,
                    'postData' => $postData,
                ], __METHOD__);
                return $result['returnstatus'] == 'Success';
            }
        }
    }

    /**
     * 获取手机
     * @param array $postData
     * @return string
     */
    function _getPhone(array $postData)
    {
        return $postData['mobile'];
    }

    /**
     * 发送数据
     * @param  $phone
     * @param  $code
     * @return array
     */
    function _getPostData($phone, $code)
    {
        return [
            'account'  => $this->account,
            'password' => $this->password,
            'mobile'   => $phone,
            'content'  => (strpos($this->msgTpl, '#@code@#') !== false ? str_replace('#@code@#', $code,
                $this->msgTpl) : $code),
            'action'   => 'send',
        ];
    }

    function _getMsgPostData($phone, $msg)
    {
        return [
            'account'  => $this->account,
            'password' => $this->password,
            'mobile'   => $phone,
            'content'  => $msg,
            'action'   => 'send',
        ];
    }
}