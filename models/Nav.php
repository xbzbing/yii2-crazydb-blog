<?php

namespace app\models;

use Yii;
use app\components\BaseModel;
use yii\caching\DbDependency;
use yii\db\Query;
use yii\helpers\Url;


/**
 * This is the model class for table "{{%nav}}".
 *
 * @property integer $id
 * @property integer $pid
 * @property string $name
 * @property string $url
 * @property integer $route
 * @property integer $sort_order
 * @property string $extra
 * @property integer $create_time
 * @property integer $update_time
 *
 * #magic method
 * @property string $navType
 *
 * # relations
 * @property Nav $parent
 * @property Nav[] $children
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
            [['pid', 'sort_order', 'route'], 'integer'],
            [['name'], 'required'],
            [['pid', 'route'], 'default', 'value' => 0],
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
            'sort_order' => '显示顺序权重',
            'extra' => '附加属性',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
            'navType' => '类型',
            'route' => '系统路由'
        ];
    }

    public function getNavType()
    {
        if ($this->pid > 0)
            return '子菜单';
        else
            return '顶级菜单';
    }

    public function getUrl()
    {
        return $this->route ? [$this->url] : $this->url;
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

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($insert && $this->pid == $this->id) {
            $this->pid = 0;
            $this->save(false);
        }
        if (isset($changedAttributes['pid']) && $this->pid > 0 && empty($changedAttributes['pid'])) {
            //由顶级菜单变成二级菜单
            self::updateAll(['pid' => $this->pid], ['pid' => $this->id]);
        }
    }

    public function afterDelete()
    {
        parent::afterDelete();
        if ($this->pid == 0)
            self::deleteAll(['pid' => $this->id]);
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
        return $this->hasMany(self::className(), ['pid' => 'id'])->orderBy(['sort_order' => SORT_DESC]);
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
            $item_array = self::find()->select('id,name')->where(['pid' => 0])->orderBy(['sort_order' => SORT_DESC])->asArray()->all();
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
            $parent = self::find()->where(['pid' => 0])->orderBy(['sort_order' => SORT_DESC])->all();;
            if (empty($parent))
                return $items;

            foreach ($parent as $node) {
                $items[$node->id] = [
                    'label' => $node->name,
                    'url' => $node->getUrl(),
                ];
                $children = $node->children;
                foreach ($children as $child) {
                    $items[$node->id]['items'] = [
                        'label' => $child->name,
                        'url' => $node->getUrl(),
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
