<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Billable, HasPushSubscriptions;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        // Numero de telephone : il sert a joindre l'apprenant pour recueillir ses
        // retours. Demande a l'inscription, ou une fois apres une entree par Google.
        'phone',
        'phone_prompted_at',
        'password',
        'google_id',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        // Cashier billing internals — not needed by the frontend and were
        // shipping in the 'auth.user' Inertia prop on every single page load.
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * PrePla's trial dates live on `user_profiles.trial_ends_at`, set at onboarding —
     * not on the native `users.trial_ends_at` column Cashier's Billable trait provides
     * (that one backs Cashier's own onGenericTrial()/trialEndsAt(), which PrePla doesn't use
     * and which stays empty for every user). Two columns, same name, different owners.
     */
    public function hasPremiumAccess(): bool
    {
        if ($this->subscribed('default')) {
            return true;
        }

        $trialEndsAt = $this->profile?->trial_ends_at;

        return $trialEndsAt && now()->lt($trialEndsAt);
    }

    public function isOnTrial(): bool
    {
        if ($this->subscribed('default')) {
            return false;
        }

        $trialEndsAt = $this->profile?->trial_ends_at;

        return $trialEndsAt && now()->lt($trialEndsAt);
    }

    public function trialDaysLeft(): int
    {
        $trialEndsAt = $this->profile?->trial_ends_at;

        if (!$trialEndsAt || now()->gte($trialEndsAt)) {
            return 0;
        }

        return (int) now()->diffInDays($trialEndsAt, false);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function exerciseAttempts(): HasMany
    {
        return $this->hasMany(UserExerciseAttempt::class);
    }

    public function dailyTasks(): HasMany
    {
        return $this->hasMany(UserDailyTask::class);
    }

    public function leaderboardEntries(): HasMany
    {
        return $this->hasMany(LeaderboardEntry::class);
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot('earned_at')
            ->withTimestamps();
    }

    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class);
    }

    // ───────────────────────── B2B « centre de langue » ─────────────────────────

    /** Centers this user belongs to (one in this lot, but kept as a relation). */
    public function centers(): BelongsToMany
    {
        // Pivot uses center_id (not the inferred language_center_id).
        return $this->belongsToMany(LanguageCenter::class, 'center_user', 'user_id', 'center_id')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /** Classrooms this user is enrolled in (as student or teacher). */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_user')
            ->withPivot('role_in_class')
            ->withTimestamps();
    }

    /**
     * UNE appartenance a un centre, sans garantie laquelle quand il y en a
     * plusieurs. Ne sert qu'aux requetes qui la bornent elles-memes par centre
     * (« ->where('center_id', ...) ») ou qui cherchent n'importe laquelle. Pour
     * savoir OU l'utilisateur agit et avec quel role : appartenanceCourante().
     */
    public function centerMembership(): HasOne
    {
        return $this->hasOne(CenterUser::class);
    }

    /** La cle de session du centre choisi par une personne qui en gere plusieurs. */
    public const CENTRE_COURANT = 'centre_courant';

    /** Quand rien n'est choisi : le centre qu'on dirige, puis celui ou l'on enseigne. */
    private const PRIORITE_DES_ROLES = ['center_admin' => 0, 'teacher' => 1, 'student' => 2];

    /**
     * L'appartenance dans laquelle l'utilisateur agit en ce moment.
     *
     * Une personne peut gerer plusieurs centres. Tout le reste de l'application
     * — les acces, les listes, les droits du responsable — passe par center() et
     * centerRole() : ils doivent donc parler du MEME centre, celui qu'elle a
     * choisi. Avant, center() prenait le premier centre venu et centerRole() une
     * appartenance quelconque : avec deux centres, on pouvait agir dans l'un avec
     * le role tenu dans l'autre.
     *
     * Recalculee a chaque appel, a dessein : une adhesion se fait par attach(),
     * qui ne declenche aucun evenement, et une memoire garderait l'ancien etat
     * dans la requete meme ou l'on vient de rejoindre un centre. Deux petites
     * requetes valent mieux qu'un droit calcule sur un etat perime.
     */
    public function appartenanceCourante(): ?CenterUser
    {
        $requete = app('request');

        $toutes = CenterUser::with('center')->where('user_id', $this->id)->get()
            ->filter(fn (CenterUser $a) => $a->center !== null)
            ->values();

        $choisie = null;

        if ($toutes->count() === 1) {
            $choisie = $toutes->first();
        } elseif ($toutes->count() > 1) {
            // Le choix de la personne, s'il designe encore un centre actif ou
            // elle est inscrite. Sinon on ne la laisse pas devant un centre ferme.
            $voulu = $requete->hasSession() ? $requete->session()->get(self::CENTRE_COURANT) : null;
            $choisie = $toutes->first(fn (CenterUser $a) => (int) $a->center_id === (int) $voulu && $a->center->is_active)
                ?? $toutes->sortBy(fn (CenterUser $a) => [
                    $a->center->is_active ? 0 : 1,
                    self::PRIORITE_DES_ROLES[$a->role] ?? 9,
                    $a->id,
                ])->first();
        }

        return $choisie;
    }

    /** Le centre dans lequel l'utilisateur agit en ce moment. */
    public function center(): ?LanguageCenter
    {
        return $this->appartenanceCourante()?->center;
    }

    /** Son role DANS CE CENTRE : center_admin | teacher | student | null. */
    public function centerRole(): ?string
    {
        return $this->appartenanceCourante()?->role;
    }

    /**
     * Les centres que l'utilisateur encadre — responsable ou enseignant —, actifs.
     * C'est entre eux qu'il peut passer de l'un a l'autre.
     *
     * @return \Illuminate\Support\Collection<int, CenterUser>
     */
    public function centresEncadres(): \Illuminate\Support\Collection
    {
        return CenterUser::with('center:id,name,slug,is_active')
            ->where('user_id', $this->id)
            ->whereIn('role', ['center_admin', 'teacher'])
            ->orderBy('id')
            ->get()
            ->filter(fn (CenterUser $a) => (bool) $a->center?->is_active)
            ->values();
    }

    /**
     * Agir desormais dans ce centre. Refuse un centre que l'utilisateur
     * n'encadre pas : on ne choisit pas d'entrer chez les autres.
     */
    public function choisirCentre(int $centerId): bool
    {
        if (! $this->centresEncadres()->contains('center_id', $centerId)) {
            return false;
        }

        app('request')->session()->put(self::CENTRE_COURANT, $centerId);

        return true;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isCenterStaff(): bool
    {
        return in_array($this->centerRole(), ['center_admin', 'teacher'], true);
    }

    public function isCenterStudent(): bool
    {
        return $this->centerRole() === 'student';
    }

    public function centerRoleIs(string $role): bool
    {
        return $this->centerRole() === $role;
    }
}
