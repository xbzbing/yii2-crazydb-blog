<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 * 网站SEO设置
 */
namespace app\modules\admin\models;

class SeoForm extends OptionModel{

    /**
     * @var string SEO 标题
     */
    public $seo_title;
    /**
     * @var string SEO 描述
     */
    public $seo_description;
    /**
     * @var string SEO 关键字
     */
    public $seo_keywords;

    public function rules(){
        return [
            [['seo_title', 'seo_description', 'seo_keywords'], 'required'],
            [['seo_title', 'seo_description', 'seo_keywords'],'simplePurify'],
            [['seo_keywords'], 'match', 'pattern'=>'/^[\x{4e00}-\x{9fa5}\w\s,]+$/u', 'message'=>'标签格式为：关键字1，Keyword2。仅包含单词字符，用[,](英文逗号)分隔。'],
        ];
    }

    public function attributeLabels(){
        return [
            'seo_title'=>'SEO 标题',
            'seo_description'=>'SEO 描述',
            'seo_keywords'=>'SEO 关键字',
        ];
    }
}