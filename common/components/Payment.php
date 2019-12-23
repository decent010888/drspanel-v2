<?php

namespace common\components;

use common\models\Transaction;
use common\models\UserAppointment;
use common\models\UserAppointmentTemp;
use common\models\UserScheduleSlots;
use Yii;
use common\models\User;

require_once('../../paytm/lib/config_paytm.php');
require_once('../../paytm/lib/encdec_paytm.php');

class Payment {

    public static function walletRechargePaytm($user_id, $appointment_id, $amount, $merchanttype = 'web') {
        // Payment::includePaytmFiles();
        $amount = Payment::format_number($amount);
        $patient_detail = Payment::get_user_details_by_id($user_id);

        $checkSum = "";
        $paramList = array();

        $ORDER_ID = $user_id . "AA" . $appointment_id . "patientwallet" . time();
        $CUST_ID = "PAA" . $user_id;
        $TXN_AMOUNT = $amount;

        if ($merchanttype == 'web') {
            $website_mer = PAYTM_MERCHANT_WEBSITE;
            $channel_id = CHANNEL_ID;
            $callback_url = 'http://192.168.1.40/drspanel/search/paytm-wallet-callback?appointment_id=' . $appointment_id;
        } else {
            $website_mer = PAYTM_MERCHANT_WEBSITE_APP;
            $channel_id = CHANNEL_ID_APP;
            $callback_url = 'http://192.168.1.40/drspanel/api-drspanel/paytm-wallet-callback?appointment_id=' . $appointment_id;
        }

        // Create an array having all required parameters for creating checksum.
        $paramList["MID"] = PAYTM_MERCHANT_MID;
        $paramList["ORDER_ID"] = $ORDER_ID;
        $paramList["CUST_ID"] = $CUST_ID;
        $paramList["INDUSTRY_TYPE_ID"] = INDUSTRY_TYPE_ID;
        $paramList["CHANNEL_ID"] = $channel_id;
        $paramList["TXN_AMOUNT"] = $TXN_AMOUNT;
        $paramList["WEBSITE"] = $website_mer;
        $paramList["CALLBACK_URL"] = $callback_url;
        if (!empty($patient_detail['email'])) {
            $paramList["EMAIL"] = $patient_detail['email'];
        }
        if (!empty($patient_detail['phone'])) {
            $paramList["MOBILE_NO"] = $patient_detail['phone'];
        }
        $checkSum = getChecksumFromArray($paramList, PAYTM_MERCHANT_KEY);
        //$paramList["CHECKSUMHASH"] = $checkSum;

        if ($merchanttype == 'web') {
            $html = array('list' => $paramList, 'txn_url' => PAYTM_TXN_URL, 'checkSum' => $checkSum);
        } else {
            $html = '<html><head>
        <title>DRSPANEL</title></head><body>
        <center><h1>Please do not refresh this page...</h1></center>';
            $html .= '<form method="post" action="' . PAYTM_TXN_URL . '" name="f1">
        <table border="1">
            <tbody>';

            foreach ($paramList as $name => $value) {
                $html .= '<input type="hidden" name="' . $name . '" value="' . $value . '">';
            }
            $html .= '<input type="hidden" name="CHECKSUMHASH" value="' . $checkSum . '">';

            $html .= '</tbody></table><script type="text/javascript"> document.f1.submit(); </script></form></body></html>';
        }

        return $html;
    }

    /* public static function includePaytmFiles() {
      $filesArr=get_required_files();
      $searchString=PAYTM_FILES_FIRST;
      if (!in_array($searchString, $filesArr)) {
      require PAYTM_FILES_FIRST;
      }
      $searchString1=PAYTM_FILES_SECOND;
      if (!in_array($searchString1, $filesArr)) {
      require PAYTM_FILES_SECOND;
      }
      } */

    public static function format_number($number) {
        return str_replace(',', '', number_format($number, 2));
    }

    public static function get_user_details_by_id($id) {
        $user = User::findOne($id);
        $output = array();
        if (!empty($user)) {
            $output['id'] = $user->id;
            $output['name'] = $user->userProfile->name;
            $output['email'] = $user->email;
            $output['phone'] = $user->phone;
            $output['country_mobile_code'] = $user->countrycode;
        }
        return $output;
    }

