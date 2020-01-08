<?php

namespace common\components;

use common\models\UserAppointment;
use common\models\UserNotification;
use common\models\UserReminder;
use yii\db\Query;
use yii\helpers\Url;
use common\models\User;

class Notifications {

    public static function signupNotify($user_id) {
        if ($user_id > 0) {
            $user = User::findOne($user_id);
            $token = $user->token;
            $message = 'Thank you for choosing DrsPanel';
            $notification_data = ['type' => 'signup_notify', 'message' => $message, 'user_id' => $user_id];
            $sendNotification = Notifications::createNotification($user, $token, $type = 'signup_notify', 'DrsPanel Registration', $message, $notification_id = $user_id, $device_type = $user->device_type, $notification_data);
        }
        return true;
    }

    public static function appointmentSmsNotification($appointment_id, $type = '', $by = '') {
        $appointment = UserAppointment::findOne($appointment_id);
        $phones = $appointment->user_phone;
        $data = array();
        $notification = array();
        $user = array();
        $token = '';
        if ($appointment->user_id > 0) {
            $user = User::findOne($appointment->user_id);
            $token = $user->token;
        }

        if ($type == 'appointment') {

            $appointment_date = date('d/m/Y', strtotime($appointment->date));
            $appointment_time = date('h:i a', $appointment->appointment_time);
            $send_message = 'Dear ' . $appointment->user_name . ' your appointment is booked with ' . $appointment->doctor_name . ' on ' . $appointment_date . ' ' . $appointment_time . ' with token ' . $appointment->token . '. PLS reach before 15 min of your time.';

            if ($by == 'patient') {
                $notify_message = 'Dear ' . $appointment->user_name . ' your appointment is booked with ' . $appointment->doctor_name . ' on ' . $appointment_date . ' ' . $appointment_time . ' with token ' . $appointment->token . '. PLS reach before 15 min of your time.';
                if ($appointment->user_id > 0) {
                    $notification_data = ['type' => 'appointment', 'message' => $notify_message, 'user_id' => $user->id, 'appointment_id' => $appointment->id];
                    $sendNotification = Notifications::createNotification($user, $token, $type = 'appointment', 'DrsPanel Appointment Booking', $notify_message, $notification_id = $appointment_id, $device_type = $user->device_type, $notification_data);
                }
            }
            $sendSms = Notifications::send_sms($send_message, $phones, 'No', 91, 1);
        } elseif ($type == 'cancelled' && $by == 'patient') {
            $appointment_date = date('d/m/Y', strtotime($appointment->date));
            $appointment_time = date('h:i a', $appointment->appointment_time);
            $send_message = 'Dear ' . $appointment->user_name . 'your appointment with ' . $appointment->doctor_name . ' on ' . $appointment_date . ', ' . $appointment_time . ' with token ' . $appointment->token . '. has been cancelled by you. . Please Rate us.';

            $notify_message = 'Dear ' . $appointment->user_name . 'your appointment with ' . $appointment->doctor_name . ' on ' . $appointment_date . ', ' . $appointment_time . ' with token ' . $appointment->token . '. has been cancelled by you. . Please Rate us.';

            if ($appointment->user_id > 0) {
                $notification_data = ['type' => 'appointment', 'message' => $notify_message,
                    'user_id' => $user->id, 'appointment_id' => $appointment->id];
                $sendNotification = Notifications::createNotification($user, $token, $type = 'appointment', 'DrsPanel Appointment Cancelled', $notify_message, $notification_id = $appointment_id, $device_type = $user->device_type, $notification_data);
            }
            $sendSms = Notifications::send_sms($send_message, $phones, 'No', 91, 1);
        } elseif ($type == 'cancelled' && $by == 'doctor') {
            $appointment_date = date('d/m/Y', strtotime($appointment->date));
            $appointment_time = date('h:i a', $appointment->appointment_time);
            $send_message = 'Dear ' . $appointment->user_name . ' your appointment with ' . $appointment->doctor_name . ' on ' . $appointment_date . ' ' . $appointment_time . ' with token ' . $appointment->token . '. has been cancelled by doctor due to any Emergency.';

            $notify_message = 'Dear ' . $appointment->user_name . ' your appointment with ' . $appointment->doctor_name . ' on ' . $appointment_date . ' ' . $appointment_time . ' with token ' . $appointment->token . '. has been cancelled by doctor due to any Emergency.';

            if ($appointment->user_id > 0) {
                $notification_data = ['type' => 'appointment', 'message' => $notify_message,
                    'user_id' => $user->id, 'appointment_id' => $appointment->id];
                $sendNotification = Notifications::createNotification($user, $token, $type = 'appointment', 'DrsPanel Appointment Cancelled', $notify_message, $notification_id = $appointment_id, $device_type = $user->device_type, $notification_data);
            }
            $sendSms = Notifications::send_sms($send_message, $phones, 'No', 91, 1);
        } else {
            $send_message = '';
        }
        return true;
    }

