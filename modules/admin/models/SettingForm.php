<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 * 这是后台系统需要的默认配置
 * 如果要添加一项配置
 * 首先要改数据库，然后在这里增加一个public属性，再到setting view增加相应的表单字段
 */
namespace app\modules\admin\models;

class SettingForm extends OptionModel{
    /**
     * @var string 网站名称
     */
    public $site_name;
    /**
     * @var string 管理员邮箱
     */
    public $admin_email;
    /**
     * @var string 网站备案信息
     */
    public $site_icp;
    /**
     * @var string 网站备案信息
     */
    public $theme;
    /**
     * @var string 网站关闭显示
     */
    public $closed_summary;
    /**
     * @var string 网站状态（open/closed）
     */
    public $site_status;
    /**
     * @var string 开放注册（open/closed）
     */
    public $allow_register;
    /**
     * @var string 版权信息
     */
    public $copyright;
    /**
     * @var string 留言功能
     */
    public $allow_comment;
    /**
     * @var string 留言需要审核
     */
    public $need_approve;
    /**
     * @var string 留言时候发送邮件
     * @todo 这个可能要放到留言管理的部分
     */
    public $send_mail_on_comment;

    /**
     * 网站分析代码，包含<script></script>
     * @var string 网站分析代码
     */
    public $site_analyzer;

    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        return [
            ['site_name','required'],
            ['admin_email','email'],
            ['site_analyzer','safe'],//这个地方可能是唯一的XSS。。
            ['site_status, allow_register, need_approve, allow_comment,send_mail_on_comment','in','range'=>['open','closed']],
            ['site_icp, copyright, closed_summary, theme','simplePurify']
        ];
    }


    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels()
    {
        return array(
            'site_name'=>'网站名称',
            'admin_email'=>'管理员邮箱',
            'site_icp'=>'网站备案信息',
            'theme'=>'主题',
            'copyright'=>'版权信息',
            'allow_register'=>'是否开放注册',
            'site_status'=>'网站状态',
            'closed_summary'=>'网站关闭时提示消息',
            'allow_comment'=>'评论功能',
            'need_approve'=>'评论审核',
            'site_analyzer'=>'网站统计代码',
            'send_mail_on_comment'=>'留言时发送邮件'
        );
    }
}