    public static function paytm_wallet_callback($data, $request) {
        $paytmParams = $data;
        $merchantKey = PAYTM_MERCHANT_KEY;
        $paytmChecksum = isset($data["CHECKSUMHASH"]) ? $data["CHECKSUMHASH"] : "";
        $isValidChecksum = verifychecksum_e($paytmParams, $merchantKey, $paytmChecksum);

        if ($isValidChecksum == "TRUE") {
            if (!empty($data) && isset($data['STATUS'])) {
                $status = $data['STATUS'];
                $data = Payment::paytm_status_api($data);

                file_put_contents('/var/www/html/drspanel/frontend/runtime/logs/paymentpay_' . env('LOG_FILE') . '.log', json_encode($data));

                $appointment_id = $request['appointment_id'];
                $appointment = UserAppointmentTemp::find()->where(['id' => $appointment_id])->one();
                if ($status == "TXN_SUCCESS") {
                    $appointment->payment_status = UserAppointment::PAYMENT_COMPLETED;
                    if ($appointment->save()) {
                        $transaction = Transaction::find()->where(['temp_appointment_id' => $appointment_id])->one();
                        $appointment_id = $transaction->appointment_id;
                        if ($appointment_id > 0) {
                            $transaction->status = 'completed';
                            $transaction->paytm_response = json_encode($data);
                            if ($transaction->save()) {
                                $addLog = Logs::transactionLog($transaction->id, 'Transaction updated');
                            }
                        } else {
                            $appointment_log = Logs::addAppointment($request['appointment_id'], $data);
                            if ($appointment_log > 0) {
                                $data['appointment_id'] = $appointment_log;
                            }
                        }
                    } else {
                        
                    }
                } elseif ($status == "TXN_FAILURE") {
                    $appointment->payment_status = UserAppointment::PAYMENT_PENDING;
                    if ($appointment->save()) {

                        $schedule_id = $appointment->schedule_id;
                        $slot_id = $appointment->slot_id;
                        $slot = UserScheduleSlots::find()->where(['id' => $slot_id, 'schedule_id' => $schedule_id])->one();
                        $slot->status = 'available';
                        $slot->save();

                        $transaction = Transaction::find()->where(['temp_appointment_id' => $appointment_id])->one();
                        $transaction->status = 'failed';
                        $transaction->paytm_response = json_encode($data);
                        if ($transaction->save()) {
                            $addLog = Logs::transactionLog($transaction->id, 'Transaction failed');
                        }
                    }
                } else {
                    $appointment->payment_status = UserAppointment::PAYMENT_PENDING;
                    if ($appointment->save()) {
                        $transaction = Transaction::find()->where(['temp_appointment_id' => $appointment_id])->one();
                        $appointment_id = $transaction->appointment_id;
                        if ($appointment_id > 0) {
                            $transaction->status = 'completed';
                            $transaction->paytm_response = json_encode($data);
                            if ($transaction->save()) {
                                $addLog = Logs::transactionLog($transaction->id, 'Transaction pending');
                            }
                        } else {
                            // $appointment_log=Logs::addAppointment($request['appointment_id'],$data);
                        }
                    } else {
                        
                    }
                }
            } else {
                $appointment_id = $request['appointment_id'];
                $appointment = UserAppointmentTemp::find()->where(['id' => $appointment_id])->one();
                $appointment->payment_status = UserAppointment::PAYMENT_PENDING;
                if ($appointment->save()) {
                    $schedule_id = $appointment->schedule_id;
                    $slot_id = $appointment->slot_id;
                    $slot = UserScheduleSlots::find()->where(['id' => $slot_id, 'schedule_id' => $schedule_id])->one();
                    $slot->status = 'available';
                    $slot->save();

                    $transaction = Transaction::find()->where(['temp_appointment_id' => $appointment_id])->one();
                    $transaction->status = 'failed';
                    $transaction->paytm_response = json_encode($data);
                    if ($transaction->save()) {
                        $addLog = Logs::transactionLog($transaction->id, 'Transaction failed');
                    }
                }
            }
        } else {
            //fail
        }
        return $data;
    }

