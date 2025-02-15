<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Club;

class NewClubNotification extends Notification
{
    use Queueable;

    protected $club;

    public function __construct(Club $club)
    {
        $this->club = $club;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('A new club has been added: ' . $this->club->club_name)
                    ->action('View Club', url('/club/' . $this->club->club_id))
                    ->line('Thank you for using our application!');
    }
}