    public static function reminderNotification($reminder_id) {
        $reminder = UserReminder::findOne($reminder_id);
        if (!empty($reminder)) {
            $appointment = UserAppointment::findOne($reminder->appointment_id);
            $user = array();
            $token = '';
            $appointment_date = date('d/m/Y', strtotime($appointment->date));
            $appointment_time = date('h:i a', $appointment->appointment_time);

            $message = 'Dear ' . $appointment->user_name . ' your appointment is booked with ' . $appointment->doctor_name . ' on ' . $appointment_date . ' ' . $appointment_time . ' with token ' . $appointment->token . '. PLS reach before 15 min of your time.';
            if ($appointment->user_id > 0) {
                $user = User::findOne($appointment->user_id);
                $token = $user->token;
                $phones = $appointment->user_phone;
                $notification_data = ['type' => 'appointment', 'message' => $message, 'user_id' => $user->id, 'appointment_id' => $appointment->id];
                $sendNotification = Notifications::createNotification($user, $token, $type = 'reminder_notification', 'Reminder Notification', $message, $notification_id = $appointment->id, $device_type = $user->device_type, $notification_data);

                $sendSms = Notifications::send_sms($message, $phones, 'No', 91, 1);
            }
            return true;
        }
        return false;
    }

    public static function thankyou($user_id) {
        if ($user_id > 0) {
            $user = User::findOne($user_id);
            $token = $user->token;
            $message = 'Thank you for visiting the clinic/hospital please give rating';

            $notification_data = ['type' => 'thanku_visiting', 'message' => $message, 'user_id' => $user->id];
            $sendNotification = Notifications::createNotification($user, $token, $type = 'thanku_visiting', 'DrsPanel Appointment Completed', $message, $notification_id = $user_id, $device_type = $user->device_type, $notification_data);
        }
        return true;
    }

