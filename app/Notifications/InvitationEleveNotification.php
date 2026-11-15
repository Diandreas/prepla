<?php

namespace App\Notifications;

use App\Models\Classroom;
use App\Models\User;
use App\Services\Center\Marque;
use App\Support\UrlDuCentre;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Invitation d'un eleve inscrit par son centre (import de la liste d'eleves).
 *
 * Trois cas :
 *  - « nouveau » : compte cree pour lui. Le lien est une URL signee valable
 *    VALIDITE_JOURS jours qui fabrique un jeton de mot de passe frais a
 *    l'ouverture (InvitationMotDePasseController) : un jeton cree a l'import
 *    expirerait au bout d'une heure.
 *  - « rejoindre » : compte PrePla deja existant, hors de ce centre. Rien n'est
 *    rattache sans son accord : le lien ouvre « rejoindre » avec le code de la
 *    classe, c'est lui qui accepte.
 *  - « classe » : deja eleve du centre, simplement ajoute a la classe.
 */
class InvitationEleveNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const VALIDITE_JOURS = 30;

    public function __construct(
        public readonly string $centreNom,
        public readonly string $classeNom,
        public readonly string $lien,
        public readonly string $mode,
        public readonly bool $masquerPrepla = false,
        public readonly ?string $code = null,
    ) {}

    /** @param  'nouveau'|'rejoindre'|'classe'  $mode */
    public static function pour(Classroom $classe, User $user, string $mode): self
    {
        $centre = $classe->center;
        $marque = app(Marque::class)->pourCentre($centre);

        $lien = match ($mode) {
            'nouveau' => UrlDuCentre::racine($centre).URL::temporarySignedRoute(
                'invitation.mot-de-passe',
                now()->addDays(self::VALIDITE_JOURS),
                ['user' => $user->id, 'v' => self::empreinte($user)],
                false,
            ),
            'rejoindre' => UrlDuCentre::route($centre, 'center.join', ['code' => $classe->invite_code]),
            default => UrlDuCentre::route($centre, 'login'),
        };

        return new self(
            centreNom: $centre->nomAffiche(),
            classeNom: $classe->name,
            lien: $lien,
            mode: $mode,
            masquerPrepla: (bool) $marque['masquer_prepla'],
            code: $mode === 'rejoindre' ? $classe->invite_code : null,
        );
    }

    /** Change des que le mot de passe change : le lien ne sert qu'une fois. */
    public static function empreinte(User $user): string
    {
        return substr(hash_hmac('sha256', (string) $user->getAuthPassword(), (string) config('app.key')), 0, 20);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->mode === 'rejoindre') {
            $message = (new MailMessage)
                ->subject("{$this->centreNom} · Invitation dans la classe {$this->classeNom}")
                ->greeting("Bonjour {$notifiable->name} !")
                ->line("{$this->centreNom} vous invite à rejoindre la classe **{$this->classeNom}**.")
                ->line("Rien n'est fait sans votre accord : connectez-vous puis confirmez avec le code **{$this->code}**. Si vous ne connaissez pas cet établissement, ignorez ce message.")
                ->action('Rejoindre la classe', $this->lien);
        } else {
            $message = (new MailMessage)
                ->subject("{$this->centreNom} · Votre inscription dans la classe {$this->classeNom}")
                ->greeting("Bonjour {$notifiable->name} !")
                ->line("{$this->centreNom} vous a inscrit dans la classe **{$this->classeNom}**.");

            if ($this->mode === 'nouveau') {
                $message->line('Un compte a été créé avec cette adresse. Choisissez votre mot de passe pour y entrer (lien valable '.self::VALIDITE_JOURS.' jours).')
                    ->action('Choisir mon mot de passe', $this->lien);
            } else {
                $message->line('Connectez-vous avec votre compte habituel : la classe apparaît dans votre espace.')
                    ->action('Me connecter', $this->lien);
            }
        }

        $message->salutation("L'équipe de {$this->centreNom}");

        if ($this->masquerPrepla) {
            $message->markdown('emails.centre.notification', ['nomDuSite' => $this->centreNom]);
        }

        return $message;
    }
}
