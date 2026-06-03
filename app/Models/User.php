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
}
