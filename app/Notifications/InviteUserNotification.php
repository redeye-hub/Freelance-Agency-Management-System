<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InviteUserNotification extends Notification
{
    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject('You are invited to ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('You have been invited as: ' . $notifiable->getRoleNames()->first())
            ->action('Set your password', $url)
            ->line('This link expires in 60 minutes.');
    }
}