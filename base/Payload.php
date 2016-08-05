<?php
/**
 * Author: liasica
 * Email: magicrolan@qq.com
 * CreateTime: 16/8/4 下午8:29
 */
namespace liasica\chinasms\base;

use yii\base\Component;

abstract class Payload extends Component
{
    /**
     * 短信模板
     * @var string
     */
    public $msgTpl = '';
}