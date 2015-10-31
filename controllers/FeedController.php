<?php

namespace app\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\caching\DbDependency;
use yii\db\Query;
use app\components\BaseController;
use app\models\Post;
use FeedWriter\RSS2;
use FeedWriter\ATOM;


class FeedController extends BaseController
{
    public function behaviors()
    {
        $sql = (new Query())
            ->select('MAX(update_time)')
            ->from(Post::tableName())
            ->createCommand()->rawSql;
        return [
            [
                'class' => 'yii\filters\PageCache',
                'only' => ['rss', 'atom'],
                'duration' => 24 * 60 * 60,
                'dependency' => [
                    'class' => DbDependency::className(),
                    'sql' => $sql,
                ],
            ],
        ];
    }

    public function init()
    {
        $this->enableTheme = false;
        parent::init();
        $this->defaultAction = 'rss';
        $this->layout = null;
    }

    public function actionRss()
    {
        $feed = new RSS2();
        $this->generateFeed($feed);
        $feed->printFeed(true);
    }

    public function actionAtom()
    {
        $feed = new ATOM();
        $this->generateFeed($feed);
        $feed->printFeed(true);
    }

    /**
     * @param ATOM|RSS2 $feed
     * @return ATOM|RSS2
     */
    protected function generateFeed($feed)
    {
        /* @var Post[] $posts */
        $site_name = ArrayHelper::getValue(Yii::$app->params, 'site_name', Yii::$app->name);

        $posts = Post::find()
            ->where(['status' => Post::STATUS_PUBLISHED])
            ->orderBy(['post_time' => SORT_DESC, 'update_time' => SORT_DESC])
            ->limit(20)
            ->all();

        $feed->setTitle($site_name);
        $feed->setLink(Url::home(true));
        $feed->setSelfLink(Url::to(['feed/rss'], true));
        $feed->setAtomLink(Url::to(['feed/atom'], true));
        $feed->setDescription(ArrayHelper::getValue(Yii::$app->params, 'seo_description', '最新更新的文章'));
        if ($posts)
            $feed->setDate($posts[0]->update_time);
        else
            $feed->setDate(time());

        foreach ($posts as $post) {
            $entry = $feed->createNewItem();
            $entry->setTitle($post->title);
            $entry->setLink($post->getUrl(true));
            $entry->setDate(intval($post->post_time));
            $entry->setDescription($post->excerpt);
            $entry->setAuthor($post->author_name ? $post->author_name : $post->author->nickname);
            $entry->setId($post->alias);
            if ($feed instanceof ATOM)
                $entry->setContent($post->content);

            $feed->addItem($entry);
        }
        return $feed;
    }

}
