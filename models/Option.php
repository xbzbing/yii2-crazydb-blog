<?php

namespace app\models;

use Yii;
use yii\helpers\Html;
use app\components\BaseModel;


/**
 * This is the model class for table "{{%option}}".
 *
 * @property string $type
 * @property string $name
 * @property string $value
 * @property string $description
 */
class Option extends BaseModel
{
    /**
     * sys类型，系统配置
     */
    const TYPE_SYS = 'sys';
    /**
     * seo类型，seo设置
     */
    const TYPE_SEO = 'seo';

    /**
     * @var string 网站名称
     */
    const SITE_NAME = 'site_name';
    /**
     * @var string 管理员邮箱
     */
    const ADMIN_EMAIL = 'admin_email';
    /**
     * @var string 网站备案信息
     */
    const SITE_IPC = 'site_icp';
    /**
     * @var string 主题
     */
    const THEME = 'theme';
    /**
     * @var string 网站关闭显示
     */
    const CLOSE_SUMMARY = 'closed_summary';
    /**
     * @var string 网站状态（open/closed）
     */
    const SITE_STATUS = 'site_status';
    /**
     * @var string 开放注册（open/closed）
     */
    const ALLOW_REGISTER = 'allow_register';
    /**
     * @var string 版权信息
     */
    const COPYRIGHT = 'copyright';
    /**
     * @var string 留言功能
     */
    const ALLOW_COMMENT = 'allow_comment';
    /**
     * @var string 留言需要审核
     */
    const AUDIT_ON_COMMENT = 'need_approve';
    /**
     * @var string 留言时候发送邮件
     */
    const MAIL_ON_COMMENT = 'send_mail_on_comment';

    /**
     * 网站分析代码，包含<script></script>
     * @var string 网站分析代码
     */
    const SITE_ANALYZER =  'site_analyzer';

    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%option}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'type'], 'required'],
            ['value', 'string'],
            ['type', 'string', 'max' => 20],
            ['name', 'string', 'max' => 50],
            ['name', 'regString', 'type' => self::REG_BLANK],
            ['type', 'regString', 'type' => self::REG_LETTER],
            ['description', 'string', 'max' => 255],
        ];
    }

    function beforeSave($insert)
    {
        /**
         * 这里先做 encode，特殊的自行 decode
         */
        $this->description = Html::encode($this->description);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'type' => '配置类型',
            'name' => '配置名称',
            'value' => '值',
            'description' => '描述',
        ];
    }
}
