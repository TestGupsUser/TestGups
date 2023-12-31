<?php

namespace common\modules\student\components\forumIn\forum\bizley\podium\src\models;

use common\models\User as MainAppUser;
use common\models\User as UserApp;
use common\modules\student\components\forumIn\forum\bizley\podium\src\db\Query;
use common\modules\student\components\forumIn\forum\bizley\podium\src\helpers\Helper;
use common\modules\student\components\forumIn\forum\bizley\podium\src\log\Log;
use common\modules\student\components\forumIn\forum\bizley\podium\src\models\db\UserActiveRecord;
use common\modules\student\components\forumIn\forum\bizley\podium\src\Podium;
use common\modules\student\components\forumIn\forum\bizley\podium\src\PodiumCache;
use common\modules\student\components\forumIn\forum\bizley\podium\src\rbac\Rbac;
use Exception;
use himiklab\yii2\recaptcha\ReCaptchaValidator;
use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;







class User extends UserActiveRecord
{
    


    const ROLE_MEMBER = 1;
    const ROLE_MODERATOR = 9;
    const ROLE_ADMIN = 10;

    


    const RESP_ERR = 0;
    const RESP_OK = 1;
    const RESP_EMAIL_SEND_ERR = 2;
    const RESP_NO_EMAIL_ERR = 3;

    


    public $captcha;

    


    public $currentPassword;

    


    public $password;

    


    public $newPassword;

    


    public $passwordRepeat;

    


    public $newPasswordRepeat;

    


    public $tos;

    


    public function rules()
    {
        $rules = [
            [['username', 'email', 'password', 'passwordRepeat', 'tos'], 'required', 'except' => ['account']],
            ['currentPassword', 'required'],
            ['currentPassword', 'validateCurrentPassword'],
            [['email', 'new_email'], 'email', 'message' => Yii::t('podium/view', 'This is not a valid e-mail address.')],
            ['email', 'unique'],
            ['new_email', 'unique', 'targetAttribute' => 'email'],
            [['password', 'newPassword'], 'passwordRequirements'],
            ['passwordRepeat', 'compare', 'compareAttribute' => 'password'],
            ['newPasswordRepeat', 'compare', 'compareAttribute' => 'newPassword'],
            ['username', 'unique'],
            ['username', 'validateUsername'],
            ['inherited_id', 'integer'],
            ['status', 'default', 'value' => self::STATUS_REGISTERED],
            ['role', 'default', 'value' => self::ROLE_MEMBER],
            ['tos', 'compare', 'compareValue' => 1, 'message' => Yii::t('podium/view', 'You have to read and agree on ToS.')],
        ];

        if (Podium::getInstance()->podiumConfig->get('recaptcha_sitekey') !== '' && Podium::getInstance()->podiumConfig->get('recaptcha_secretkey') !== '') {
            $rules[] = ['captcha', ReCaptchaValidator::class, 'secret' => Podium::getInstance()->podiumConfig->get('recaptcha_secretkey')];
        } else {
            $rules[] = ['captcha', 'captcha', 'captchaAction' => Podium::getInstance()->id . '/account/captcha'];
        }

        return $rules;
    }

    


    public function scenarios()
    {
        $scenarios = [
            'installation' => [],
            'token' => [],
            'ban' => [],
            'role' => [],
            'passwordChange' => ['password', 'passwordRepeat'],
            'register' => ['username', 'email', 'password', 'passwordRepeat'],
            'account' => ['username', 'new_email', 'newPassword', 'newPasswordRepeat', 'currentPassword'],
            'accountInherit' => ['username', 'new_email', 'currentPassword'],
        ];
        if (Podium::getInstance()->podiumConfig->get('use_captcha')) {
            $scenarios['register'][] = 'captcha';
        }
        return $scenarios;
    }

    



    public function activate()
    {
        if ($this->status == self::STATUS_REGISTERED) {
            $transaction = static::getDb()->beginTransaction();
            try {
                $this->removeActivationToken();
                $this->status = self::STATUS_ACTIVE;
                if (!$this->save()) {
                    throw new Exception('User saving error!');
                }
                if (!Podium::getInstance()->rbac->assign(Podium::getInstance()->rbac->getRole(Rbac::ROLE_USER), $this->id)) {
                    throw new Exception('User role assigning error!');
                }
                $transaction->commit();
                return true;
            } catch (Exception $e) {
                $transaction->rollBack();
                Log::error($e->getMessage(), null, __METHOD__);
            }
        }
        return false;
    }

    



