<?php

namespace App\Notifications;

use App\Models\Assignment;
use App\Services\Center\Marque;
use App\Support\UrlDuCentre;
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
        // Le centre au nom duquel le devoir part (signature, objet), et s'il
        // masque la marque PrePla (en-tete et pied du courriel a son nom).
        public readonly ?string $centreNom = null,
        public readonly bool $masquerPrepla = false,
        // Le lien vers le tableau de bord, sur l'adresse du centre s'il en a
        // une (lot B) : la notification part en file, hors de toute requete.
        public readonly ?string $lien = null,
    ) {}

    public static function forAssignment(Assignment $assignment, ?string $teacherName = null): self
    {
        $centre = $assignment->classroom?->center;
        $marque = app(Marque::class)->pourCentre($centre);

        return new self(
            title: $assignment->title,
            exerciseCount: $assignment->items()->count(),
            teacherName: $teacherName,
            dueAt: $assignment->due_at?->translatedFormat('d F'),
            // Le nom que le centre montre a ses eleves ; sans centre, PrePla.
            centreNom: $centre?->nomAffiche(),
            masquerPrepla: (bool) $marque['masquer_prepla'],
            lien: UrlDuCentre::route($centre, 'dashboard'),
        );
    }

    private function lienDuTableauDeBord(): string
    {
        return $this->lien ?? route('dashboard');
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
            ->action('Commencer', $this->lienDuTableauDeBord())
            ->data(['url' => $this->lienDuTableauDeBord()]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $from = $this->teacherName ? " par {$this->teacherName}" : '';

        $message = (new MailMessage)
            ->subject(($this->centreNom ? "{$this->centreNom} · " : '')."Nouveau devoir : {$this->title}")
            ->greeting("Bonjour {$notifiable->name} !")
            ->line("Un devoir vient de t'être donné{$from} : **{$this->title}**.")
            ->line($this->body())
            ->action('Ouvrir mon devoir', $this->lienDuTableauDeBord())
            // Le devoir vient du centre : c'est lui qui signe.
            ->salutation($this->centreNom ? "L'équipe de {$this->centreNom}" : "L'équipe PrePla");

        // Marque blanche : ni l'en-tete ni le pied du courriel ne disent PrePla.
        if ($this->masquerPrepla && $this->centreNom) {
            $message->markdown('emails.centre.notification', ['nomDuSite' => $this->centreNom]);
        }

        return $message;
    }
}
