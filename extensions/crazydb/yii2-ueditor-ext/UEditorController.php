<?php
namespace crazydb\ueditor;

use yii;
use yii\web\Controller;

class UEditorController extends Controller{

    public $access;
    public $config;

    public $defaultAction = 'index';


    public function init(){
        parent::init();
        error_reporting(0);
        date_default_timezone_set( 'PRC' );
        header( "Content-Type: text/html; charset=utf-8" );
        //权限判断
        //这里仅判断是否登录
        //更多的权限判断需自行扩展
        //当客户使用低版本IE时，会使用swf上传插件，维持认证状态可以参考文档UEditor「自定义请求参数」部分。
        //http://fex.baidu.com/ueditor/#server-server_param
        //请求config（配置信息）不需要登录权限
        $action = Yii::$app->request->get('action');
        if($action != 'config' && Yii::$app->user->isGuest){
            echo '{"url":"null","fileType":"null","original":"null","state":"Failed:[需要登录]没有上传权限！"}';
            Yii::$app->end(-1);
        }

        //保留UE默认的配置引入方式
        $CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", '', file_get_contents(__DIR__.'/assets/php/config.json')), true);

        if(!is_array($this->config))
            $this->config = array();
        if(!is_array($CONFIG))
            $CONFIG = array();
        $web_root = Yii::$app->request->baseUrl;
        $default = array(
            'imagePathFormat'=>$web_root.'/upload/image/{yyyy}{mm}{dd}/{time}{rand:6}',
            'scrawlPathFormat'=>$web_root.'/upload/image/{yyyy}{mm}{dd}/{time}{rand:6}',
            'snapscreenPathFormat'=>$web_root.'/upload/image/{yyyy}{mm}{dd}/{time}{rand:6}',
            'catcherPathFormat'=>$web_root.'/upload/image/{yyyy}{mm}{dd}/{time}{rand:6}',
            'videoPathFormat'=>$web_root.'/upload/video/{yyyy}{mm}{dd}/{time}{rand:6}',
            'filePathFormat'=>$web_root.'/upload/file/{yyyy}{mm}{dd}/{rand:4}_{filename}',
            'imageManagerListPath'=>$web_root.'/upload/image/',
            'fileManagerListPath'=>$web_root.'/upload/file/',
        );
        $this->config = $this->config + $default + $CONFIG;
    }

    /**
     * 蛋疼的统一后台入口
     */
    public function actionIndex(){
        $actions = array(
            'uploadimage'=>'UploadImage',
            'uploadscrawl'=>'UploadScrawl',
            'uploadvideo'=>'UploadVideo',
            'uploadfile'=>'UploadFile',
            'listimage'=>'ListImage',
            'listfile'=>'ListFile',
            'catchimage'=>'CatchImage',
            'config'=>'Config'
        );
        $action = Yii::$app->request->get('action');

        if(isset($actions[$action])){
            $this->run($actions[$action]);
        }else{
            $this->show(json_encode(array(
                'state'=> '请求地址出错'
            )));
        }
    }

    /**
     * 显示配置信息
     */
    public function actionConfig(){
        $this->show(json_encode($this->config));
    }

    /**
     * 显示最终结果，并终止运行
     * @param $result
     */
    protected function show($result){
        $callback = Yii::$app->request->get('callback');
        if($callback)
            echo "$callback($result)";
        else
            echo $result;
        Yii::$app->end();
    }

}
