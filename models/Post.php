<?php

namespace app\models;

use Yii;
use yii\helpers\Url;
use app\components\BaseModel;
use app\components\XUtils;

/**
 * This is the model class for table "{{%post}}".
 *
 * @property string $id 文章ID
 * @property integer $cid 分类ID
 * @property string $author_id 作者ID
 * @property string $author_name 作者昵称
 * @property string $type 文章类型
 * @property string $title 标题
 * @property string $alias 访问别名
 * @property string $excerpt 简介
 * @property string $content 内容
 * @property string $cover 封面图片地址
 * @property string $password 密码
 * @property string $status 状态
 * @property integer $create_time 创建时间
 * @property integer $post_time 发布时间
 * @property integer $update_time 更新时间
 * @property string $tags 标签
 * @property integer $comment_count 评论数量
 * @property integer $view_count 点击数量
 * @property string $options 配置信息
 * @property string $ext_info 附加信息
 *
 * #利用魔术方法获取的属性
 * @property array $availableStatus
 * @property string $postStatus
 * @property array $availableType
 * @property string $postType
 *
 * #relations
 * @property User $author
 * @property Category $category
 * @property Comment[] $comments
 * @property Tag[] $postTags
 */
class Post extends BaseModel{
    /**
     * 文章状态
     * 共四类
     * 已发布、已删除、草稿、隐藏
     * 其中已删除未做相关支持
     */
    const STATUS_PUBLISHED = 'published';
    const STATUS_DRAFT = 'draft';
    const STATUS_DELETED = 'deleted';
    const STATUS_HIDDEN = 'hidden';
    /**
     * 支持的文章类型
     * 目前共三类
     * 文章、相册、产品
     */
    const TYPE_POST = 'post';
    const TYPE_ALBUM = 'album';
    const TYPE_PRODUCT = 'product';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%post}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cid', 'author_id', 'comment_count', 'view_count'], 'integer'],
            [['author_id', 'author_name', 'title', 'content', 'create_time'], 'required'],
            [['excerpt', 'content', 'ext_info'], 'string'],
            [['author_name'], 'string', 'max' => 80],
            [['type'], 'string', 'max' => 20],
            [['title', 'alias', 'cover', 'tags'], 'string', 'max' => 255],
            [['password'], 'string', 'max' => 32],
            [['status'], 'string', 'max' => 50],
            [['options'], 'string', 'max' => 8],
            [['status'], 'in', 'range'=>array_keys(self::getAvailableStatus()), 'message'=>'文章的「状态」错误！'],
            [['type'], 'in' , 'range'=>array_keys(self::getAvailableType()), 'message'=>'文章的「类型」错误！'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '文章ID',
            'cid' => '分类ID',
            'author_id' => '作者ID',
            'author_name' => '作者昵称',
            'type' => '类型',
            'title' => '标题',
            'alias' => '访问别名',
            'excerpt' => '摘要',
            'content' => '正文',
            'cover' => '封面图片地址',
            'password' => '密码',
            'status' => '状态',
            'create_time' => '创建时间',
            'post_time' => '发表时间',
            'update_time' => '更新时间',
            'tags' => '标签',
            'comment_count' => '评论数',
            'view_count' => '浏览数',
            'options' => '配置信息',
            'ext_info' => '附加数据',
            'postType' => '类型',
            'postStatus' => '状态'
        ];
    }

    /**
     * 获得支持的文章状态
     * @return array
     */
    public static function getAvailableStatus(){
        return [
            self::STATUS_DELETED => '已删除',
            self::STATUS_DRAFT => '草稿',
            self::STATUS_PUBLISHED => '已发布',
            self::STATUS_HIDDEN => '隐藏'
        ];
    }
    /**
     * 获得文章类型对应的名称
     * @param $status string
     * @return string|null
     */
    public static function getStatusName($status){
        $statuses = self::getAvailableStatus();
        return isset($statuses[$status])?$statuses[$status]:null;
    }
    public function getPostStatus(){
        return self::getStatusName($this->status);
    }
    /**
     * 获得支持的文章状态
     * @return array
     */
    public static function getAvailableType(){
        return [
            self::TYPE_POST => '文章',
            self::TYPE_ALBUM => '相册',
            self::TYPE_PRODUCT => '产品'
        ];
    }
    /**
     * 获得文章类型对应的名称
     * @param $type string
     * @return string|null
     */
    public static function getTypeName($type){
        $types = self::getAvailableType();
        if(isset($types[$type]))
            return $types[$type];
        else
            return null;
    }

    public function getPostType(){
        return self::getTypeName($this->type);
    }
    /**
     * 自动填写create_time、post_time、update_time
     * 新 Post 自动填写作者ID和作者的昵称
     * 自动填写最终修改人（name和id）
     * @see CActiveRecord::beforeSave()
     */
    public function beforeSave($insert){
        if(!parent::beforeSave($insert))
            return false;

        //创建新Post
        if($insert){
            $this->create_time = $this->update_time = time();
            $this->ext_info = null;
        }
        //编辑状态记录信息
        if($this->scenario=='edit'){
            $this->ext_info = is_array($this->ext_info)?serialize($this->ext_info):null;
            $this->update_time = time();
        }

        //修改保存状态，处理发布时间
        if($this->status!=self::STATUS_PUBLISHED && $this->status!=self::STATUS_HIDDEN)
            $this->post_time = null;
        else{
            if(empty($this->post_time))
                $this->post_time = time();
            elseif(!is_numeric($this->post_time))
                $this->post_time = strtotime($this->post_time);
        }

        if($this->status != self::STATUS_HIDDEN){
            $this->password = null;
        }

        //title安全修正，并生成文章URL别名
        $this->title = htmlspecialchars($this->title);

        if(!$this->alias)
            $this->alias = $this->title;
        $this->alias = str_replace([' ','%'],['-'],trim($this->alias));
        $this->alias = strip_tags($this->alias);
        $this->alias = htmlspecialchars($this->alias);

        //生成并处理excerpt
        if(!$this->excerpt){
            if($this->status == self::STATUS_PUBLISHED){
                $this->excerpt = strip_tags($this->excerpt,'<p><ul><li><strong>');
                $this->excerpt = XUtils::strimwidthWithTag($this->content,0,350,'...');
            }
        }
        $this->excerpt = str_replace(['<p></p>','<p><br /></p>'],'',$this->excerpt);

        //处理封面图片
        if($this->cover){
            //TODO 验证cover
        }else{
            $this->cover = $this->getCoverImage();
        }

        //处理并生成tags
        $tags_array = array_unique(explode(',', str_replace([' ', '，'], ',', $this->tags)));
        foreach($tags_array as $key => $tag){
            if(preg_match('/^[0-9a-zA-Z_\x{4e00}-\x{9fa5}]+$/u',$tag))
                $tags_array[$key] = $tag;
            else
                unset($tags_array[$key]);
        }
        $this->tags = implode(',',$tags_array);

        return true;
    }
    /**
     * 获取文章访问的URL
     * @return null|string
     */
    public function getUrl(){
        if($this->isNewRecord)
            return null;
        if($this->alias)
            return Url::to(['post/alias','name'=>$this->alias]);
        else
            return Url::to(['post/view','id'=>$this->id],true);
    }

    /**
     * 获取文章中第一张图片为封面图片。
     * @return string
     */
    public function getCoverImage(){
        preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $this->content, $matches);
        if(isset($matches[1][0]))
            return $matches[1][0];
        else
            return '';
    }
    /**
     * 获取关联的Post
     * 前一篇或者后一篇
     * @param $relation
     * @param bool $category 是否按同一分类获取上下文
     * @param bool $simple 是否采用简单模式获取上下文
     * @param bool $refresh 是否强制刷新缓存
     * @return Post
     */
    public function getRelatedOne($relation, $category = false,$simple = true,$refresh = false){
        $relations = ['before'=>'<','after'=>'>'];
        $orders = ['before'=>SORT_DESC,'after'=>SORT_ASC];
        $op = null;
        if(isset($relations[$relation]))
            $op = $relations[$relation];
        else
            return null;
        $one = Yii::$app->cache->get("post_{$relation}_".$this->id);
        if($refresh)
            $one = null;
        if($one){
            Yii::trace('从缓存中获取:'.$relation,'Post');
            return $one;
        }else
            Yii::trace('从数据库中查询'.$relation,'Post');
        $post = self::find()->where("post_time{$op}:postTime",[':postTime'=>$this->post_time])
            ->andWhere(['status'=>[self::STATUS_HIDDEN,self::STATUS_PUBLISHED]])
            ->orderBy(['post_time'=>$orders[$relation]]);
        if($simple)
            $post->select('id,title,alias,status');
        if($category)
            $post->andWhere(['cid'=>$this->cid]);

        $one = $post->one();
        Yii::$app->cache->set("post_{$relation}_".$this->id,$one,3600);
        return $one;
    }

    public function getAuthor(){
        return $this->hasOne(User::className(),['id'=>'author_id']);
    }
    public function getCategory(){
        return $this->hasOne(Category::className(),['id'=>'cid']);
    }
    public function getComments(){
        return $this->hasMany(Comment::className(),['pid'=>'id']);
    }
    public function getPostTags(){
        return $this->hasMany(Tag::className(),['pid'=>'id']);
    }
}
