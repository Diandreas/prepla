<?php

namespace App\Notifications;

use App\Models\Classroom;
use App\Services\Center\Marque;
use App\Support\UrlDuCentre;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invitation d'un eleve inscrit par son centre (import de la liste d'eleves).
 *
 * Compte cree pour lui : le lien choisit son mot de passe (jeton de
 * reinitialisation, valable le temps prevu par config/auth.php). Compte deja
 * existant : le lien mene a la connexion, la classe l'attend.
 */
class InvitationEleveNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $centreNom,
        public readonly string $classeNom,
        public readonly string $lien,
        public readonly bool $nouveauCompte,
        public readonly bool $masquerPrepla = false,
    ) {}

    public static function pour(Classroom $classe, string $email, ?string $jeton): self
    {
        $centre = $classe->center;
        $marque = app(Marque::class)->pourCentre($centre);

        $lien = $jeton !== null
            ? UrlDuCentre::route($centre, 'password.reset', ['token' => $jeton, 'email' => $email])
            : UrlDuCentre::route($centre, 'login');

        return new self(
            centreNom: $centre->nomAffiche(),
            classeNom: $classe->name,
            lien: $lien,
            nouveauCompte: $jeton !== null,
            masquerPrepla: (bool) $marque['masquer_prepla'],
        );
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("{$this->centreNom} · Votre inscription dans la classe {$this->classeNom}")
            ->greeting("Bonjour {$notifiable->name} !")
            ->line("{$this->centreNom} vous a inscrit dans la classe **{$this->classeNom}**.");

        if ($this->nouveauCompte) {
            $message->line('Un compte a été créé avec cette adresse. Choisissez votre mot de passe pour y entrer.')
                ->action('Choisir mon mot de passe', $this->lien);
        } else {
            $message->line('Connectez-vous avec votre compte habituel : la classe apparaît dans votre espace.')
                ->action('Me connecter', $this->lien);
        }

        $message->salutation("L'équipe de {$this->centreNom}");

        if ($this->masquerPrepla) {
            $message->markdown('emails.centre.notification', ['nomDuSite' => $this->centreNom]);
        }

        return $message;
    }
}