    public function changeEmail()
    {
        $this->email = $this->new_email;
        $this->new_email = null;
        $this->removeEmailToken();
        return $this->save();
    }

    



    public function changePassword()
    {
        $this->setPassword($this->password);
        $this->generateAuthKey();
        $this->removePasswordResetToken();
        return $this->save();
    }

    



    public function getNewMessagesCount()
    {
        $cache = Podium::getInstance()->podiumCache->getElement('user.newmessages', $this->id);
        if ($cache === false) {
            $cache = (new Query())->from(MessageReceiver::tableName())->where([
                    'receiver_id' => $this->id,
                    'receiver_status' => Message::STATUS_NEW
                ])->count();
            Podium::getInstance()->podiumCache->setElement('user.newmessages', $this->id, $cache);
        }
        return $cache;
    }

    



    public function getPodiumName()
    {
        return $this->username ? $this->username : 'user_' . $this->id;
    }

    




    public function getPodiumTag($simple = false)
    {
        if ($this->inherited_id != 0)
            $username = (new MainAppUser)->find()->where(['id' => $this->inherited_id])->one()->username;
        else
            $username = $this->podiumName;
        return Helper::podiumUserTag($username, $this->role, $this->id, $this->podiumSlug, $simple);
    }

    



    public function getPodiumSlug()
    {
        return $this->slug ? $this->slug : 'forum-' . $this->id;
    }

    



    public function getPostsCount()
    {
        return static::findPostsCount($this->id);
    }

    




    public static function findPostsCount($id)
    {
        $cache = Podium::getInstance()->podiumCache->getElement('user.postscount', $id);
        if ($cache === false) {
            $cache = (new Query())->from(Post::tableName())->where(['author_id' => $id])->count();
            Podium::getInstance()->podiumCache->setElement('user.postscount', $id, $cache);
        }
        return $cache;
    }

    



    public function getThreadsCount()
    {
        return static::findThreadsCount($this->id);
    }

    




    public static function findThreadsCount($id)
    {
        $cache = Podium::getInstance()->podiumCache->getElement('user.threadscount', $id);
        if ($cache === false) {
            $cache = (new Query())->from(Thread::tableName())->where(['author_id' => $id])->count();
            Podium::getInstance()->podiumCache->setElement('user.threadscount', $id, $cache);
        }
        return $cache;
    }

    



    public static function getRoles()
    {
        return [
            self::ROLE_MEMBER => Yii::t('podium/view', 'Member'),
            self::ROLE_MODERATOR => Yii::t('podium/view', 'Moderator'),
            self::ROLE_ADMIN => Yii::t('podium/view', 'Admin'),
        ];
    }

    



    public static function getModRoles()
    {
        return [
            self::ROLE_MODERATOR => Yii::t('podium/view', 'Moderator'),
            self::ROLE_ADMIN => Yii::t('podium/view', 'Admin'),
        ];
    }

    



    public static function getStatuses()
    {
        return [
            self::STATUS_ACTIVE => Yii::t('podium/view', 'Active'),
            self::STATUS_BANNED => Yii::t('podium/view', 'Banned'),
            self::STATUS_REGISTERED => Yii::t('podium/view', 'Registered'),
        ];
    }

    



    public function getSubscriptionsCount()
    {
        $cache = Podium::getInstance()->podiumCache->getElement('user.subscriptions', $this->id);
        if ($cache === false) {
            $cache = (new Query())->from(Subscription::tableName())->where([
                    'user_id' => $this->id,
                    'post_seen' => Subscription::POST_NEW
                ])->count();
            Podium::getInstance()->podiumCache->setElement('user.subscriptions', $this->id, $cache);
        }
        return $cache;
    }

    





    public function isBefriendedBy($userId)
    {
        if ((new Query())->select('id')->from('{{%podium_user_friend}}')->where([
                'user_id' => $userId,
                'friend_id' => $this->id
            ])->exists()) {
            return true;
        }
        return false;
    }

    





    public function isFriendOf($userId)
    {
        if ((new Query())->select('id')->from('{{%podium_user_friend}}')->where([
                'user_id' => $this->id,
                'friend_id' => $userId
            ])->exists()) {
            return true;
        }
        return false;
    }

    




