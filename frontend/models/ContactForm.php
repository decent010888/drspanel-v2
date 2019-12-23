<?php

namespace frontend\models;

use Yii;
use yii\base\Model;
use common\models\ContactUs;

/**
 * ContactForm is the model behind the contact form.
 */
class ContactForm extends Model
{
    public $name;
    public $email;
    public $subject;
    public $body;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // name, email, subject and body are required
            [['name', 'email', 'subject', 'body'], 'required'],
            // We need to sanitize them
            [['name', 'subject', 'body'], 'filter', 'filter' => 'strip_tags'],
            // email has to be a valid email address
            ['email', 'email'],
            // verifyCode needs to be entered correctly

        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'name' => Yii::t('frontend', 'Name'),
            'email' => Yii::t('frontend', 'Email'),
            'subject' => Yii::t('frontend', 'Subject'),
            'body' => Yii::t('frontend', 'Body'),
        ];
    }

    /**
     * Sends an email to the specified email address using the information collected by this model.
     * @param  string  $email the target email address
     * @return boolean whether the model passes validation
     */
    public function contact($email)
    {
        if ($this->validate()) {

            $contact_data['name']   =   $this->name;
            $contact_data['email']   =   $this->email;
            $contact_data['subject']   =   $this->subject;
            $contact_data['message']   =   $this->body;

            $contact = new ContactUs();
            $contact->name = $this->name;
            $contact->email = $this->email;
            $contact->subject = $this->subject;
            $contact->message = $this->body;
            $contact->save();

            return Yii::$app->mailer->compose()
                ->setTo($email)
                ->setFrom(Yii::$app->params['robotEmail'])
                ->setReplyTo([$this->email => $this->name])
                ->setSubject($this->subject)
                ->setTextBody($this->body)
                ->send();
        } else {
            return false;
        }
    }
}
