<?php

/**
 * @var $this \yii\web\View
 * @var $url \common\models\User
 */
use yii\helpers\Url;

$baseUrl = Url::to('@frontendUrl');
?>
<table width="600" border="0" align="center" cellpadding="0" cellspacing="0">
    <?php echo $this->render('mail_header', ['baseUrl' => $baseUrl]); ?>
    <tr>
        <td bgcolor="#FFFFFF">
            <table width="550" border="0" align="center" cellpadding="0" cellspacing="0">
                <tbody>
                    <tr>
                        <td style="color: rgb(102, 102, 102);font-size: 16px;line-height: normal;">
                            <strong>Hello, <?php echo $username; ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td height="20"></td>
                    </tr>
                    <tr>
                        <td style="color: rgb(102, 102, 102);font-size: 16px;line-height: normal;">
                            <?php echo $otp . ' is the OTP for accessing your DrsPanel account. PLS DO NOT SHARE IT WITH ANYONE.' ?>
                        </td>
                    </tr>
                    <tr>
                        <td height="20" align="center" valign="top"></td>
                    </tr>
            </table>
        </td>
    </tr>
    <?php echo $this->render('mail_footer', ['baseUrl' => $baseUrl]); ?>
</table>