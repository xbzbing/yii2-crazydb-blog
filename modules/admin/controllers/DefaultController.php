<?php
namespace app\modules\admin\controllers;

use Yii;
use app\components\Common;
use app\components\XUtils;
use app\modules\admin\components\Controller;

/**
 * Default controller
 */
class DefaultController extends Controller
{

    public function actionIndex()
    {
        $server['serverSoft'] = $_SERVER['SERVER_SOFTWARE'];
        $server['serverOs'] = PHP_OS;
        $server['phpVersion'] = PHP_VERSION;
        $server['fileupload'] = ini_get('file_uploads') ? ini_get('upload_max_filesize') : '禁止上传';
        $server['serverUri'] = $_SERVER['SERVER_NAME'];
        $server['maxExcuteTime'] = ini_get('max_execution_time') . ' 秒';
        $server['maxExcuteMemory'] = ini_get('memory_limit');
        $server['magic_quote_gpc'] = get_magic_quotes_gpc() ? '开启' : '关闭';
        $server['allow_url_fopen'] = ini_get('allow_url_fopen') ? '开启' : '关闭';
        $server['excuteUseMemory'] = function_exists('memory_get_usage') ? XUtils::dataFormat(memory_get_usage()) : '未知';
        $dbsize = 0;
        $connection = Yii::$app->db;
        $sql = "SHOW TABLE STATUS LIKE '{$connection->tablePrefix}%'";
        $command = $connection->createCommand($sql)->queryAll();
        foreach ($command as $table)
            $dbsize += $table['Data_length'] + $table['Index_length'];
        $mysqlVersion = $connection->createCommand("SELECT version() AS version")->queryAll();
        $server['mysqlVersion'] = $mysqlVersion[0]['version'];
        $server['dbsize'] = $dbsize ? XUtils::dataFormat($dbsize) : '未知';
        return $this->render('index', ['server' => $server]);
    }

    public function actionLocale($language)
    {
        Common::setLanguage($language);
        return $this->redirect(['index']);
    }

}
