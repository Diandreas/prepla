<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Prévenir un élève qu'un devoir l'attend.
 *
 * Un enseignant pouvait publier un devoir, mais personne n'en informait l'élève :
 * il ne le découvrait qu'en ouvrant l'application de lui-même. Le devoir arrive
 * maintenant par notification, avec un repli par courriel pour qui n'a pas autorisé
 * les notifications.
 */
class AssignmentPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $title,
        public readonly int $exerciseCount,
        public readonly ?string $teacherName = null,
        public readonly ?string $dueAt = null,
    ) {}

    public static function forAssignment(Assignment $assignment, ?string $teacherName = null): self
    {
        return new self(
            title: $assignment->title,
            exerciseCount: $assignment->items()->count(),
            teacherName: $teacherName,
            dueAt: $assignment->due_at?->translatedFormat('d F'),
        );
    }

    public function via(object $notifiable): array
    {
        $channels = [WebPushChannel::class];

        // Sans abonnement aux notifications, le devoir passerait inaperçu : on écrit.
        if (! $notifiable->pushSubscriptions()->exists()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    private function body(): string
    {
        $parts = [$this->exerciseCount . ' exercice' . ($this->exerciseCount > 1 ? 's' : '')];

        if ($this->dueAt) {
            $parts[] = 'à rendre avant le ' . $this->dueAt;
        }

        return implode(' · ', $parts);
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        $from = $this->teacherName ? " de {$this->teacherName}" : '';

        return (new WebPushMessage)
            ->title("Nouveau devoir{$from} : {$this->title}")
            ->body($this->body())
            ->icon('/icons/pwa-192-v4.png')
            ->badge('/icons/pwa-192-v4.png')
            ->action('Commencer', route('dashboard'))
            ->data(['url' => route('dashboard')]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $from = $this->teacherName ? " par {$this->teacherName}" : '';

        return (new MailMessage)
            ->subject("Nouveau devoir : {$this->title}")
            ->greeting("Bonjour {$notifiable->name} !")
            ->line("Un devoir vient de t'être donné{$from} : **{$this->title}**.")
            ->line($this->body())
            ->action('Ouvrir mon devoir', route('dashboard'))
            ->salutation("L'équipe PrePla");
    }
}
