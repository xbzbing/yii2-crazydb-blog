<?php

namespace app\models;

use app\components\CMSException;
use Yii;
use app\components\BaseModel;
use app\components\XUtils;

/**
 * This is the model class for table "{{%comment}}".
 *
 * @property string $id 评论ID
 * @property string $pid 文章ID
 * @property string $uid 用户ID
 * @property string $author 评论用户姓名
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
 * @property string $ext 保留字段
 *
 * #getter
 * @property array $availableStatus 支持的评论状态
 * @property array $availableType 支持的评论类型
 * @property string $commentType 回复类型
 * @property string $commentStatus 回复状态
 * @property array $extInfo 扩展信息
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
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%comment}}';
    }

	/**
	 * 支持的评论状态
	 * @return array
	 */
	public static function getAvailableStatus(){
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
	public static function getAvailableType(){
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
	public static function getStatusName($status){
		$statuses = self::getAvailableStatus();
		return isset($statuses[$status])?$statuses[$status]:null;
	}
	public function getCommentStatus(){
		return self::getStatusName($this->status);
	}
	/**
	 * 获得评论状态所对应的名称
	 * @param string $type
	 * @return string|null
	 */
	public static function getTypeName($type){
		$types = self::getAvailableType();
		return isset($types[$type])?$types[$type]:$types[self::TYPE_REPLY];
	}
	public function getCommentType(){
		return self::getTypeName($this->type);
	}
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pid', 'author', 'email', 'content'], 'required'],
            [['pid', 'uid', 'replyto', 'create_time', 'update_time'], 'integer'],
            [['content'], 'purify'],
            [['status'],'default', 'value' => self::STATUS_UNAPPROVED ],
            [['status'],'in','range'=>array_keys(self::getAvailableStatus()),'message'=>'评论状态异常'],
            [['type'],'default', 'value' => self::TYPE_REPLY ],
            [['type'],'in','range'=>array_keys(self::getAvailableType()),'message'=>'评论类型异常'],
            [['author'], 'string', 'max' => 80],
            [['email'], 'email', 'message'=>'不是有效的E-mail地址。'],
            [['url'], 'url', 'message'=>'URL地址不合法，需要以http或https开头']
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
            'author' => '用户名称',
            'email' => '电子邮箱',
            'type' => '回复的类型',
            'replyto' => '回复目标ID',
            'url' => 'URL',
            'ip' => '用户IP',
            'user_agent' => '浏览器',
            'create_time' => '评论时间',
            'update_time' => '更新时间',
            'content' => '评论内容',
            'status' => '评论审核状态',
            'ext' => '保留字段',
            'commentType' =>  '回复类型',
            'commentStatus' => '回复状态'
        ];
    }

    public function getPost(){
        return $this->hasOne(Post::className(),['id'=>'pid']);
    }

    public function getUser(){
        return $this->hasOne(User::className(),['id'=>'uid']);
    }

	/**
	 * 获得 message 信息
	 * @return array|bool
	 */
	public function getExtInfo(){
		try{
			if(is_array($this->ext))
				return $this->ext;

			$this->ext = unserialize($this->ext);
		}catch (CMSException $cms){
			return false;
		}
	}

	public function beforeSave($insert){
		if( $this->isNewRecord ){
			$this->ip = XUtils::getClientIP();
			$this->create_time = time();
			$this->user_agent = htmlspecialchars( Yii::$app->request->getUserAgent() );
		}else{
			$this->update_time = time();
			$this->ext = serialize([
				'ip'=>XUtils::getClientIP(),
				'username'=>Yii::$app->user->isGuest?'Guest':Yii::$app->user->username,
			]);
		}
		return parent::beforeSave($insert);
	}
}
