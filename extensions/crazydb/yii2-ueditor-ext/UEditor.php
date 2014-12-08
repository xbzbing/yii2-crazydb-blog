<?php

/**
 * UEditor InputWidget.
 */
namespace crazydb\ueditor;

use yii;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use yii\helpers\Url;

class UEditor extends yii\widgets\InputWidget
{

    /**
     * 生成的ueditor对象的名称，默认为editor。
     * 主要用于同一个页面的多个editor实例的管理。
     * @var string
     */
    public $name = 'editor';

    /**
     * UEditor配置
     * @var array
     */
    public $config = array();


    /**
     * Initializes the widget.
     */
    public function init(){
        parent::init();
        Yii::setAlias('crazydb', '@app/extensions/crazydb');
        Yii::setAlias('@crazydb/ueditor', '@crazydb/yii2-ueditor-ext');
        if (!($this->model instanceof ActiveRecord))
            throw new Exception('必须指定一个AR');

        //注册资源文件
        $asset = UEditorAsset::register($this->getView());


        //常用配置项
        if (empty($this->config['UEDITOR_HOME_URL']))
            $this->config['UEDITOR_HOME_URL'] = $asset->baseUrl . '/';

        if (empty($this->config['serverUrl']))
            $this->config['serverUrl'] = Url::to(['ueditor/index']);

        if (empty($this->config['lang']))
            $this->config['lang'] = strtolower(Yii::$app->language);

        if (empty($this->config['initialFrameHeight']))
            $this->config['initialFrameHeight'] = 400;

        if (empty($this->config['initialFrameWidth']))
            $this->config['initialFrameWidth'] = '100%';

        //扩展默认不直接引入config.js文件，因此需要自定义配置项
        if (empty($this->config['toolbars'])) {
            //这是一个丑陋的二维数组，得到的是一些常用的编辑器按钮。
            $this->config['toolbars'] = [
                ['fullscreen', 'source', 'undo', 'redo', '|', 'customstyle', 'paragraph', 'fontfamily', 'fontsize'],
                ['bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'superscript', 'subscript', 'removeformat',
                    'formatmatch', 'autotypeset', 'blockquote', 'pasteplain', '|',
                    'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', '|',
                    'rowspacingtop', 'rowspacingbottom', 'lineheight', '|',
                    'directionalityltr', 'directionalityrtl', 'indent', '|'
                ],
                ['justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|', 'link', 'unlink', '|',
                    'insertimage', 'emotion', 'scrawl', 'insertvideo', 'music', 'attachment', 'map',
                    'insertcode', 'pagebreak', '|',
                    'horizontal', 'inserttable', '|',
                    'print', 'preview', 'searchreplace', 'help'
                ]
            ];
        }
    }

    /**
     * Runs the widget.
     */
    public function run(){
        $id = Html::getInputId($this->model, $this->attribute);

        $config = json_encode($this->config);

        $script = <<<UEDITOR
    var {$this->name} = UE.getEditor('{$id}',{$config});
    {$this->name}.ready(function(){
        this.addListener( "beforeInsertImage", function ( type, imgObjs ) {
            for(var i=0;i < imgObjs.length;i++){
                imgObjs[i].src = imgObjs[i].src.replace(".thumbnail","");
            }
        });
    });
UEDITOR;

        $this->getView()->registerJs($script);

        return Html::activeTextarea($this->model, $this->attribute);
    }

}