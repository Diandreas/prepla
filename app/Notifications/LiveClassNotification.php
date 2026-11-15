<?php

namespace App\Notifications;

use App\Models\LiveSession;
use App\Services\Center\Marque;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Prevenir les eleves d'une classe qu'un live est planifie, ou qu'il commence.
 * Meme canal que les devoirs : notification push, repli par courriel.
 */
class LiveClassNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $titre,
        public readonly string $quand,
        public readonly bool $maintenant,
        public readonly string $lien,
        public readonly ?string $centreNom = null,
        public readonly bool $masquerPrepla = false,
        public readonly int $occurrences = 1,
    ) {}

    public static function pour(LiveSession $live, bool $maintenant, int $occurrences = 1): self
    {
        $centre = $live->classroom?->center;
        $marque = app(Marque::class)->pourCentre($centre);

        return new self(
            titre: $live->title,
            quand: $live->starts_at->translatedFormat('l d F \à H\hi'),
            maintenant: $maintenant,
            lien: route('live.show', $live),
            centreNom: $centre?->nomAffiche(),
            masquerPrepla: (bool) $marque['masquer_prepla'],
            occurrences: $occurrences,
        );
    }

    public function via(object $notifiable): array
    {
        $canaux = [WebPushChannel::class];
        if (! $notifiable->pushSubscriptions()->exists()) {
            $canaux[] = 'mail';
        }

        return $canaux;
    }

    private function phrase(): string
    {
        if ($this->maintenant) {
            return "Le cours en direct « {$this->titre} » commence maintenant.";
        }
        $serie = $this->occurrences > 1 ? " (série de {$this->occurrences} séances)" : '';

        return "Cours en direct « {$this->titre} » prévu {$this->quand}{$serie}.";
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->maintenant ? 'Live en cours' : 'Nouveau live planifié')
            ->body($this->phrase())
            ->icon('/icons/pwa-192-v4.png')
            ->action('Ouvrir', $this->lien)
            ->data(['url' => $this->lien]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(($this->centreNom ? "{$this->centreNom} · " : '').($this->maintenant ? 'Live en cours : ' : 'Live planifié : ').$this->titre)
            ->greeting("Bonjour {$notifiable->name} !")
            ->line($this->phrase())
            ->action($this->maintenant ? 'Rejoindre le live' : 'Voir le live', $this->lien)
            ->salutation($this->centreNom ? "L'équipe de {$this->centreNom}" : "L'équipe PrePla");

        if ($this->masquerPrepla && $this->centreNom) {
            $message->markdown('emails.centre.notification', ['nomDuSite' => $this->centreNom]);
        }

        return $message;
    }
}
