<?php

namespace common\modules\student\components\forumIn\forum\bizley\podium\src\models;

use cebe\markdown\GithubMarkdown;
use common\modules\student\components\forumIn\forum\bizley\podium\src\db\Query;
use common\modules\student\components\forumIn\forum\bizley\podium\src\log\Log;
use common\modules\student\components\forumIn\forum\bizley\podium\src\models\db\MessageActiveRecord;
use common\modules\student\components\forumIn\forum\bizley\podium\src\Podium;
use Exception;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;









class Message extends MessageActiveRecord
{
    


    const MAX_RECEIVERS = 10;
    const SPAM_MESSAGES = 10;
    const SPAM_WAIT     = 1;

    



    public static function re()
    {
        return Yii::t('podium/view', 'Re:');
    }

    




    public function isMessageReceiver($userId)
    {
        if ($this->messageReceivers) {
            foreach ($this->messageReceivers as $receiver) {
                if ($receiver->receiver_id == $userId) {
                    return true;
                }
            }
        }
        return false;
    }

    



    public function send()
    {
        $transaction = static::getDb()->beginTransaction();
        try {
            $this->sender_id = User::loggedId();
            $this->sender_status = self::STATUS_READ;

            if (!$this->save()) {
                throw new Exception('Message saving error!');
            }

            $count = count($this->receiversId);
            foreach ($this->receiversId as $receiver) {
                if (!(new Query())->select('id')->from(User::tableName())->where(['id' => $receiver, 'status' => User::STATUS_ACTIVE])->exists()) {
                    if ($count == 1) {
                        throw new Exception('No active receivers to send message to!');
                    }
                    continue;
                }
                $message = new MessageReceiver();
                $message->message_id = $this->id;
                $message->receiver_id = $receiver;
                $message->receiver_status = self::STATUS_NEW;
                if (!$message->save()) {
                    throw new Exception('MessageReceiver saving error!');
                }
                Podium::getInstance()->podiumCache->deleteElement('user.newmessages', $receiver);
            }
            $transaction->commit();
            $sessionKey = 'messages.' . $this->sender_id;
            if (Yii::$app->session->has($sessionKey)) {
                $sentAlready = explode('|', Yii::$app->session->get($sessionKey));
                $sentAlready[] = time();
                Yii::$app->session->set($sessionKey, implode('|', $sentAlready));
            } else {
                Yii::$app->session->set($sessionKey, time());
            }
            return true;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Log::error($e->getMessage(), $this->id, __METHOD__);
        }
        return false;
    }

    





    public static function tooMany($userId)
    {
        $sessionKey = 'messages.' . $userId;
        if (Yii::$app->session->has($sessionKey)) {
            $sentAlready = explode('|', Yii::$app->session->get($sessionKey));
            $validated = [];
            foreach ($sentAlready as $t) {
                if (preg_match('/^[0-9]+$/', $t)) {
                    if ($t > time() - self::SPAM_WAIT * 60) {
                        $validated[] = $t;
                    }
                }
            }
            Yii::$app->session->set($sessionKey, implode('|', $validated));
            if (count($validated) >= self::SPAM_MESSAGES) {
                return true;
            }
        }
        return false;
    }

    



    public function remove()
    {
        $transaction = static::getDb()->beginTransaction();
        try {
            $clearCache = false;
            if ($this->sender_status == self::STATUS_NEW) {
                $clearCache = true;
            }
            $this->scenario = 'remove';
            if (empty($this->messageReceivers)) {
                if (!$this->delete()) {
                    throw new Exception('Message removing error!');
                }
                if ($clearCache) {
                    Podium::getInstance()->podiumCache->deleteElement('user.newmessages', $this->sender_id);
                }
                $transaction->commit();
                return true;
            }
            $allDeleted = true;
            foreach ($this->messageReceivers as $mr) {
                if ($mr->receiver_status != MessageReceiver::STATUS_DELETED) {
                    $allDeleted = false;
                    break;
                }
            }
            if ($allDeleted) {
                foreach ($this->messageReceivers as $mr) {
                    if (!$mr->delete()) {
                        throw new Exception('Received message removing error!');
                    }
                }
                if (!$this->delete()) {
                    throw new Exception('Message removing error!');
                }
                if ($clearCache) {
                    Podium::getInstance()->podiumCache->deleteElement('user.newmessages', $this->sender_id);
                }
                $transaction->commit();
                return true;
            }
            $this->sender_status = self::STATUS_DELETED;
            if (!$this->save()) {
                throw new Exception('Message status changing error!');
            }
            if ($clearCache) {
                Podium::getInstance()->podiumCache->deleteElement('user.newmessages', $this->sender_id);
            }
            $transaction->commit();
            return true;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Log::error($e->getMessage(), $this->id, __METHOD__);
        }
        return false;
    }

    





    public function podiumReport($post = null)
    {
        try {
            if (empty($post)) {
                throw new Exception('Reported post missing');
            }
            $logged = User::loggedId();
            $this->sender_id = $logged;
            $this->topic = Yii::t('podium/view', 'Complaint about the post #{id}', ['id' => $post->id]);
            if (Podium::getInstance()->podiumConfig->get('use_wysiwyg') == '0') {
                $this->content .= "\n\n---\n"
                            . '[' . Yii::t('podium/view', 'Direct link to this post') . '](' . Url::to(['forum/show', 'id' => $post->id]) . ')'
                            . "\n\n---\n"
                            . '**' . Yii::t('podium/view', 'Post contents') . '**'
                            . $post->content;
            } else {
                $this->content .= '<hr>'
                            . Html::a(Yii::t('podium/view', 'Direct link to this post'), ['forum/show', 'id' => $post->id])
                            . '<hr>'
                            . '<p>' . Yii::t('podium/view', 'Post contents') . '</p>'
                            . '<blockquote>' . $post->content . '</blockquote>';
            }
            $this->sender_status = self::STATUS_DELETED;
            if (!$this->save()) {
                throw new Exception('Saving complaint error!');
            }

            $receivers = [];
            $mods = $post->forum->mods;
            $stamp = time();
            foreach ($mods as $mod) {
                if ($mod != $logged) {
                    $receivers[] = [$this->id, $mod, self::STATUS_NEW, $stamp, $stamp];
                }
            }
            if (empty($receivers)) {
                throw new Exception('No one to send report to');
            }
            if (!Podium::getInstance()->db->createCommand()->batchInsert(
                    MessageReceiver::tableName(),
                    ['message_id', 'receiver_id', 'receiver_status', 'created_at', 'updated_at'],
                    $receivers
                )->execute()) {
                throw new Exception('Reports saving error!');
            }

            Podium::getInstance()->podiumCache->delete('user.newmessages');
            Log::info('Post reported', $post->id, __METHOD__);
            return true;
        } catch (Exception $e) {
            Log::error($e->getMessage(), null, __METHOD__);
        }
        return false;
    }

    



    public function markRead()
    {
        if ($this->sender_status == self::STATUS_NEW) {
            $this->sender_status = self::STATUS_READ;
            if ($this->save()) {
                Podium::getInstance()->podiumCache->deleteElement('user.newmessages', $this->sender_id);
            }
        }
    }

    




    public function getParsedContent()
    {
        if (Podium::getInstance()->podiumConfig->get('use_wysiwyg') == '0') {
            $parser = new GithubMarkdown();
            $parser->html5 = true;
            return $parser->parse($this->content);
        }
        return $this->content;
    }
}
