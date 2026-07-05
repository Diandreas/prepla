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
        'password',
        'google_id',
        'avatar',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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

    /** The single center membership (or null). Eager-loadable via 'centerMembership'. */
    public function centerMembership(): HasOne
    {
        return $this->hasOne(CenterUser::class);
    }

    public function center(): ?LanguageCenter
    {
        return $this->centers()->first();
    }

    /** Center-scoped role: center_admin | teacher | student | null. */
    public function centerRole(): ?string
    {
        return $this->centerMembership?->role;
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
