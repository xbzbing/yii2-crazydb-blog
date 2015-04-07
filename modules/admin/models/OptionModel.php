<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 * 这是后台Config的model基类
 * 封装
 */
namespace app\modules\admin\models;

use Yii;
use yii\base\Model;
use app\models\Option;

/**
 * Class OptionModel
 * @package app\modules\admin\models
 * @var array $oldAttributes
 * @var string $type
 */
class OptionModel extends Model{
    protected $type;
    protected $_oldAttributes;
    /**
     * 简单处理输入字符串
     * 去掉标签，并进行转义
     * @param $attribute
     * @param $params
     */
    public function simplePurify($attribute,$params){
        $this->{$attribute} = htmlspecialchars(strip_tags($this->{$attribute}));
    }

    /**
     * 配置类型
     * @param $type
     */
    public function setType($type){
        $this->type = $type;
    }

    public function getType(){
        return $this->type;
    }

    /**
     * 旧数据
     * @param $old
     */
    public function setOldAttributes($old){
        $this->_oldAttributes = is_array($old)?$old:array();
    }

    public function getOldAttributes(){
        return $this->_oldAttributes;
    }

    public function getOldAttribute($name){
        return isset($this->_oldAttributes[$name])?$this->_oldAttributes[$name]:null;
    }
    /**
     * 修改option数据表
     * @param string $type
     * @return integer 影响的行数
     */
    public function replace($type){
        $row = 0;
        foreach($this->attributes as $name => $value){
            if($this->getOldAttribute($name) == $value)
                continue;
            $row += Yii::$app->db->createCommand(
                "REPLACE INTO ".Option::tableName()." (type, name, value) VALUES(:type,:name,:value)",
                [':type'=>$type,':name'=>$name,':value'=>$value]
            )->execute();
        }
        return $row;
    }
    /**
     * 进行自动验证并保存
     * @param $type
     * @return bool|int
     */
    public function save($type){
        if($this->validate())
            return $this->replace($type);
        else
            return false;
    }
}
 