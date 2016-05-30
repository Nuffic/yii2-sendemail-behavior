<?php
namespace nuffic\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\ModelEvent;
use yii\di\Instance;

/**
 * SendEmail behavior
 */
class SendEmail extends Behavior
{
    public $emailField='email';
    public $view;
    public $subject;
    public $from;
    public $data;

    public $mailer='mailer';

    const EVENT_BEFORE_SEND='beforeSend';
    const EVENT_AFTER_SEND='afterSend';

    public function send()
    {
        return $this->sendInternal();
    }

    protected function sendInternal()
    {
        if (!$this->owner->beforeSend()) {
            return false;
        }
        $sends = $this->realSend();
        $this->owner->afterSend();
        return $sends;
    }

    public function beforeSend()
    {
        $event = new ModelEvent;
        $this->owner->trigger(self::EVENT_BEFORE_SEND, $event);
        return $event->isValid;
    }

    public function afterSend()
    {
        $event = new ModelEvent;
        $this->owner->trigger(self::EVENT_AFTER_SEND, $event);
    }

    private function realSend()
    {
        $mailer = Instance::ensure($this->mailer, 'yii\mail\BaseMailer');
        $email = $mailer->compose($this->getView(), $this->getData())
            ->setTo($this->getTo())
            ->setFrom($this->getFrom())
            ->setSubject($this->getSubject());

        return $mailer->send($email);
    }

    private function getTo()
    {
        if (is_callable($this->emailField)) {
            return call_user_func($this->emailField, $this->owner);
        }

        return $this->owner->{$this->emailField};
    }

    private function getView()
    {
        if (is_callable($this->view)) {
            return call_user_func($this->view, $this->owner);
        }

        return $this->view;
    }
    private function getFrom()
    {
        if (!$this->from) {
            return Yii::$app->params['adminEmail'];
        }

        if (is_callable($this->from)) {
            return call_user_func($this->from, $this->owner);
        }

        return $this->from;
    }

    private function getSubject()
    {
        if (is_callable($this->subject)) {
            return call_user_func($this->subject, $this->owner);
        }

        return $this->subject;
    }


    public function getData()
    {
        if (!is_callable($this->data)) {
            throw new \Exception("data is not callable");
        }

        return call_user_func($this->data, $this->owner, $this);
    }
}
