<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class ForgotPasswordRequestNotification extends Notification
{
    use Queueable;

    protected $requestTime;
    protected $ipAddress;
    protected $userAgent;

    /**
     * Create a new notification instance.
     */
    public function __construct($ipAddress = null, $userAgent = null)
    {
        $this->requestTime = Carbon::now();
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
            ->subject('Password Reset Request Received - HR Admin System')
            ->view('emails.forgot-password-request', [
                'user' => $notifiable,
                'requestTime' => $this->requestTime,
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
            'request_time' => $this->requestTime,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ];
    }
}
