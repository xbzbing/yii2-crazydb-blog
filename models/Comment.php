<?php

namespace app\models;

use Yii;
use \yii\db\ActiveRecord;
use yii\web\User;

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
 * @property integer $create_time 评论时间
 * @property integer $update_time 更新时间
 * @property string $content 评论内容
 * @property string $status 状态
 * @property string $ext 保留字段
 */
class Comment extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%comment}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pid', 'author', 'email', 'ip', 'create_time', 'content', 'ext'], 'required'],
            [['pid', 'uid', 'replyto', 'create_time', 'update_time'], 'integer'],
            [['type', 'content', 'status', 'ext'], 'string'],
            [['author'], 'string', 'max' => 80],
            [['email', 'ip'], 'string', 'max' => 100],
            [['url'], 'string', 'max' => 255]
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
            'author' => '评论用户姓名',
            'email' => '电子邮箱',
            'type' => '回复的类型',
            'replyto' => '回复目标ID',
            'url' => 'URL',
            'ip' => '用户IP',
            'create_time' => '评论时间',
            'update_time' => '更新时间',
            'content' => '评论内容',
            'status' => '评论审核状态',
            'ext' => '保留字段',
        ];
    }

    public function getPost(){
        return $this->hasOne(Post::className(),['id'=>'pid']);
    }

    public function getUser(){
        return $this->hasOne(User::className(),['id'=>'uid']);
    }
}
