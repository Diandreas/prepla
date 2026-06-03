<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'target_exam_id',
        'target_score',
        'exam_date',
        'current_level',
        'level_source',
        'plan',
        'interface_language',
        'native_language',
        'xp_total',
        'streak_current',
        'streak_last_date',
        'onboarding_completed_at',
        'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'streak_last_date' => 'date',
            'onboarding_completed_at' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targetExam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'target_exam_id');
    }
}
