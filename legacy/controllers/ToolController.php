<?php

namespace app\controllers;

use Yii;
use yii\helpers\FileHelper;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;

class ToolController extends Controller
{
    public function actions()
    {
        return [
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'transparent' => true
            ],
        ];
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * 图片上传
     * @return array
     */
    public function actionImageUpload()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['status' => 'error', 'message' => 'Invalid Method.'];
        }

        //上传结果检测
        $imageFile = UploadedFile::getInstanceByName('file');

        if (!$imageFile)
            return ['status' => 'error', 'message' => 'Not File Uploaded.'];

        if ($imageFile->hasError) {
            return ['status' => 'error', 'message' => $imageFile->error];
        }

        //后缀检测
        if (!empty(Yii::$app->params['upload']['imageExtension']) && is_array(Yii::$app->params['upload']['imageExtension']))
            $imageExtension = Yii::$app->params['upload']['imageExtension.'];
        else
            $imageExtension = ['png', 'jpg'];

        if (!in_array($imageFile->extension, $imageExtension)) {
            return ['status' => 'error', 'message' => 'Invalid extension.'];
        }

        if (empty(Yii::$app->params['upload']['savePath']))
            $savePath = 'upload' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . date('Ymd') . DIRECTORY_SEPARATOR;
        else
            $savePath = Yii::$app->params['upload']['savePath'] . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . date('Ymd') . DIRECTORY_SEPARATOR;

        $basePath = Yii::getAlias('@webroot');

        if (!file_exists($basePath . DIRECTORY_SEPARATOR . $savePath) && !FileHelper::createDirectory($basePath . DIRECTORY_SEPARATOR . $savePath, 0755)) {
            return ['status' => 'error', 'message' => 'Invalid Save Path.'];
        }

        $imageName = date('his') . '_' . substr(md5(Yii::$app->security->generateRandomString()), 8, 16) . '.' . $imageFile->extension;

        if (!$imageFile->saveAs($basePath . DIRECTORY_SEPARATOR . $savePath . DIRECTORY_SEPARATOR . $imageName)) {
            Yii::$app->response->statusCode = 500;
            return ['status' => 'error', 'message' => 'Save Image Failed.'];
        }
        if (substr($savePath, 0, 1) == '/')
            $savePath = substr($savePath, 1);
        $url = Url::home(true) . str_replace(['\\', '//'], '/', "{$savePath}/{$imageName}");

        $headers = [
            'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
            'Last-Modified' => gmdate("D, d M Y H:i:s") . ' GMT',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => ' no-cache',
        ];

        foreach ($headers as $key => $value)
            Yii::$app->response->headers->set($key, $value);

        return [
            'status' => 'success',
            'url' => $url
        ];
    }
}
