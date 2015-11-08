<?php

namespace app\models;

use Yii;
use yii\caching\DbDependency;
use yii\db\Query;
use app\components\BaseModel;
use app\components\CMSException;
use app\components\XUtils;

/**
 * This is the model class for table "{{%comment}}".
 *
 * @property string $id 评论ID
 * @property string $pid 文章ID
 * @property string $uid 用户ID
 * @property string $nickname 评论用户姓名
 * @property string $email 电子邮箱
 * @property string $type 回复的类型
 * @property string $replyto 回复目标ID
 * @property string $url URL
 * @property string $ip 用户IP
 * @property string $user_agent UA
 * @property integer $create_time 评论时间
 * @property integer $update_time 更新时间
 * @property string $content 评论内容
 * @property string $status 状态
 *
 * #getter
 * @property array $availableStatus 支持的评论状态
 * @property array $availableType 支持的评论类型
 * @property string $commentType 回复类型
 * @property string $commentStatus 回复状态
 *
 *
 * @property Post $post 所评论文章
 * @property User $user 发表评论的用户
 */
class Comment extends BaseModel
{

    /**
     * @var string 审核未通过
     */
    const STATUS_UNAPPROVED = 'unapproved';
    /**
     * 审核通过
     * @var string 审核通过
     */
    const STATUS_APPROVED = 'approved';
    /**
     * @var string 垃圾留言
     */
    const STATUS_SPAM = 'spam';
    /**
     * 评论的回复类型
     * @var string 回复留言
     */
    const TYPE_REPLYTO = 'replyTo';
    /**
     * 评论的回复类型
     * @var string 回复文章
     */
    const TYPE_REPLY = 'reply';

    /**
     * 回复目标的名字
     * @var string
     */
    public $target_name;

    public $captcha;

    const SCENARIO_COMMENT = 'comment';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%comment}}';
    }


    public function scenarios(){
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_COMMENT] = ['pid', 'nickname', 'email', 'content', 'captcha', 'url'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pid', 'nickname', 'email', 'content'], 'required'],
            [['pid', 'uid', 'replyto'], 'integer'],
            ['content', 'string'],
            ['status', 'default', 'value' => self::STATUS_UNAPPROVED],
            ['status', 'in', 'range' => array_keys(self::getAvailableStatus()), 'message' => '评论状态异常'],
            ['type', 'default', 'value' => self::TYPE_REPLY],
            ['type', 'in', 'range' => array_keys(self::getAvailableType()), 'message' => '评论类型异常'],
            ['nickname', 'string', 'max' => 80],
            ['email', 'email', 'message' => '不是有效的E-mail地址。'],
            ['url', 'url', 'message' => 'URL地址不合法，需要以http或https开头'],
            ['captcha', 'captcha', 'skipOnEmpty' => false, 'on' => self::SCENARIO_COMMENT]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '评论ID',
            'pid' => '文章ID',
            'uid' => '用户ID',
            'nickname' => '用户名称',
            'email' => '电子邮箱',
            'type' => '回复类型',
            'replyto' => '回复目标ID',
            'url' => 'URL',
            'ip' => '用户IP',
            'user_agent' => '浏览器',
            'create_time' => '评论时间',
            'update_time' => '更新时间',
            'content' => '评论内容',
            'status' => '评论审核状态',
            'commentType' => '回复类型',
            'commentStatus' => '回复状态',
            'captcha' => '验证码'
        ];
    }

    /**
     * 支持的评论状态
     * @return array
     */
    public static function getAvailableStatus()
    {
        return [
            self::STATUS_UNAPPROVED => '未审核',
            self::STATUS_APPROVED => '审核通过',
            self::STATUS_SPAM => '垃圾评论'
        ];
    }

    /**
     * 支持的评论状态
     * @return array
     */
    public static function getAvailableType()
    {
        return [
            self::TYPE_REPLY => '评论',
            self::TYPE_REPLYTO => '回复'
        ];
    }

    /**
     * 获得评论状态所对应的名称
     * @param string $status
     * @return string|null
     */
    public static function getStatusName($status)
    {
        $statuses = self::getAvailableStatus();
        return isset($statuses[$status]) ? $statuses[$status] : null;
    }

    public function getCommentStatus()
    {
        return self::getStatusName($this->status);
    }

    /**
     * 获得评论状态所对应的名称
     * @param string $type
     * @return string|null
     */
    public static function getTypeName($type)
    {
        $types = self::getAvailableType();
        return isset($types[$type]) ? $types[$type] : $types[self::TYPE_REPLY];
    }

    public function getCommentType()
    {
        return self::getTypeName($this->type);
    }

    public function getPost()
    {
        return $this->hasOne(Post::className(), ['id' => 'pid']);
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'uid']);
    }


    public function beforeSave($insert)
    {
        if ($this->isNewRecord) {
            $this->ip = XUtils::getClientIP();
            $this->create_time = time();
            $this->user_agent = htmlspecialchars(Yii::$app->request->getUserAgent());
        }

        $this->update_time = time();
        $this->content = XUtils::HTMLPurify($this->content);
        $this->nickname = htmlspecialchars(strip_tags($this->nickname));
        return parent::beforeSave($insert);
    }

    /**
     * 获取最近的 {$limit} 条评论
     * @param int $limit
     * @param bool|false $refresh
     * @param int $size
     * @return array|mixed|null
     */
    public static function getRecentComments($limit = 10, $refresh = false, $size = 40)
    {

        $cache_key = '__recentComments';
        $limit = intval($limit) ? intval($limit) : 10;
        if ($refresh)
            $comments = null;
        else
            $comments = Yii::$app->cache->get($cache_key);

        if (empty($comments)) {

            /* @var self[] $post_comments */
            $post_comments = self::find()->with('post')->limit($limit)->all();
            $ids = $avatars = $urls = array();

            foreach ($post_comments as $comment) {
                $ids[$comment->pid] = $comment->pid;
                if (!isset($avatars[$comment->id]))
                    $avatars[$comment->id] = XUtils::getAvatar($comment->email, $size);
            }

            foreach ($post_comments as $comment) {
                $comments[] = [
                    'id' => $comment->id,
                    'nickname' => $comment->nickname,
                    'website' => $comment->url,
                    'pid' => $comment->pid,
                    'post_url' => $comment->post->getUrl(true),
                    'content' => $comment->content,
                    'create_time' => $comment->create_time,
                    'email' => $comment->email,
                    'avatar' => $avatars[$comment->id],
                    'title' => $comment->post ? $comment->post->title : '',
                ];
            }
            $dp = new DbDependency();
            $dp->sql = (new Query())
                ->select('MAX(update_time)')
                ->from(self::tableName())
                ->createCommand()->rawSql;
            Yii::$app->cache->set(
                $cache_key,
                $comments,
                3600,
                $dp
            );
        }
        return $comments;
    }

}