    public static function shiftStartNotification($doctor_id, $schedule_id, $date = NULL, $message = '') {
        $appointments = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'schedule_id' => $schedule_id,
                    'date' => $date, 'status' => array(UserAppointment::STATUS_PENDING, UserAppointment::STATUS_AVAILABLE)])->all();
        foreach ($appointments as $key => $appointment) {
            if ($appointment->user_id > 0) {
                $user = User::findOne($appointment->user_id);
                $phones = $appointment->user_phone;
                $token = $user->token;
                $appointment_time = date('h:i a', $appointment->appointment_time);

                $message = 'Dear ' . $appointment->user_name . ' your shift with ' . $appointment->doctor_name . ' token no ' . $token . ' today at ' . $appointment_time . ' has been started. PLS reach the place before 15 min of your time.';

                //$message = 'Your doctor ' . $appointment->doctor_name . ' started the shift ' . $appointment_time;
                $notification_data = ['type' => 'shift_status', 'message' => $message, 'user_id' => $user->id, 'appointment_id' => $appointment->id];
                $sendNotification = Notifications::createNotification($user, $token, $type = 'shift_status', 'DrsPanel Shift Start', $message, $notification_id = $appointment->id, $device_type = $user->device_type, $notification_data);
                $sendSms = Notifications::send_sms($message, $phones, 'No', 91, 1);
            }
        }
        return true;
    }

    public static function appointmentUpdateNotification($appointment_id, $status) {
        $appointment = UserAppointment::findOne($appointment_id);
        $user = array();
        $token = '';
        if ($status == 'skip') {
            $message = 'You have missed your token no ' . $appointment->token . ' the doctor shifted your appointment to last no in the list.';
        } else {
            $message = 'You have not paid already to the doctor please confirm with your doctor.';
        }
        if ($appointment->user_id > 0) {
            $user = User::findOne($appointment->user_id);
            $token = $user->token;
            $notification_data = [
                'type' => 'appointment',
                'message' => $message,
                'user_id' => $user->id,
                'appointment_id' => $appointment->id
            ];
            $sendNotification = Notifications::createNotification($user, $token, $type = 'appointment', 'Appointment Update Notification', $message, $notification_id = $appointment->id, $device_type = $user->device_type, $notification_data);
        }
        return true;
    }

    public static function shiftUpdateNotification($doctor_id, $schedule_id, $date, $live_token) {
        $nextappointment = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'appointment_shift' => $schedule_id, 'date' => $date, 'status' => UserAppointment::STATUS_PENDING])
                        ->orderBy('token asc')->one();
        if ($nextappointment->user_id > 0) {
            $user = User::findOne($nextappointment->user_id);
            $token = $user->token;
            $message = 'You are the next token no.' . $nextappointment->token;
            if ($user->device_type == 'ios') {
                $notification_data = ['type' => 'live_status', 'message' => $message, 'user_id' => $user->id, 'token' => $live_token];
                $sendNotification = Notifications::createNotification($user, $token, $type = 'live_status', 'DrsPanel Shift Updated', $message, $notification_id = $nextappointment->id, $device_type = $user->device_type, $notification_data);
            }
        }

        $appointments = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'appointment_shift' => $schedule_id, 'date' => $date, 'status' => UserAppointment::STATUS_PENDING])->all();
        foreach ($appointments as $key => $appointment) {
            if ($appointment->user_id > 0) {
                $user = User::findOne($appointment->user_id);
                $token = $user->token;
                $message = 'Token no ' . $live_token . ' is appointing by doctor';
                if ($user->device_type == 'android') {
                    $notification_data = ['type' => 'live_status', 'message' => $message, 'user_id' => $user->id, 'token' => $token];
                    $sendNotification = Notifications::createNotification($user, $token, $type = 'live_status', 'DrsPanel Shift Updated', $message, $notification_id = $appointment->id, $device_type = $user->device_type, $notification_data);
                }
            }
        }

        return true;
    }

    public static function shiftCompleteNotification($doctor_id, $schedule_id, $date = NULL, $message) {
        $appointments = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'appointment_shift' => $schedule_id, 'date' => $date, 'status' => UserAppointment::STATUS_PENDING])->all();
        foreach ($appointments as $key => $appointment) {
            if ($appointment->user_id > 0) {
                $user = User::findOne($appointment->user_id);
                $token = $user->token;
                $appointment_time = date('h:i a', $appointment->appointment_time);
                $message = 'Your doctor ' . $appointment->doctor_name . ' ended the shift ' . $appointment_time . ', Please contact doctor for further details.';
                $notification_data = ['type' => $user->device_type, 'message' => $message, 'user_id' => $user->id];
                $sendNotification = Notifications::createNotification($user, $token, $type = 'shift_status', 'DrsPanel Shift Completed', $message, $notification_id = $appointment->id, $device_type = $user->device_type, $notification_data);
            }
        }
        return true;
    }

    public static function appointmentUsers($doctor_id, $shift, $date = NULL) {
        $lists = new Query();
        $lists = User::find();
        $lists->alias('u');
        $lists->innerJoin('user_appointment as ua', 'ua.user_id = u.id');
        $lists->andWhere(['ua.doctor_id' => $doctor_id, 'ua.appointment_shift' => $shift, 'ua.status' => 'pending']);
        if ($date)
            $lists->andWhere(['ua.date' => $date]);
        $lists->select(['u.token as device_token', 'ua.id as appointment_id', 'ua.status', 'ua.appointment_time',
            'ua.token', 'ua.user_gender']);
        $lists->all();
        $command = $lists->createCommand();
        $lists = $command->queryAll();
        return $lists;
    }

    public static function before2hNotification($appdata) {
        $notification_id = $appdata->id;
        $phones = $appdata->user_phone;
        $data = $notification = $user = array();
        $token = '';
        if ($appdata->user_id > 0) {
            $user = User::findOne($appdata->user_id);
            $token = $user->token;
        }

        $appointment_date = date('d/m/Y', strtotime($appdata->date));
        $appointment_time = date('h:i a', $appdata->appointment_time);
        $notify_message = 'Dear ' . $appdata->user_name . ' your appointment is booked with ' . $appdata->doctor_name . ' on ' . $appointment_date . ' ' . $appointment_time . ' with token ' . $appdata->token . '. PLS reach before 15 min of your time.';

        if ($appdata->user_id > 0) {
            $notification_data = ['type' => 'appointment', 'message' => $notify_message, 'user_id' => $user->id, 'appointment_id' => $appdata->id];
            Notifications::createNotification($user, $token, $type = 'appointment', 'DrsPanel Appointment Schedule', $notify_message, $notification_id, $user->device_type, $notification_data);
        }
        Notifications::send_sms($notify_message, $phones, 'No', 91, 1);
        return true;
    }

    public static function createNotification($user, $token, $type, $title, $notify_message, $notification_id, $device_type = '', $notification_data) {
        $notification_c = new UserNotification();
        $notification_c->sender_id = $user->id;
        $notification_c->receiver_id = $user->id;
        $notification_c->message = $notify_message;
        $notification_c->type = $type;
        $notification_c->read_status = 0;
        $notification_c->save();
        if ($device_type == 'android') {
            $notification_data = [
                'to' => $token,
                'data' => [
                    'title' => $title,
                    'body' => $notify_message,
                    'notificationId' => $notification_id,
                    'show_in_foreground' => true,
                    'priority' => 'high',
                    'actions' => 'com.drspanel.LAUNCHER',
                    'color' => '#B1152A',
                    'autoCancel' => true,
                    'channelId' => 'my_default_channel',
                    'clickAction' => 'com.drspanel.LAUNCHER',
                    'largeIcon' => 'ic_launcher',
                    'lights' => true,
                    'icon' => 'ic_notif',
                    'notification_data' => $notification_data
                ],
                'priority' => 'high'
            ];
            Notifications::sendGCM($notification_data);
        } elseif ($user->device_type == 'ios') {
            $notification_data = [
                'to' => $token,
                'content_available' => false,
                'notification' => [
                    'title' => $title,
                    'body' => $notify_message,
                    'notificationId' => $notification_id,
                    'show_in_foreground' => true,
                    'channelId' => 'my_default_channel',
                    'sound' => 'default',
                    'vibrate' => '300',
                    'notification_data' => $notification_data
                ],
            ];
            Notifications::sendGCM($notification_data);
        }
        return true;
    }

    public static function sendGCM($notification_data = []) {
        $url = 'https://fcm.googleapis.com/fcm/send';

        $fields = json_encode($notification_data);
        $headers = [
            'Authorization: key=' . env("GCM_SERVER_KEY"),
            'Content-Type: application/json'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        $result = curl_exec($ch);
        curl_close($ch);
        return true;
    }

    public static function send_sms($text = '', $to = '', $issos = 'NO', $country_code = 91, $otpcheck = 0) {

        include_once('../../textlocal.class.php');
        $country_code = str_replace("+", "", $country_code);
        if ($otpcheck == 0) {
            if (is_array($to)) {
                $numbers = array();
                foreach ($to as $t) {
                    $numbers[] = "91" . $t;
                }
            } else {
                $numbers[] = "91" . $to;
            }
            $sender = 'TXTLCL';
            $text = strip_tags($text);
            $textlocal = new \Textlocal('harshitchaudhary15@gmail.com', 'Drspanel@90');
        } else {
            $to = "91" . $to;
            $numbers = array($to);
            $sender = 'DRSPNL';
            $text = strip_tags($text);
            $textlocal = new \Textlocal('harshitchaudhary15@gmail.com', 'Drspanel@90');
        }
        try {
            $result = $textlocal->sendSms($numbers, $text, $sender);
            //echo "<pre>";
            //print_r($result);
            //die;
        } catch (\Exception $e) {
            //echo "<pre>";
            //print_r($e->getMessage());
            //die;
            die('Error: ' . $e->getMessage());
            return true;
        }
        return true;
    }

    public static function getSender_name() {
        // Account details
        $apiKey = urlencode('AEdbtc5J/po-qcaOwYsY6P1ZNaSDDBKDS3zy55giq5');

        // Prepare data for POST request
        $data = array('apikey' => $apiKey);

        // Send the POST request with cURL
        $ch = curl_init('https://api.textlocal.in/get_sender_names/');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        // Process your response here
        echo"<pre>";
        print_r($response);
        die;
    }

}
