<?php

namespace common\modules\student\components\forumIn\forum\bizley\podium\src\controllers;

use common\modules\student\components\forumIn\forum\bizley\podium\src\filters\AccessControl;
use common\modules\student\components\forumIn\forum\bizley\podium\src\models\Category;
use common\modules\student\components\forumIn\forum\bizley\podium\src\models\Forum;
use common\modules\student\components\forumIn\forum\bizley\podium\src\models\Thread;
use common\modules\student\components\forumIn\forum\bizley\podium\src\models\User;
use common\modules\student\components\forumIn\forum\bizley\podium\src\rbac\Rbac;
use common\modules\student\components\forumIn\forum\bizley\podium\src\services\ThreadVerifier;
use Yii;
use yii\helpers\Html;
use yii\web\Response;









class ForumThreadController extends BaseController
{
    


    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => false]],
            ],
        ];
    }

    




    public function actions()
    {
        return [
            'lock' => [
                'class' => 'common\modules\student\components\forumIn\forum\bizley\podium\src\actions\ThreadAction',
                'permission' => Rbac::PERM_LOCK_THREAD,
                'boolAttribute' => 'locked',
                'switcher' => 'podiumLock',
                'onMessage' => Yii::t('podium/flash', 'Thread has been locked.'),
                'offMessage' => Yii::t('podium/flash', 'Thread has been unlocked.')
            ],
            'pin' => [
                'class' => 'common\modules\student\components\forumIn\forum\bizley\podium\src\actions\ThreadAction',
                'permission' => Rbac::PERM_PIN_THREAD,
                'boolAttribute' => 'pinned',
                'switcher' => 'podiumPin',
                'onMessage' => Yii::t('podium/flash', 'Thread has been pinned.'),
                'offMessage' => Yii::t('podium/flash', 'Thread has been unpinned.')
            ],
        ];
    }

    







    public function actionDelete($cid = null, $fid = null, $id = null, $slug = null)
    {
        $thread = (new ThreadVerifier([
            'categoryId' => $cid,
            'forumId' => $fid,
            'threadId' => $id,
            'threadSlug' => $slug
        ]))->verify();
        if (empty($thread)) {
            $this->error(Yii::t('podium/flash', 'Sorry! We can not find the thread you are looking for.'));
            return $this->redirect(['forum/index']);
        }

        if (!User::can(Rbac::PERM_DELETE_THREAD, ['item' => $thread])) {
            $this->error(Yii::t('podium/flash', 'Sorry! You do not have the required permission to perform this action.'));
            return $this->redirect(['forum/index']);
        }

        $postData = Yii::$app->request->post('thread');
        if ($postData) {
            if ($postData != $thread->id) {
                $this->error(Yii::t('podium/flash', 'Sorry! There was an error while deleting the thread.'));
            } else {
                if ($thread->podiumDelete()) {
                    $this->success(Yii::t('podium/flash', 'Thread has been deleted.'));
                    return $this->redirect([
                        'forum/forum',
                        'cid' => $thread->forum->category_id,
                        'id' => $thread->forum->id,
                        'slug' => $thread->forum->slug
                    ]);
                }
                $this->error(Yii::t('podium/flash', 'Sorry! There was an error while deleting the thread.'));
            }
        }
        return $this->render('delete', ['model' => $thread]);
    }

    







    public function actionMove($cid = null, $fid = null, $id = null, $slug = null)
    {
        $thread = (new ThreadVerifier([
            'categoryId' => $cid,
            'forumId' => $fid,
            'threadId' => $id,
            'threadSlug' => $slug
        ]))->verify();
        if (empty($thread)) {
            $this->error(Yii::t('podium/flash', 'Sorry! We can not find the thread you are looking for.'));
            return $this->redirect(['forum/index']);
        }

        if (!User::can(Rbac::PERM_MOVE_THREAD, ['item' => $thread])) {
            $this->error(Yii::t('podium/flash', 'Sorry! You do not have the required permission to perform this action.'));
            return $this->redirect(['forum/index']);
        }

        $forum = Yii::$app->request->post('forum');
        if ($forum) {
            if (!is_numeric($forum) || $forum < 1 || $forum == $thread->forum->id) {
                $this->error(Yii::t('podium/flash', 'You have to select the new forum.'));
            } else {
                if ($thread->podiumMoveTo($forum)) {
                    $this->success(Yii::t('podium/flash', 'Thread has been moved.'));
                    return $this->redirect([
                        'forum/thread',
                        'cid' => $thread->forum->category->id,
                        'fid' => $thread->forum->id,
                        'id' => $thread->id,
                        'slug' => $thread->slug
                    ]);
                }
                $this->error(Yii::t('podium/flash', 'Sorry! There was an error while moving the thread.'));
            }
        }

        $categories = Category::find()->orderBy(['name' => SORT_ASC]);
        $forums = Forum::find()->orderBy(['name' => SORT_ASC]);
        $list = [];
        $options = [];
        foreach ($categories->each() as $cat) {
            $catlist = [];
            foreach ($forums->each() as $for) {
                if ($for->category_id == $cat->id) {
                    $catlist[$for->id] = (User::can(Rbac::PERM_UPDATE_THREAD, ['item' => $for]) ? '* ' : '')
                                        . Html::encode($cat->name)
                                        . ' &raquo; '
                                        . Html::encode($for->name);
                    if ($for->id == $thread->forum->id) {
                        $options[$for->id] = ['disabled' => true];
                    }
                }
            }
            $list[Html::encode($cat->name)] = $catlist;
        }
        return $this->render('move', [
            'model' => $thread,
            'list' => $list,
            'options' => $options
        ]);
    }

    





    public function actionNewThread($cid = null, $fid = null)
    {
        if (!User::can(Rbac::PERM_CREATE_THREAD)) {
            $this->error(Yii::t('podium/flash', 'Sorry! You do not have the required permission to perform this action.'));
            return $this->redirect(['forum/index']);
        }

        $forum = Forum::find()->where(['id' => $fid, 'category_id' => $cid])->limit(1)->one();
        if (empty($forum)) {
            $this->error(Yii::t('podium/flash', 'Sorry! We can not find the forum you are looking for.'));
            return $this->redirect(['forum/index']);
        }

        $model = new Thread();
        $model->scenario = 'new';
        $model->subscribe = 1;
        $preview = false;
        $postData = Yii::$app->request->post();
        if ($model->load($postData)) {
            $model->posts = 0;
            $model->views = 0;
            $model->category_id = $forum->category->id;
            $model->forum_id = $forum->id;
            $model->author_id = User::loggedId();
            if ($model->validate()) {
                if (isset($postData['preview-button'])) {
                    $preview = true;
                } else {
                    if ($model->podiumNew()) {
                        $this->success(Yii::t('podium/flash', 'New thread has been created.'));
                        return $this->redirect([
                            'forum/thread',
                            'cid' => $forum->category->id,
                            'fid' => $forum->id,
                            'id' => $model->id,
                            'slug' => $model->slug
                        ]);
                    }
                    $this->error(Yii::t('podium/flash', 'Sorry! There was an error while creating the thread. Contact administrator about this problem.'));
                }
            }
        }
        return $this->render('new-thread', [
            'preview' => $preview,
            'model' => $model,
            'forum' => $forum,
        ]);
    }
}
