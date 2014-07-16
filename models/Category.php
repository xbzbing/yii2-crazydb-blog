<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
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
 */
class Category extends ActiveRecord
{
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
            [['name'], 'required'],
            [['desc', 'display', 'seo_description'], 'string'],
            [['parent', 'sort_order'], 'integer'],
            [['name', 'seo_keywords'], 'string', 'max' => 255],
            [['alias', 'seo_title'], 'string', 'max' => 100]
        ];
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
        ];
    }

    /**
     * 保存前检测父类ID是否是当前分类的子类
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert){
        if(!parent::beforeSave($insert)){
            return false;
        }
        if(!$insert){
            if(self::find()->where(['id'=>$this->parent, 'parent'=>$this->id])->exists()){
                $this->addError('parent','不合法的父类ID！');
                return false;
            }
        }
        if(self::find()->where(['parent'=>$this->id])->exists()){
            $this->parent = 0;
        }
        return true;
    }

    /**
     * 保存后检测，分类的父类是否是自己。
     * @param bool $insert
     */
    public function afterSave($insert){

        if($this->parent == $this->id){
            $this->parent = 0;
            $this->save(false);
        }
        parent::afterSave($insert);
    }

    public function getUrl(){
        if($this->isNewRecord)
            return false;
        if($this->alias){
            return Yii::$app->urlManager->createAbsoluteUrl(['category/alias','name'=>str_replace(' ','-',$this->alias)]);
        }else
            return Yii::$app->urlManager->createAbsoluteUrl(['category/view','id'=>$this->id]);
    }
}
