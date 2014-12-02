<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 * @date 14-6-9 上午9:54
 */

namespace app\components;
use yii;
use yii\db\ActiveRecord;
use yii\helpers\HtmlPurifier;

class BaseModel extends ActiveRecord{

    /**
     * 匹配指定字符串
     * params中存在type时
     * blank => 只能由汉字、字母、数字以及下划线组成
     * letter => 只能由字母、数字以及下划线组成
     * normal => 不能包含特殊符号(default)
     * 默认为normal
     * @param string $attribute
     * @param array $params
     */
    public function regString($attribute, $params = array()){
        $type = isset($params['type'])?$params['type']:'normal';

        if($type == 'blank'){
            $pattern = '/^[0-9a-zA-Z_\s\x{4e00}-\x{9fa5}]+$/u';
            $message = ' 只能由汉字、字母、数字以及下划线组成。';
        }elseif($type == 'letter'){
            $pattern = '/^[0-9a-zA-Z_]+$/u';
            $message = ' 只能由字母、数字以及下划线组成。';
        }else{
            $pattern = '/^[0-9a-zA-Z_\x{4e00}-\x{9fa5}]+$/u';
            $message = ' 不能包含特殊符号。';
        }

        if(!preg_match($pattern,$this->{$attribute})){
            $this->addError($attribute,$this->getAttributeLabel($attribute).$message);
        }
    }

	/**
	 * 富文本过滤
	 * @param $attribute
	 * @param array $params
	 */
	public function purify($attribute, $params = array()){
		//不做进一步判断params的合法性
		if(isset($params['Attr.AllowedFrameTargets'])){
			$params['Attr.AllowedFrameTargets']  = array('_blank');
		}
		$this->{$attribute} = HTMLPurifier::process($this->{$attribute}, $params);
	}
}