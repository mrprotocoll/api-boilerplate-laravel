<?php

declare(strict_types=1);

namespace Modules\V1\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\V1\User\Models\User;

final class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct() {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->view(
                'email.auth.welcome', // The name of the Blade view file
                [
                    'name' => $notifiable->name(),
                    'dashboardLink' => config('constants.routes.dashboard'),
                ]
            );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'Welcome',
            'title' => 'Welcome onboard',
            'message' => 'Complete your profile.',
            'url' => '/dashboard',
        ];
    }
}