    public function isIgnoredBy($userId)
    {
        if ((new Query())->select('id')->from('{{%podium_user_ignore}}')->where([
                'user_id' => $userId,
                'ignored_id' => $this->id
            ])->exists()) {
            return true;
        }
        return false;
    }

    




    public function isIgnoring($user_id)
    {
        if ((new Query())->select('id')->from('{{%podium_user_ignore}}')->where([
                'user_id' => $this->id,
                'ignored_id' => $user_id
            ])->exists()) {
            return true;
        }
        return false;
    }

    



    public static function loggedId()
    {
        if (Podium::getInstance()->user->isGuest) {
            return null;
        }
        if (Podium::getInstance()->userComponent !== true) {
            $user = static::findMe();
            if (empty($user)) {
                return null;
            }
            return $user->id;
        }
        return Podium::getInstance()->user->id;
    }

    



    public function ban()
    {
        $this->scenario = 'ban';
        $this->status = self::STATUS_BANNED;
        return $this->save();
    }

    




    public function demoteTo($role)
    {
        $transaction = static::getDb()->beginTransaction();
        try {
            $this->scenario = 'role';
            $this->role = $role;
            if (!$this->save()) {
                throw new Exception('User saving error!');
            }
            if (Podium::getInstance()->rbac->getRolesByUser($this->id)) {
                if (!Podium::getInstance()->rbac->revoke(Podium::getInstance()->rbac->getRole(Rbac::ROLE_MODERATOR), $this->id)) {
                    throw new Exception('User role revoking error!');
                }
            }
            if (!Podium::getInstance()->rbac->assign(Podium::getInstance()->rbac->getRole(Rbac::ROLE_USER), $this->id)) {
                throw new Exception('User role assigning error!');
            }
            if ((new Query())->from(Mod::tableName())->where(['user_id' => $this->id])->exists()) {
                if (!Podium::getInstance()->db->createCommand()->delete(Mod::tableName(), ['user_id' => $this->id])->execute()) {
                    throw new Exception('Moderator deleting error!');
                }
            }
            Activity::updateRole($this->id, User::ROLE_MEMBER);

            $transaction->commit();
            Log::info('User demoted', $this->id, __METHOD__);
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            Log::error($e->getMessage(), null, __METHOD__);
        }
        return false;
    }

    





    public function promoteTo($role)
    {
        $transaction = static::getDb()->beginTransaction();
        try {
            $this->scenario = 'role';
            $this->role = $role;
            if (!$this->save()) {
                throw new Exception('User saving error!');
            }
            if (Podium::getInstance()->rbac->getRolesByUser($this->id)) {
                if (!Podium::getInstance()->rbac->revoke(Podium::getInstance()->rbac->getRole(Rbac::ROLE_USER), $this->id)) {
                    throw new Exception('User role revoking error!');
                }
            }
            if (!Podium::getInstance()->rbac->assign(Podium::getInstance()->rbac->getRole(Rbac::ROLE_MODERATOR), $this->id)) {
                throw new Exception('User role assigning error!');
            }
            Activity::updateRole($this->id, User::ROLE_MODERATOR);

            $transaction->commit();
            Log::info('User promoted', $this->id, __METHOD__);
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            Log::error($e->getMessage(), null, __METHOD__);
        }
        return false;
    }

    



    public function unban()
    {
        $this->setScenario('ban');
        $this->status = self::STATUS_ACTIVE;
        return $this->save();
    }

    



    public function register()
    {
        $this->setPassword($this->password);
        $this->generateActivationToken();
        $this->generateAuthKey();
        $this->status = self::STATUS_REGISTERED;

        if (!$this->save()) {
            return self::RESP_ERR;
        }
        if (empty($this->email)) {
            return self::RESP_NO_EMAIL_ERR;
        }
        if (!$this->sendActivationEmail()) {
            return self::RESP_EMAIL_SEND_ERR;
        }
        return self::RESP_OK;
    }

    



    public function saveChanges()
    {
        if (!empty($this->newPassword)) {
            $this->setPassword($this->newPassword);
        }
        if (!empty($this->new_email)) {
            $this->generateEmailToken();
        }
        $updateActivityName = $this->isAttributeChanged('username');
        if (!$this->save(false)) {
            return false;
        }
        if ($updateActivityName) {
            Activity::updateName($this->id, $this->podiumName, $this->podiumSlug);
        }
        return true;
    }

