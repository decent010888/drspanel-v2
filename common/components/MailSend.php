<?php

namespace common\components;

use Yii;
use common\commands\SendEmailCommand;
use yii\helpers\Url;
use yii\web\View;
use common\models\UserProfile;

class MailSend {

    public static function sendOtpMail($userDetail, $userD, $otp) {
        
        Yii::$app->commandBus->handle(new SendEmailCommand([
            'subject' => 'Verify your email',
            'view' => 'sendotpmail',
            'to' => $userD['email'],
            'from' => ['contact@drspanel.in' => 'Drspanel'],
            'params' => [
                'otp' => $otp,
                'username' => $userDetail['name'],
            ]
        ]));
    }

}