    public static function paytm_status_api($data) {

        //Payment::includePaytmFiles();

        header("Pragma: no-cache");
        header("Cache-Control: no-cache");
        header("Expires: 0");


        $ORDER_ID = $data['ORDERID'];
        $requestParamList = array();
        $responseParamList = array();

        $requestParamList = array("MID" => PAYTM_MERCHANT_MID, "ORDERID" => $ORDER_ID);

        $checkSum = getChecksumFromArray($requestParamList, PAYTM_MERCHANT_KEY);
        //$checkSum = $data['CHECKSUMHASH'];
        $requestParamList['CHECKSUMHASH'] = urlencode($checkSum);

        $data_string = "JsonData=" . json_encode($requestParamList);
        //echo $data_string;

        $ch = curl_init();                    // initiate curl
        $url = PAYTM_STATUS_QUERY_URL; //Paytm server where you want to post data

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);  // tell curl you want to post something
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string); // define what you want to post
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return the output in string format
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch); // execute
        $info = curl_getinfo($ch);

        //file_put_contents('paytm_txn_parm.txt', print_r($data_string, true),FILE_APPEND);
        //file_put_contents('paytm_txn_parm.txt', print_r($output, true),FILE_APPEND);

        $data = json_decode($output, true);

        return $data;
    }

    public static function paytm_refund_api($data, $appointmentId) {

        //Payment::includePaytmFiles();

        header("Pragma: no-cache");
        header("Cache-Control: no-cache");
        header("Expires: 0");


        $MID = $data->MID;
        $ORDER_ID = $data->ORDERID;
        $TXNID = $data->TXNID;
        $REFID = 'REFID' . $appointmentId;
        $TXNAMOUNT = $data->TXNAMOUNT;
        $paytmParams = array();

        /* body parameters */
        $paytmParams["body"] = array(
            /* Find your MID in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys */
            "mid" => PAYTM_MERCHANT_MID,
            /* This has fixed value for refund transaction */
            "txnType" => "REFUND",
            /* Enter your order id for which refund needs to be initiated */
            "orderId" => $ORDER_ID,
            /* Enter transaction id received from Paytm for respective successful order */
            "txnId" => $TXNID,
            /* Enter numeric or alphanumeric unique refund id */
            "refId" => $REFID,
            /* Enter amount that needs to be refunded, this must be numeric */
            "refundAmount" => $TXNAMOUNT,
        );

        $checksum = getChecksumFromString(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), PAYTM_MERCHANT_KEY);

        /* head parameters */
        $paytmParams["head"] = array(
            /* This is used when you have two different merchant keys. In case you have only one please put - C11 */
            "clientId" => "C11",
            /* put generated checksum value here */
            "signature" => $checksum
        );

        /* prepare JSON string for request */
        $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

        //$requestParamList = array("MID" => $MID, "TXNTYPE" => "REFUND", "ORDERID" => $ORDER_ID, "TXNID" => $TXNID, "REFID" => $REFID, "REFUNDAMOUNT" => $TXNAMOUNT);
        //$checkSum = getRefundChecksumFromArray($requestParamList, PAYTM_MERCHANT_KEY);
        //$checkSum = $data['CHECKSUMHASH'];
        //$requestParamList['CHECKSUMHASH'] = urlencode($checkSum);
        //$data_string = "JsonData=" . json_encode($requestParamList);
        //echo $data_string;
        // initiate curl
        $url = PAYTM_REFUND_URL; //Paytm server where you want to post data
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);  // tell curl you want to post something
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); // define what you want to post
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return the output in string format
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch); // execute
        $info = curl_getinfo($ch);

        //file_put_contents('paytm_txn_parm.txt', print_r($data_string, true),FILE_APPEND);
        //file_put_contents('paytm_txn_parm.txt', print_r($output, true),FILE_APPEND);

        $data = json_decode($output, true);

        return $data;
    }

    public static function get_refund_status($data, $appointmentId) {

        //Payment::includePaytmFiles();

        header("Pragma: no-cache");
        header("Cache-Control: no-cache");
        header("Expires: 0");


        $ORDER_ID = $data->ORDERID;
        $REFID = $data->REFID;
        $paytmParams = array();

        /* body parameters */
        $paytmParams["body"] = array(
            /* Find your MID in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys */
            "mid" => PAYTM_MERCHANT_MID,
            /* Enter your order id for which refund needs to be initiated */
            "orderId" => $ORDER_ID,
            /* Enter numeric or alphanumeric unique refund id */
            "refId" => $REFID,
        );

        $checksum = getChecksumFromString(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), PAYTM_MERCHANT_KEY);

        /* head parameters */
        $paytmParams["head"] = array(
            /* This is used when you have two different merchant keys. In case you have only one please put - C11 */
            "clientId" => "C11",
            /* put generated checksum value here */
            "signature" => $checksum
        );

        /* prepare JSON string for request */
        $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

        //$requestParamList = array("MID" => $MID, "TXNTYPE" => "REFUND", "ORDERID" => $ORDER_ID, "TXNID" => $TXNID, "REFID" => $REFID, "REFUNDAMOUNT" => $TXNAMOUNT);
        //$checkSum = getRefundChecksumFromArray($requestParamList, PAYTM_MERCHANT_KEY);
        //$checkSum = $data['CHECKSUMHASH'];
        //$requestParamList['CHECKSUMHASH'] = urlencode($checkSum);
        //$data_string = "JsonData=" . json_encode($requestParamList);
        //echo $data_string;
        // initiate curl
        $url = PAYTM_REFUND_STATUS_URL; //Paytm server where you want to post data
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);  // tell curl you want to post something
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); // define what you want to post
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return the output in string format
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch); // execute
        $info = curl_getinfo($ch);

        //file_put_contents('paytm_txn_parm.txt', print_r($data_string, true),FILE_APPEND);
        //file_put_contents('paytm_txn_parm.txt', print_r($output, true),FILE_APPEND);

        $data = json_decode($output, true);

        return $data;
    }

}

?>
