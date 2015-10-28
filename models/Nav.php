<?php

namespace app\models;

use Yii;
use app\components\BaseModel;
use yii\caching\DbDependency;
use yii\db\Query;


/**
 * This is the model class for table "{{%nav}}".
 *
 * @property integer $id
 * @property integer $pid
 * @property string $name
 * @property string $url
 * @property integer $order
 * @property string $extra
 * @property integer $create_time
 * @property integer $update_time
 *
 * @property Nav parent
 * @property Nav[] children
 */
class Nav extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%nav}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pid', 'order'], 'integer'],
            [['name'], 'required'],
            ['pid', 'default', 'value' => 0],
            [['name', 'url', 'extra'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pid' => '父菜单',
            'name' => '名字',
            'url' => '网址',
            'order' => '显示顺序',
            'extra' => '附加属性',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert))
            return false;
        if ($insert)
            $this->create_time = time();

        $this->update_time = time();
        return true;
    }

    /**
     * 关联属性 获取 父节点
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(self::className(), ['id' => 'pid']);
    }
    /**
     * 关联属性 获取 子节点
     * @return \yii\db\ActiveQuery
     */
    public function getChildren()
    {
        return $this->hasMany(self::className(), ['pid' => 'id'])->orderBy(['order' => SORT_DESC]);
    }

    /**
     * 获取父类导航。
     * @param bool $refresh 强制刷新
     * @return array
     */
    public static function getParentNav($refresh = false)
    {
        $cache_key = '__parent_nav';
        if ($refresh)
            $items = [];
        else
            $items = Yii::$app->cache->get($cache_key);

        if (empty($items)) {
            $item_array = self::find()->select('id,name')->where(['pid' => 0])->orderBy(['order' => SORT_DESC])->asArray()->all();
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

    /**
     * 获取导航树。
     * @param bool $refresh 强制刷新
     * @return array
     */
    public static function getNavTree($refresh = false)
    {
        $cache_key = '__nav_tree';
        if ($refresh)
            $items = [];
        else
            $items = Yii::$app->cache->get($cache_key);

        if (empty($items)) {
            /* @var self[] $parent */
            $parent = self::find()->where(['pid' => 0])->orderBy(['order' => SORT_DESC])->all();;
            if (empty($parent))
                return $items;

            foreach ($parent as $node) {
                $items[$node->id] = [
                    'label' => $node->name,
                    'url' => $node->url,
                    'items' => []
                ];
                $children = $node->children;
                foreach ($children as $child) {
                    $items[$node->id]['items'] = [
                        'label' => $child->name,
                        'url' => $node->url,
                    ];
                }
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
