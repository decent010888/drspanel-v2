<?php

use yii\helpers\Url;

$baseUrl = Url::to('@frontendUrl');
?>
<tr>
    <td height="70" bgcolor="#f5f5f5">
        <table width="600" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td width="26"></td>
                <td width="547">
                    <img src="<?php echo $baseUrl ?>/images/maillogo.png" width="210">
                </td>
                <td width="27"></td>
            </tr>
        </table>
    </td>
</tr>

<tr>
    <td bgcolor="#FFFFFF">
        <table width="600" border="0" cellspacing="0" cellpadding="0">
            <tr height="20">
                <td width="26" height="22"></td>
                <td width="549" height="22"></td>
                <td width="25" height="22"></td>
            </tr>
            <tr>
                <td width="26"></td>
                <td style="color: rgb(102, 102, 102); font-size: 14px;" width="549">© Drspanel <?php echo date('Y') ?>. All rights reserved.<br></td>
                <td width="25"></td>
            </tr>
            <tr>
                <td></td>
                <td style="color: rgb(102, 102, 102);font-size: 14px;">Important Notes - Privacy Statement</td>
                <td></td>
            </tr>
            <tr>
                <td height="20"></td>
                <td height="20"></td>
                <td height="20"></td>
            </tr>
            <tr>
                <td></td>
                <td style="color: rgb(102, 102, 102); font-size: 14px;">Please do not reply to this email - use the contact points on the Drspanel </td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td style="color: rgb(102, 102, 102);font-size: 14px;">website to contact us. https://drspanel.in/contact-us</td>
                <td></td>
            </tr>
            <tr height="25">
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td style="color: rgb(102, 102, 102); text-decoration:none; font-size: 14px;">
                    <a href="<?php echo $baseUrl . '/page/about-us'; ?>" style="color: rgb(102, 102, 102); text-decoration:none; font-size: 15px;">About Us</a> |
                    <a href="<?php echo $baseUrl . '/contact-us'; ?>" style="color: rgb(102, 102, 102); text-decoration:none; font-size: 15px;">Contact us</a> |
                    <a href="<?php echo $baseUrl . '/page/terms-condition   '; ?>" style="color: rgb(102, 102, 102); text-decoration:none; font-size: 15px;">Terms & Conditions</a> |
                    <a href="<?php echo $baseUrl . '/page/privacy-policy'; ?>" style="color: rgb(102, 102, 102); text-decoration:none; font-size: 15px;">Privacy</a></td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td height="35"></td>
                <td></td>
                <td></td>
            </tr>
        </table></td>
</tr>