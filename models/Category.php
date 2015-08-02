<?php

namespace app\models;

use Yii;
use \app\components\BaseModel;
use yii\helpers\Url;

/**
 * This is the model class for table "{{%category}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $alias
 * @property string $desc
 * @property integer $parent
 * @property string $display
 * @property integer $sort_order
 * @property string $seo_title
 * @property string $seo_keywords
 * @property string $seo_description
 *
 * #用魔术方法获取的属性
 * @property string $url
 * @property string $displayType
 * @property array $availableDisplay
 *
 * #relations
 * @property Category $parentCategory
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
            [['desc', 'seo_description'], 'string'],
            ['display', 'in', 'range' => array_keys(self::getAvailableDisplay()), 'message' => '分类「显示模式」错误！'],
            [['parent', 'sort_order'], 'integer'],
            [['name', 'seo_keywords'], 'string', 'max' => 255],
            [['alias', 'seo_title'], 'string', 'max' => 100],
        ];
    }


    /**
     * 父类
     * @return self
     */
    public function getParentCategory()
    {
        return $this->parent > 0 ? $this->hasOne(self::className(), ['id' => 'parent']) : null;
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
            'id' => '分类ID',
            'name' => '分类名称',
            'alias' => 'URL别名',
            'desc' => '分类介绍',
            'parent' => '分类父ID',
            'display' => '显示模式',
            'sort_order' => '分类显示排序',
            'seo_title' => 'SEO 标题',
            'seo_keywords' => 'SEO 关键字',
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
        if ($this->parent != 0) {
            //插入模式即已有id。且父id不为0
            if (!$insert) {
                //父类ID不能是自身
                if ($this->parent == $this->id) {
                    $this->parent = 0;
                } else {
                    //父类ID不能是自己的子类
                    if (self::find()->where(['id' => $this->parent, 'parent' => $this->id])->exists()) {
                        $this->addError('parent', '不合法的父类ID！');
                        return false;
                    }
                }
            }
            //父类ID不能不存在
            if (self::find()->where(['parent' => $this->id])->exists()) {
                $this->parent = 0;
            }
        }

        //分类名称name，并生成URL别名
        $this->name = htmlspecialchars(strip_tags($this->name));

        if (!$this->alias)
            $this->alias = $this->name;
        else
            $this->alias = htmlspecialchars(strip_tags($this->alias));

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
        if ($insert && $this->parent == $this->id) {
            $this->parent = 0;
            $this->save(false);
        }
        parent::afterSave($insert, $changedAttributes);
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
            return Url::to(['/category/alias', 'name' => str_replace(' ', '-', $this->alias)], true);
        } else
            return Url::to(['/category/view', 'id' => $this->id], true);
    }
}
