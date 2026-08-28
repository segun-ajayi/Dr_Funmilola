<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\NotificationDelivery;
use App\Notifications\PortalActivityNotification;
use App\Notifications\AppointmentReminderEmail;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature='appointments:send-reminders';
    protected $description='Create idempotent reminders for upcoming confirmed appointments';

    public function handle(): int
    {
        $count=0;Appointment::with(['patient.notificationPreference','service'])->whereIn('status',['confirmed','rescheduled'])->whereBetween('starts_at',[now(),now()->addHours(24)])->chunkById(100,function($appointments) use(&$count){foreach($appointments as $appointment){$hours=$appointment->starts_at->diffInHours(now(),true);foreach(array_filter(['24_hour'=>$hours<=24,'2_hour'=>$hours<=2]) as $type=>$due){$preference=$appointment->patient->notificationPreference;foreach(['in_app','email'] as $channel){if($channel==='in_app'&&$preference&&!$preference->in_app_reminders)continue;if($channel==='email'&&$preference&&!$preference->email_reminders)continue;$delivery=NotificationDelivery::firstOrCreate(['appointment_id'=>$appointment->id,'user_id'=>$appointment->patient_id,'notification_type'=>$type,'channel'=>$channel],['status'=>$channel==='in_app'?'delivered':'queued','delivered_at'=>$channel==='in_app'?now():null]);if(!$delivery->wasRecentlyCreated)continue;$time=$appointment->starts_at->setTimezone('Africa/Lagos')->format('g:i A, j M Y');if($channel==='in_app')$appointment->patient->notify(new PortalActivityNotification('Appointment reminder',"Your {$appointment->service->name} appointment is at {$time}.",'reminder'));else $appointment->patient->notify(new AppointmentReminderEmail($appointment->service->name,$time));$count++;}}}});
        $this->info("Created {$count} reminder deliveries.");return self::SUCCESS;
    }
}
