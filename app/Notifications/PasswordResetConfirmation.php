<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class PasswordResetConfirmation extends Notification
{
    use Queueable;

    protected $resetTime;
    protected $ipAddress;
    protected $userAgent;

    /**
     * Create a new notification instance.
     */
    public function __construct($ipAddress = null, $userAgent = null)
    {
        $this->resetTime = Carbon::now();
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Password Successfully Reset - HR Admin System')
            ->view('emails.password-reset-confirmation', [
                'user' => $notifiable,
                'resetTime' => $this->resetTime,
                'ipAddress' => $this->ipAddress,
                'userAgent' => $this->userAgent,
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'reset_time' => $this->resetTime,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ];
    }
}
