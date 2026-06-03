<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PracticeReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $streakDays = 0
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [WebPushChannel::class];

        // Also send by email if user has no push subscription
        if (!$notifiable->pushSubscriptions()->exists()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        $body = $this->streakDays > 0
            ? "Ne brisez pas votre série de {$this->streakDays} jour" . ($this->streakDays > 1 ? 's' : '') . ' ! Pratiquez maintenant.'
            : 'Une courte session suffit pour progresser. À vous !';

        return (new WebPushMessage)
            ->title('PrePla — Temps de pratiquer')
            ->body($body)
            ->icon('/icons/icon-192.png')
            ->badge('/icons/icon-72.png')
            ->action('Pratiquer maintenant', route('dashboard'))
            ->data(['url' => route('dashboard')]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->streakDays > 0
            ? "Ne brisez pas votre série de {$this->streakDays} jour" . ($this->streakDays > 1 ? 's' : '') . ' !'
            : 'PrePla — Temps de pratiquer aujourd\'hui';

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Bonjour {$notifiable->name} !")
            ->line($this->streakDays > 0
                ? "Votre série de **{$this->streakDays} jour" . ($this->streakDays > 1 ? 's' : '') . "** est en jeu. Pratiquez maintenant pour la maintenir."
                : 'Une courte session de 15 minutes suffit pour progresser.')
            ->action('Continuer ma préparation', route('dashboard'))
            ->salutation('L\'équipe PrePla');
    }
}
