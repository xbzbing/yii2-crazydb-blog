<?php
namespace crazydb\ueditor;

use yii;
use yii\base\Exception;
use yii\imagine\Image;
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
        if(file_exists(__DIR__.'/assets/php/Uploader.class.php'))
            require(__DIR__.'/assets/php/Uploader.class.php');
        else
            throw new Exception('缺少上传类php/Uploader.class.php文件');
    }

    /**
     * 蛋疼的统一后台入口
     */
    public function actionIndex(){
        $actions = array(
            'uploadimage'=>'uploadImage',
            'uploadscrawl'=>'uploadScrawl',
            'uploadvideo'=>'uploadVideo',
            'uploadfile'=>'uploadFile',
            'listimage'=>'listImage',
            'listfile'=>'listFile',
            'catchimage'=>'catchImage',
            'config'=>'config'
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
    /**
     * 上传图片
     */
    public function actionUploadImage(){
        $config = array(
            "pathFormat" => $this->config['imagePathFormat'],
            "maxSize" => $this->config['imageMaxSize'],
            "allowFiles" => $this->config['imageAllowFiles']
        );
        $fieldName = $this->config['imageFieldName'];
        $this->upload($fieldName, $config);
    }

    /**
     * 上传涂鸦
     */
    public function actionUploadScrawl(){
        $config = array(
            "pathFormat" => $this->config['scrawlPathFormat'],
            "maxSize" => $this->config['scrawlMaxSize'],
            "allowFiles" => $this->config['scrawlAllowFiles'],
            "oriName" => "scrawl.png"
        );
        $fieldName = $this->config['scrawlFieldName'];
        $this->upload($fieldName, $config, 'base64');
    }
    /**
     * 上传视频
     */
    public function actionUploadVideo(){
        $config = array(
            "pathFormat" => $this->config['videoPathFormat'],
            "maxSize" => $this->config['videoMaxSize'],
            "allowFiles" => $this->config['videoAllowFiles']
        );
        $fieldName = $this->config['videoFieldName'];
        $this->upload($fieldName, $config);
    }
    /**
     * 上传文件
     */
    public function actionUploadFile(){
        $config = array(
            "pathFormat" => $this->config['filePathFormat'],
            "maxSize" => $this->config['fileMaxSize'],
            "allowFiles" => $this->config['fileAllowFiles']
        );
        $fieldName = $this->config['fileFieldName'];
        $this->upload($fieldName, $config);
    }

    /**
     * 文件列表
     */
    public function actionListFile(){
        $allowFiles = $this->config['fileManagerAllowFiles'];
        $listSize = $this->config['fileManagerListSize'];
        $path = $this->config['fileManagerListPath'];
        $this->manage($allowFiles,$listSize,$path);
    }

    /**
     *  图片列表
     */
    public function actionListImage(){
        $allowFiles = $this->config['imageManagerAllowFiles'];
        $listSize = $this->config['imageManagerListSize'];
        $path = $this->config['imageManagerListPath'];
        $this->manage($allowFiles,$listSize,$path);
    }

    /**
     * 获取远程图片
     */
    public function actionCatchImage(){
        set_time_limit(0);
        /* 上传配置 */
        $config = array(
            "pathFormat" => $this->config['catcherPathFormat'],
            "maxSize" => $this->config['catcherMaxSize'],
            "allowFiles" => $this->config['catcherAllowFiles'],
            "oriName" => "remote.png"
        );
        $fieldName = $this->config['catcherFieldName'];
        /* 抓取远程图片 */
        $list = array();
        if (isset($_POST[$fieldName])) {
            $source = $_POST[$fieldName];
        } else {
            $source = $_GET[$fieldName];
        }
        foreach ($source as $imgUrl) {
            $item = new Uploader($imgUrl, $config, "remote");
            $info = $item->getFileInfo();
            $info['thumbnail'] = $this->imageHandle($info['url']);
            $list[] = array(
                "state" => $info["state"],
                "url" => $info["url"],
                "source" => $imgUrl
            );
        }
        /* 返回抓取数据 */
        $result = json_encode(array(
            'state'=> count($list) ? 'SUCCESS':'ERROR',
            'list'=> $list
        ));
        $this->show($result);
    }

    /**
     * 各种上传
     * @param $fieldName
     * @param $config
     * @param $base64
     */
    protected function upload($fieldName,$config, $base64 = 'upload'){

        $up = new Uploader($fieldName, $config, $base64);
        $info = $up->getFileInfo();
        if( $this->thumbnail&&$info['state']=='SUCCESS'&&in_array($info['type'],array('.png','.jpg','.bmp','.gif'))){
            $info['thumbnail'] = $this->imageHandle($info['url']);
        }
        $result =  json_encode($info);
        $this->show($result);
    }

    /**
     * 自动处理图片
     * @param $fullName
     * @return mixed|string
     */
    protected function imageHandle($fullName){
        $thumbnail = $fullName;
        $path = $_SERVER['DOCUMENT_ROOT'];
        if (substr($fullName, 0, 1) != '/') {
            $fullName = '/' . $fullName;
        }
        $path = $path.$fullName;

        //@see https://github.com/yiisoft/yii2/tree/master/extensions/imagine

        return $path;
    }

    /**
     * 文件和图片管理action使用
     * @param $allowFiles
     * @param $listSize
     * @param $path
     */
    protected function manage($allowFiles, $listSize, $path){
        $allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);
        /* 获取参数 */
        $size = isset($_GET['size']) ? $_GET['size'] : $listSize;
        $start = isset($_GET['start']) ? $_GET['start'] : 0;
        $end = $start + $size;

        /* 获取文件列表 */
        $path = $_SERVER['DOCUMENT_ROOT'] . (substr($path, 0, 1) == "/" ? "":"/") . $path;
        $files = $this->getfiles($path, $allowFiles);
        if (!count($files)) {
            $result =  json_encode(array(
                "state" => "no match file",
                "list" => array(),
                "start" => $start,
                "total" => count($files),
            ));
            $this->show($result);
        }
        /* 获取指定范围的列表 */
        $len = count($files);
        for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--){
            $list[] = $files[$i];
        }
        /* 返回数据 */
        $result = json_encode(array(
            "state" => "SUCCESS",
            "list" => $list,
            "start" => $start,
            "total" => count($files),
        ));
        $this->show($result);
    }

    /**
     * 遍历获取目录下的指定类型的文件
     * @param $path
     * @param $allowFiles
     * @param array $files
     * @return array|null
     *
     */
    protected function getfiles($path, $allowFiles, &$files = array()){
        if (!is_dir($path)) return null;
        if(in_array(basename($path),$this->ignoreDir)) return null;
        if(substr($path, strlen($path) - 1) != '/') $path .= '/';
        $handle = opendir($path);
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $path2 = $path . $file;
                if (is_dir($path2)) {
                    $this->getfiles($path2, $allowFiles, $files);
                } else {
                    if($this->action->id == 'ListImage'&&$this->thumbnail){
                        $pat = "/\.thumbnail\.(".$allowFiles.")$/i";
                    }else{
                        $pat = "/\.(".$allowFiles.")$/i";
                    }
                    if (preg_match($pat, $file)) {
                        $files[] = array(
                            'url'=> substr($path2, strlen($_SERVER['DOCUMENT_ROOT'])),
                            'mtime'=> filemtime($path2)
                        );
                    }
                }
            }
        }
        return $files;
    }
}
