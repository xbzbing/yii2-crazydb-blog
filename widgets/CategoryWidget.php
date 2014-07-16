<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 * @date 14-6-9 上午12:10
 */

namespace app\widgets;

use Yii;
use yii\bootstrap;
use yii\helpers\Html;
use app\models\Category;



class CategoryWidget extends bootstrap\Widget{

    public $parent = 0;

    public $refresh = false;

    private $_category;

    public function init(){
        $this->parent = abs(intval($this->parent));
        if($this->refresh){
            $this->_category = null;
        }else{
            $this->_category = Yii::$app->cache->get('widget_category_'.$this->parent);
        }
        //@todo 使用array，并仅保留需要字段
        if(empty($this->_category)){
            $this->_category = Category::findAll(['parent'=>$this->parent]);
            Yii::$app->cache->set('widget_category_'.$this->parent,$this->_category);
        }
    }

    public function run(){
        foreach($this->_category as $category){
            echo Html::a($category->name,$category->getUrl());
        }
    }

}