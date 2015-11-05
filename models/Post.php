<?php

namespace app\models;

use Yii;
use yii\helpers\Url;
use yii\caching\DbDependency;
use yii\db\Query;
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
 * @property string $ext_info 附加信息
 * @property integer $is_top 是否置顶
 *
 * #利用魔术方法获取的属性
 * @property array $availableStatus 支持的文章状态
 * @property string $postStatus 文章状态
 * @property array $availableType 支持的文章类型
 * @property string $postType 文章类型
 * @property string $url 访问地址
 * @property string $postCategory 文章分类
 *
 * #relations
 * @property User $author 作者
 * @property Category $category 分类
 * @property Comment[] $comments 评论s
 * @property Comment[] $allComments 全部评论s
 * @property Tag[] $postTags 标签s
 */
class Post extends BaseModel
{
    const SCENARIO_EDIT = 'edit';
    const SCENARIO_MANAGE = 'manage';
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


    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_EDIT] = ['cid', 'author_name', 'title', 'content', 'excerpt', 'type', 'alias', 'cover', 'password', 'status', 'tags'];
        $scenarios[self::SCENARIO_MANAGE] = $scenarios[self::SCENARIO_EDIT] + ['author_id', 'view_count'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cid', 'author_id', 'view_count'], 'integer'],
            [['author_id', 'title', 'content'], 'required'],
            [['excerpt', 'content', 'ext_info'], 'string'],
            [['author_name'], 'string', 'max' => 80],
            [['type'], 'string', 'max' => 20],
            [['title', 'alias', 'cover', 'tags'], 'string', 'max' => 255],
            [['password'], 'string', 'max' => 32],
            [['content', 'excerpt'], 'purify'],
            [['status'], 'default', 'value' => self::STATUS_PUBLISHED],
            [['status'], 'in', 'range' => array_keys(self::getAvailableStatus()), 'message' => '文章的「状态」错误！'],
            [['type'], 'default', 'value' => self::TYPE_POST],
            [['type'], 'in', 'range' => array_keys(self::getAvailableType()), 'message' => '文章的「类型」错误！'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '文章ID',
            'cid' => '分类',
            'author_id' => '作者',
            'author_name' => '笔名',
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
            'ext_info' => '附加数据',
            'postType' => '类型',
            'postStatus' => '状态',
            'is_top' => '置顶'
        ];
    }

    /**
     * 获得支持的文章状态
     * @return array
     */
    public static function getAvailableStatus()
    {
        return [
            self::STATUS_PUBLISHED => '已发布',
            self::STATUS_DRAFT => '存草稿',
            self::STATUS_HIDDEN => '加锁发布',
            self::STATUS_DELETED => '已删除',
        ];
    }

    /**
     * 获得文章状态对应的名称
     * @param $status string
     * @return string|null
     */
    public static function getStatusName($status)
    {
        $statuses = self::getAvailableStatus();
        return isset($statuses[$status]) ? $statuses[$status] : null;
    }

    public function getPostStatus()
    {
        return self::getStatusName($this->status);
    }

    /**
     * 获得支持的文章状态
     * @return array
     */
    public static function getAvailableType()
    {
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
    public static function getTypeName($type)
    {
        $types = self::getAvailableType();
        if (isset($types[$type]))
            return $types[$type];
        else
            return null;
    }

    /**
     * 获取文章类型
     * @return null|string
     */
    public function getPostType()
    {
        return self::getTypeName($this->type);
    }

    public function getIsTop()
    {
        return $this->is_top > 0;
    }

    /**
     * 获得文章所属分类
     * @return string|null
     */
    public function getPostCategory()
    {
        $categories = Category::getAllCategories();
        return isset($categories[$this->cid]) ? $categories[$this->cid] : null;
    }

    /**
     * 自动填写create_time、post_time、update_time
     * 新 Post 自动填写作者ID和作者的昵称
     * 自动填写最终修改人（name和id）
     * @see CActiveRecord::beforeSave()
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert))
            return false;

        $this->comment_count = intval($this->comment_count);

        //创建新Post
        if ($insert) {
            $this->create_time = $this->update_time = time();
            $this->ext_info = null;
        }
        //编辑状态记录信息
        if (in_array($this->scenario, [self::SCENARIO_EDIT, self::SCENARIO_MANAGE])) {
            $this->ext_info = is_array($this->ext_info) ? serialize($this->ext_info) : null;
            $this->update_time = time();
        }

        //修改保存状态，处理发布时间
        if ($this->status != self::STATUS_PUBLISHED && $this->status != self::STATUS_HIDDEN)
            $this->post_time = null;
        else {
            if (empty($this->post_time))
                $this->post_time = time();
            elseif (!is_numeric($this->post_time))
                $this->post_time = strtotime($this->post_time);
        }

        if ($this->status != self::STATUS_HIDDEN) {
            $this->password = null;
        }

        //title安全修正，并生成文章URL别名
        $this->title = htmlspecialchars($this->title);

        if (!$this->alias)
            $this->alias = $this->title;
        $this->alias = str_replace([' ', '%', '/', '\\'], ['-'], trim($this->alias));
        $this->alias = strip_tags($this->alias);
        $this->alias = htmlspecialchars($this->alias);

        //alias唯一性校验
        if (self::find()->where(['alias' => $this->alias])->exists()) {
            $this->addError('alias', '访问别名不能重复!');
            return false;
        }

        //生成并处理excerpt
        if (!$this->excerpt) {
            if ($this->status == self::STATUS_PUBLISHED) {
                $this->excerpt = strip_tags($this->excerpt, '<p><ul><li><strong>');
                $this->excerpt = XUtils::strimwidthWithTag($this->content, 0, 350, '...');
            }
        }
        $this->excerpt = str_replace(['<p></p>', '<p><br /></p>'], '', $this->excerpt);

        //处理封面图片
        if ($this->cover) {
            //TODO 验证cover
        } else {
            $this->cover = $this->getCoverImage();
        }

        //处理并生成tags
        $tags_array = array_unique(explode(',', str_replace([' ', '，'], ',', $this->tags)));
        $tag_count = 0;
        foreach ($tags_array as $key => $tag) {
            if ($tag_count <= 5 && preg_match('/^[0-9a-zA-Z_\x{4e00}-\x{9fa5}]+$/u', $tag)) {
                $tags_array[$key] = $tag;
                $tag_count++;
            } else
                unset($tags_array[$key]);
        }
        $this->tags = implode(',', $tags_array);

        return true;
    }

    /**
     * 保存后更新tags
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        if (!empty($changedAttributes['tags']))
            Tag::post2tags($this->tags, $this->id, $this->cid);

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * 删除后更新Tag表
     */
    public function afterDelete()
    {
        Tag::deleteAll(['pid' => $this->id]);

        parent::afterDelete();
    }

    /**
     * 获取文章访问的URL
     * @param bool $schema
     * @return null|string
     */
    public function getUrl($schema = false)
    {
        if ($this->isNewRecord)
            return null;
        if ($this->alias)
            return Url::to(['/post/show', 'name' => $this->alias], $schema);
        else
            return Url::to(['/post/view', 'id' => $this->id], $schema);
    }

    /**
     * 获取文章中第一张图片为封面图片。
     * @return string
     */
    public function getCoverImage()
    {
        preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $this->content, $matches);
        if (isset($matches[1][0]))
            return $matches[1][0];
        else
            return null;
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
    public function getRelatedOne($relation, $category = false, $simple = true, $refresh = false)
    {
        $relations = ['before' => '<', 'after' => '>'];
        $orders = ['before' => SORT_DESC, 'after' => SORT_ASC];
        if ($simple)
            $cache_key = "simple_post_{$relation}_{$this->id}";
        else
            $cache_key = "all_post_{$relation}_{$this->id}";
        $op = null;
        if (isset($relations[$relation]))
            $op = $relations[$relation];
        else
            return null;
        $one = Yii::$app->cache->get($cache_key);
        if ($refresh)
            $one = null;
        if ($one) {
            Yii::trace('从缓存中获取:' . $relation, 'Post');
            return $one;
        } else
            Yii::trace('从数据库中查询' . $relation, 'Post');
        $post = self::find()->where([$op, 'post_time', $this->post_time])
            ->andWhere(['status' => [self::STATUS_HIDDEN, self::STATUS_PUBLISHED]])
            ->orderBy(['post_time' => $orders[$relation]]);
        if ($simple)
            $post->select('id,title,alias,status');
        if ($category)
            $post->andWhere(['cid' => $this->cid]);

        $one = $post->one();

        $dp = new DbDependency();
        $dp->sql = (new Query())
            ->select('MAX(update_time)')
            ->from(self::tableName())
            ->createCommand()->rawSql;
        Yii::$app->cache->set(
            $cache_key,
            $one,
            3600,
            $dp
        );
        return $one;
    }

    # Relationships

    public function getAuthor()
    {
        return $this->hasOne(User::className(), ['id' => 'author_id']);
    }

    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'cid']);
    }

    public function getComments()
    {
        return $this->hasMany(Comment::className(), ['pid' => 'id'])->orderBy(['create_time' => SORT_ASC]);
    }

    public function getAllComments()
    {
        return $this->hasMany(Comment::className(), ['pid' => 'id'])
            ->onCondition(['status' => Comment::STATUS_APPROVED])
            ->orderBy(['create_time' => SORT_ASC]);
    }

    public function getPostTags()
    {
        return $this->hasMany(Tag::className(), ['pid' => 'id']);
    }
}