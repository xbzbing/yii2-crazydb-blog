<?php

namespace app\controllers;

use Yii;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use app\components\CMSUtils;
use app\models\Option;
use app\models\Comment;
use app\models\CommentSearch;
use app\models\Post;
use app\models\User;
use app\components\BaseController;

/**
 * CommentController implements the CRUD actions for Comment model.
 */
class CommentController extends BaseController
{
    /**
     * 留言
     * @TODO 开启了留言审核之后的通知机制
     * @param integer $id 文章的ID
     * @return array
     */
    public function actionAdd($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (isset($_POST['Comment'])) {
            $info = '留言成功！';
            $display = 1;
            $status = Comment::STATUS_UNAPPROVED;
            if (CMSUtils::getSysConfig(Option::ALLOW_COMMENT) === Option::STATUS_OPEN) {
                if (CMSUtils::getSysConfig(Option::AUDIT_ON_COMMENT) === Option::STATUS_OPEN) {
                    $info .= '您的留言需要经过管理员的审核才可以显示出来。';
                    $display = 0;
                } else
                    $status = Comment::STATUS_APPROVED;
            } else {
                return ['status' => 'fail', 'info' => '留言功能已被关闭。'];
            }

            $comment = new Comment();
            $comment->setScenario(Comment::SCENARIO_COMMENT);
            $comment->pid = $id;
            $comment->load(Yii::$app->request->post());
            $comment->status = $status;
            if ($comment->replyto > 0)
                $comment->type = Comment::TYPE_REPLYTO;
            else
                $comment->type = Comment::TYPE_REPLY;

            if (!Yii::$app->user->isGuest) {
                /* @var User $current_user */
                $current_user = Yii::$app->user->identity;
                $comment->nickname = $current_user->nickname;
                $comment->uid = $current_user->id;
                $comment->email = $current_user->email;
                $comment->url = $current_user->website ? $current_user->website : Url::to(['/user/show', 'nickname' => $comment->nickname]);
            } else {
                $comment->uid = 0;
                /* @var User $user */
                $user = User::find()->where(['email' => $comment->email])->orWhere(['nickname' => $comment->nickname])->one();
                if ($user != null) {
                    //保护注册用户不被冒用身份
                    $info = '<p>留言失败！</p>';
                    if ($user->email == $comment->email)
                        $info .= "<p>当前邮箱&lt;{$comment->email}&gt;已经被注册，</p>";
                    if ($user->nickname == $comment->nickname)
                        $info .= "<p>当前用户昵称&lt;{$comment->nickname}&gt;已经被注册，</p>";
                    $info .= "<p>为防止用户身份被冒用，请登录后再留言。</p>";
                    return ['status' => 'fail', 'info' => $info, 'label' => []];
                }

            }

            if ($comment->save()) {
                //保存成功，返回结果并在前台显示
                /* @var Post $post */
                $template = $display ? $this->renderPartial('//comment/display', ['comment' => $comment]) : '';
                $post = Post::findOne($comment->pid);
                $post_url = $post->getUrl(true);
                $post_link = Html::a($post->title, $post_url);
                $comment_url = Html::a('点击查看', $post_url . '#comment-' . $comment->id);

                //开始邮件通知被回复的人
                //防止没有设置mailer
                $sendMail = empty($_POST['Comment']['sendMail']) ? false : $_POST['Comment']['sendMail'];
                $mailer = Yii::$app->get('mailer', false);
                if ($sendMail && !$mailer)
                    $info = '留言成功！[邮件发送失败，服务器未配置mailer组件]';

                //1、是否勾选了通知对方 2、是否配置了mailer组件 3、是否开启留言邮件功能 4、留言通过审核 5、留言类型是回复
                $serverReady = $mailer && CMSUtils::getSysConfig(Option::AUDIT_ON_COMMENT) === Option::STATUS_OPEN;
                $clientReady = $sendMail && $comment->status == Comment::STATUS_APPROVED && $comment->type == Comment::TYPE_REPLYTO;

                if ($serverReady && $clientReady && CMSUtils::getSysConfig(Option::AUDIT_ON_COMMENT) != Option::STATUS_OPEN) {
                    /* @var Comment $replyTo */
                    $replyTo = Comment::findOne($comment->replyto);
                    if ($replyTo && $replyTo->email != $comment->email && $replyTo->email != Yii::$app->params['admin_email']) {
                        //自言自语式或者给管理员留言式
                        $notice = <<<NOTICE
                        {$comment->nickname} 在 《{$post_link}》上回复了你：
                        <br>
                        <p>{$comment->content}</p>
                        <br>
                        {$comment_url}，如果链接不能点击，请直接访问下面的链接:
                        <br><em>{$post_url}#comment-{$comment->id}</em>
NOTICE;
                        CMSUtils::notice($replyTo->email, $replyTo->nickname, '你的留言有新的回复', $notice);
                    }
                }
                //通知管理员
                if ($mailer && Yii::$app->params['admin_email'] != $comment->email) {
                    //通知管理员有新的留言
                    $notice = <<<NOTICE
                        {$comment->nickname} 在《{$post_link}》发表了评论：
                        <br>
                        <p>{$comment->content}</p>
                        <br>
                        {$comment_url}，如果链接不能点击，请直接访问下面的链接:
                        <br><em>{$post_url}#comment-{$comment->id}</em>
NOTICE;
                    CMSUtils::notice(Yii::$app->params['admin_email'], '网站管理员', '网站有新的留言', $notice);
                }
                return [
                    'status' => 'success',
                    'info' => $info,
                    'replyto' => $comment->replyto,
                    'display' => $display,
                    'template' => $template
                ];
            } else {
                $info = '<p>留言失败！</p>';
                $labels = array();
                foreach ($comment->errors as $label => $error) {
                    $info .= '<p>' . implode(' ', $error) . '</p>';
                    $labels[] = $label;
                }
                return ['status' => 'fail', 'info' => $info, 'label' => $labels];
            }
        } else
            return ['status' => 'fail', 'info' => '请填写留言内容'];
    }

}
