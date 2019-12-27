<?php

namespace console\controllers;

use common\components\DrsPanel;
use common\components\Logs;
use common\components\Notifications;
use common\models\Groups;
use common\models\User;
use common\models\UserAppointment;
use common\models\UserProfile;
use common\models\UserReminder;
use Yii;
use yii\console\Controller;
use yii\db\Query;

class CronjobsController extends Controller {

    public function actionIndex() {

        //$semail=Email::emailgiftsetup(18);
        echo "cron service runnning";
    }

    public function actionUpdateSlotBlocked() {
        DrsPanel::checkBlockedSlots();
        return true;
    }

    public function actionUpdateDatabase() {
        //update rating to profile
        $updateRatingToProfile = DrsPanel::ratingUpdateToProfile();

        //update hospital speciality
        $lists = new Query();
        $lists = UserProfile::find();
        $lists->joinWith('user');
        $lists->where(['user_profile.groupid' => Groups::GROUP_HOSPITAL]);
        $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
            'user.admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]]);
        $updatespeciality = DrsPanel::addHospitalSpecialityCount($lists->createCommand()->queryAll());

        //update shift status with fees
        $shiftstatus_update = DrsPanel::userShiftsStatus();

        return true;
    }

    public function actionCancelBooking() {
        $date = date('Y-m-d');
        $pastcheck = strtotime('-1 day', strtotime($date));
        $pastdate = date('Y-m-d', $pastcheck);
        $pastcheck = strtotime($pastdate);


        $status_type = array(UserAppointment::STATUS_AVAILABLE, UserAppointment::STATUS_PENDING, UserAppointment::STATUS_SKIP, UserAppointment::STATUS_ACTIVE);
        $appointments = UserAppointment::find()->where(['status' => $status_type])
                        ->andWhere(['<=', 'date', $pastdate])->all();

        foreach ($appointments as $appointment) {
            $appointment->status = UserAppointment::STATUS_CANCELLED;
            if ($appointment->save()) {
                $addLog = Logs::appointmentLog($appointment->id, 'Appointment cancelled by doctor');
            } else {
                echo "<pre>";
                print_r($appointment->getErrors());
                die;
            }
        }
        return true;
    }

    public function actionReminderNotification() {
        $time = time();
        $reminders = UserReminder::find()->where(['status' => array('pending')])
                        ->andWhere('reminder_datetime <= "' . $time . '"')->all();
        foreach ($reminders as $reminder) {
            $sendReminder = Notifications::reminderNotification($reminder->id);
            if ($sendReminder) {
                $reminder->status = 'completed';
                if ($reminder->save()) {
                    
                } else {
                    
                }
            }
        }
        echo 'success';
    }

    /* At mid night  0 0 * * *     */
    public function actionDoctorSponserend() {
        $time = time();
        $reminders = \common\models\UserPlanDetail::find()->where(['status' => array('pending')])->andWhere('to_date < "' . date('Y-m-d') . '"')->all();
        foreach ($reminders as $reminder) {
            User::updateAll(['user_plan' => 'other'], ['id' => $reminder->user_id]);
        }
        echo 'success';
    }

    /*  Every minutes */
    public function actionNotifyBeforeTwoHour() {
        $setNotify = UserAppointment::find()->where('appointment_time > ' . time() . ' ')->andWhere(['is_deleted' => 0, 'status' => 'pending'])->all();
        if ($setNotify) {
            foreach ($setNotify as $notifyData) {
                //Convert to date
                $datestr = date('Y-m-d H:i:s', $notifyData['start_time']);
                $time = new \DateTime($datestr);
                $diff = $time->diff(new \DateTime());
                $minutes = ($diff->days * 24 * 60) +
                        ($diff->h * 60) + $diff->i;
                if ($minutes == 120) {
                    $sendReminder = Notifications::before2hNotification($notifyData);
                }
            }
        }
        echo 'success';
    }
   /*   0 7 * * *    */
    public function actionNotifyAtseven() {
        $setNotify = UserAppointment::find()->where('appointment_time > ' . time() . ' ')->andWhere(['is_deleted' => 0, 'status' => 'pending'])->all();
        if ($setNotify) {
            foreach ($setNotify as $notifyData) {
                $sendReminder = Notifications::before2hNotification($notifyData);
            }
        }
        echo 'success';
    }

}
