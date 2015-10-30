<?php

namespace app\models;

use Yii;
use app\components\BaseModel;
use yii\helpers\Url;
use yii\caching\DbDependency;
use yii\db\Query;

/**
 * This is the model class for table "{{%category}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $alias
 * @property string $desc
 * @property integer $pid
 * @property string $display
 * @property integer $sort_order
 * @property string $keywords
 * @property integer $update_time
 *
 * #用魔术方法获取的属性
 * @property string $url
 * @property string $displayType
 * @property array $availableDisplay
 *
 * #relations
 * @property Category $parent
 * @property Category[] $children
 * @property Post[] $posts
 * @property Post[] $allPosts
 */
class Category extends BaseModel
{

    const DISPLAY_LIST = 'list';
    const DISPLAY_PAGE = 'page';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%category}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['name', 'required'],
            [['desc'], 'string'],
            ['display', 'in', 'range' => array_keys(self::getAvailableDisplay()), 'message' => '分类「显示模式」错误！'],
            [['pid', 'sort_order'], 'integer'],
            [['name', 'keywords'], 'string', 'max' => 255],
            [['alias'], 'string', 'max' => 100],
        ];
    }


    /**
     * 父类
     * @return self
     */
    public function getParent()
    {
        return $this->pid > 0 ? $this->hasOne(self::className(), ['id' => 'pid']) : null;
    }

    public function getChildren(){
        return $this->hasMany(self::className(), ['pid' => 'id'])->orderBy(['sort_order' => SORT_DESC]);
    }

    public function getAllPosts()
    {
        return $this->hasMany(Post::className(), ['cid' => 'id']);
    }

    public function getPosts()
    {
        return $this->hasMany(Post::className(), ['cid' => 'id', 'status' => [Post::STATUS_PUBLISHED, Post::STATUS_HIDDEN]]);
    }

    public static function getAvailableDisplay()
    {
        return [
            self::DISPLAY_LIST => '列表',
            self::DISPLAY_PAGE => '页面'
        ];
    }

    public static function getDisplayName($display)
    {
        $type = self::getAvailableDisplay();
        return isset($type[$display]) ? $type[$display] : null;
    }

    public function getDisplayType()
    {
        return self::getDisplayName($this->display);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '分类 ID',
            'name' => '分类名称',
            'alias' => 'URL 别名',
            'desc' => '分类介绍',
            'pid' => '分类父',
            'display' => '显示模式',
            'sort_order' => '显示顺序权重',
            'seo_title' => 'SEO 标题',
            'keywords' => 'SEO 关键字',
            'seo_description' => 'SEO 描述',
            'displayType' => '显示模式'
        ];
    }

    /**
     * 保存前检测父类ID是否是当前分类的子类
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($this->pid != 0) {
            //插入模式即已有id。且父id不为0
            if (!$insert) {
                //父类ID不能是自身
                if ($this->pid == $this->id) {
                    $this->pid = 0;
                } else {
                    //父类ID不能是自己的子类
                    if (self::find()->where(['id' => $this->pid, 'pid' => $this->id])->exists()) {
                        $this->addError('pid', '不合法的父类ID！');
                        return false;
                    }
                }
            }
            //父类ID不能不存在
            if (self::find()->where(['pid' => $this->id])->exists()) {
                $this->pid = 0;
            }
        }

        //分类名称name，并生成URL别名
        $this->name = htmlspecialchars(strip_tags($this->name));

        if (!$this->alias)
            $this->alias = $this->name;
        else
            $this->alias = htmlspecialchars(strip_tags($this->alias));

        //alias 唯一性校验
        if (self::find()->where(['alias' => $this->alias])->exists()){
            $this->addError('alias', '访问别名不能重复!');
            return false;
        }

        $this->update_time = time();
        return true;
    }

    /**
     * 保存后检测，分类的父类是否是自己。
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {

        //防止预测ID插入脏数据
        if ($insert && $this->pid == $this->id) {
            $this->pid = 0;
            $this->save(false);
        }
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * 删除后将自分类提升至自己的父分类
     */
    public function afterDelete()
    {
        parent::afterDelete();
        self::updateAll(['pid' => $this->pid], ['pid' => $this->id]);
    }
    /**
     * 获取访问URL
     * @return string
     */
    public function getUrl()
    {
        if ($this->isNewRecord)
            return false;
        if ($this->alias) {
            return Url::to(['/category/alias', 'alias' => $this->alias], true);
        } else
            return Url::to(['/category/view', 'id' => $this->id], true);
    }

    /**
     * 获取所有的文章分类。
     * @param bool $refresh 强制刷新
     * @return array
     */
    public static function getAllCategories($refresh = false)
    {
        $cache_key = '__categories';
        if ($refresh)
            $items = [];
        else
            $items = Yii::$app->cache->get($cache_key);

        if (empty($items)) {
            $item_array = self::find()->select('id,name')->asArray()->all();
            if (empty($item_array))
                return [];
            foreach ($item_array as $item) {
                $items[$item['id']] = $item['name'];
            }
            $dp = new DbDependency();
            $dp->sql = (new Query())
                ->select('MAX(update_time)')
                ->from(self::tableName())
                ->createCommand()->rawSql;
            Yii::$app->cache->set(
                $cache_key,
                $items,
                3600,
                $dp
            );
        }
        return $items;
    }
}