    private $_access = [];

    






    public static function can($permissionName, $params = [], $allowCaching = true)
    {
        if (Podium::getInstance()->userComponent === true) {
            return Podium::getInstance()->user->can($permissionName, $params, $allowCaching);
        }
        if (!Podium::getInstance()->user->isGuest) {
            $user = static::findMe();
            if (empty($user)) {
                return false;
            }
            if ($allowCaching && empty($params) && isset($user->_access[$permissionName])) {
                return $user->_access[$permissionName];
            }
            $access = Podium::getInstance()->rbac->checkAccess($user->id, $permissionName, $params);
            if ($allowCaching && empty($params)) {
                $user->_access[$permissionName] = $access;
            }
            return $access;
        }
        return false;
    }

    




    public static function friendsList()
    {
        if (Podium::getInstance()->user->isGuest) {
            return null;
        }
        $logged = static::loggedId();
        $cache = Podium::getInstance()->podiumCache->getElement('user.friends', $logged);
        if ($cache === false) {
            $cache = [];
            $friends = static::findMe()->friends;
            if ($friends) {
                foreach ($friends as $friend) {
                    $cache[$friend->id] = $friend->getPodiumTag(true);
                }
            }
            Podium::getInstance()->podiumCache->setElement('user.friends', $logged, $cache);
        }
        return $cache;
    }

    





    public function updateModeratorForOne($forumId = null)
    {
        try {
            if ((new Query())->from(Mod::tableName())->where([
                    'forum_id' => $forumId,
                    'user_id' => $this->id
                ])->exists()) {
                Podium::getInstance()->db->createCommand()->delete(Mod::tableName(), [
                    'forum_id' => $forumId,
                    'user_id' => $this->id
                ])->execute();
            } else {
                Podium::getInstance()->db->createCommand()->insert(Mod::tableName(), [
                    'forum_id' => $forumId,
                    'user_id' => $this->id
                ])->execute();
            }
            Podium::getInstance()->podiumCache->deleteElement('forum.moderators', $forumId);
            Log::info('Moderator updated', $this->id, __METHOD__);
            return true;
        } catch (Exception $e) {
            Log::error($e->getMessage(), null, __METHOD__);
        }
        return false;
    }

    






    public function updateModeratorForMany($newForums = [], $oldForums = [])
    {
        $transaction = static::getDb()->beginTransaction();
        try {
            $add = [];
            foreach ($newForums as $forum) {
                if (!in_array($forum, $oldForums)) {
                    if ((new Query())->from(Forum::tableName())->where(['id' => $forum])->exists()
                        && (new Query())->from(Mod::tableName())->where(['forum_id' => $forum, 'user_id' => $this->id])->exists() === false) {
                        $add[] = [$forum, $this->id];
                    }
                }
            }
            $remove = [];
            foreach ($oldForums as $forum) {
                if (!in_array($forum, $newForums)) {
                    if ((new Query)->from(Mod::tableName())->where(['forum_id' => $forum, 'user_id' => $this->id])->exists()) {
                        $remove[] = $forum;
                    }
                }
            }
            if (!empty($add)) {
                if (!Podium::getInstance()->db->createCommand()->batchInsert(Mod::tableName(), ['forum_id', 'user_id'], $add)->execute()) {
                    throw new Exception('Moderators adding error!');
                }
            }
            if (!empty($remove)) {
                if (!Podium::getInstance()->db->createCommand()->delete(Mod::tableName(), ['forum_id' => $remove, 'user_id' => $this->id])->execute()) {
                    throw new Exception('Moderators deleting error!');
                }
            }
            Podium::getInstance()->podiumCache->delete('forum.moderators');
            Log::info('Moderators updated', null, __METHOD__);
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            Log::error($e->getMessage(), null, __METHOD__);
        }
        return false;
    }

    




    protected function generateUsername()
    {
        
        







        $user = new UserApp();
        $username = $user->find()->where(['id' => Yii::$app->user->id])->one()->username;
        $username = str_replace(' ', '_', $username) . '_' . Yii::$app->user->id;
        $this->username = $username;
    }

    




