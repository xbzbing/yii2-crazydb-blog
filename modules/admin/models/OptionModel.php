<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 * 这是后台Config的model基类
 */
namespace app\modules\admin\models;

use Yii;
use yii\base\Model;

class OptionModel extends Model{
    protected $type;
    /**
     * 简单处理输入字符串
     * 去掉标签，并进行转义
     * @param $attribute
     * @param $params
     */
    public function simplePurify($attribute,$params){
        $this->{$attribute} = htmlspecialchars(strip_tags($this->{$attribute}));
    }

    public function setType($type){
        $this->type = $type;
    }

    public function getType(){
        return $this->type;
    }

    /**
     * 修改option数据表
     * @param string $type
     * @return integer 影响的行数
     */
    public function replace($type){
        $row = 0;
        $command = Yii::$app->db->createCommand("REPLACE INTO {{option}} (type, name, value) VALUES(:type,:name,:value)");
        foreach($this->attributes as $name => $value){
            $row += $command->execute(array(':type'=>$type,':name'=>$name,':value'=>$value));
        }
        return $row/2;
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
 