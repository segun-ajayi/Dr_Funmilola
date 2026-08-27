<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminderEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $service, private readonly string $appointmentTime) { $this->afterCommit(); }
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Appointment reminder')->greeting('Hello '.$notifiable->name.',')->line("This is a reminder for your {$this->service} appointment at {$this->appointmentTime}.")->line('Please sign in to your secure patient portal if you need to contact the practice.')->action('Open patient portal',url('/portal'))->line('This reminder does not contain clinical information.');
    }
}