    public static function createInheritedAccount()
    {
        if (!Podium::getInstance()->user->isGuest) {
            $transaction = static::getDb()->beginTransaction();
            try {
                $newUser = new static;
                $newUser->setScenario('installation');
                $newUser->inherited_id = Podium::getInstance()->user->id;
                $newUser->status = self::STATUS_ACTIVE;
                $newUser->role = self::ROLE_MEMBER;
                $newUser->generateUsername();
                if (!$newUser->save()) {
                    throw new Exception('Account creating error');
                }
                if (!Podium::getInstance()->rbac->assign(Podium::getInstance()->rbac->getRole(Rbac::ROLE_USER), $newUser->id)) {
                    throw new Exception('User role assigning error');
                }
                PodiumCache::clearAfter('activate');
                Log::info('Inherited account created', $newUser->id, __METHOD__);
                $transaction->commit();
                return true;
            } catch (Exception $e) {
                $transaction->rollBack();
                Log::error($e->getMessage(), null, __METHOD__);
            }
        }
        return false;
    }

    





    public static function getMembersList($query = null)
    {
        if (is_null($query) || !is_string($query)) {
            return Json::encode(['results' => []]);
        }

        $cache = Podium::getInstance()->podiumCache->getElement('members.fieldlist', $query);
        if ($cache === false) {
            $users = static::find()->where(['and',
                ['status' => self::STATUS_ACTIVE],
                ['!=', 'id', static::loggedId()],
                ['like', 'username', $query]
            ]);
            $users->orderBy(['username' => SORT_ASC, 'id' => SORT_ASC]);
            $results = ['results' => []];
            foreach ($users->each() as $user) {
                $results['results'][] = ['id' => $user->id, 'text' => $user->getPodiumTag(true)];
            }
            if (empty($results['results'])) {
                return Json::encode(['results' => []]);
            }
            $cache = Json::encode($results);
            Podium::getInstance()->podiumCache->setElement('members.fieldlist', $query, $cache);
        }
        return $cache;
    }

    





    public function updateIgnore($member)
    {
        try {
            if ($this->isIgnoredBy($member)) {
                if (!Podium::getInstance()->db->createCommand()->delete('{{%podium_user_ignore}}', [
                        'user_id' => $member,
                        'ignored_id' => $this->id
                    ])->execute()) {
                    return false;
                }
                Log::info('User unignored', $this->id, __METHOD__);
            } else {
                if (!Podium::getInstance()->db->createCommand()->insert('{{%podium_user_ignore}}', ['user_id' => $member, 'ignored_id' => $this->id])->execute()) {
                    return false;
                }
                Log::info('User ignored', $this->id, __METHOD__);
            }
            return true;
        } catch (Exception $e) {
            Log::error($e->getMessage(), null, __METHOD__);
        }
        return false;
    }

    





    public function updateFriend($friend)
    {
        try {
            if ($this->isBefriendedBy($friend)) {
                if (!Podium::getInstance()->db->createCommand()->delete('{{%podium_user_friend}}', [
                        'user_id' => $friend,
                        'friend_id' => $this->id
                    ])->execute()) {
                    return false;
                }
                Log::info('User unfriended', $this->id, __METHOD__);
            } else {
                if (!Podium::getInstance()->db->createCommand()->insert('{{%podium_user_friend}}', ['user_id' => $friend, 'friend_id' => $this->id])->execute()) {
                    return false;
                }
                Log::info('User befriended', $this->id, __METHOD__);
            }
            Podium::getInstance()->podiumCache->deleteElement('user.friends', $friend);
            return true;
        } catch (Exception $e) {
            Log::error($e->getMessage(), null, __METHOD__);
        }
        return false;
    }

    




    protected function sendActivationEmail()
    {
        $forum = Podium::getInstance()->podiumConfig->get('name');
        $email = Content::fill(Content::EMAIL_REGISTRATION);
        if ($email !== false) {
            $link = Url::to(['account/activate', 'token' => $this->activation_token], true);
            return Email::queue(
                $this->email,
                str_replace('{forum}', $forum, $email->topic),
                str_replace('{forum}', $forum, str_replace('{link}',
                    Html::a($link, $link), $email->content)),
                !empty($this->id) ? $this->id : null
            );
        }
        return false;
    }

    private static $_identity;

    



    public static function findMe()
    {
        if (Podium::getInstance()->userComponent === true) {
            return Podium::getInstance()->user->identity;
        }
        if (static::$_identity === null) {
            static::$_identity = static::find()->where(['inherited_id' => Podium::getInstance()->user->id])->limit(1)->one();
        }
        return static::$_identity;
    }
}
