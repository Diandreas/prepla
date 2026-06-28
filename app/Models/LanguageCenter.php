<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LanguageCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'owner_email',
        'seats_limit',
        'default_exam_id',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
            'seats_limit' => 'integer',
        ];
    }

    public function members(): BelongsToMany
    {
        // Pivot uses center_id (not the inferred language_center_id).
        return $this->belongsToMany(User::class, 'center_user', 'center_id', 'user_id')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function staff(): BelongsToMany
    {
        return $this->members()->wherePivotIn('role', ['center_admin', 'teacher']);
    }

    public function students(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'student');
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'center_id');
    }

    public function defaultExam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'default_exam_id');
    }

    /** Seats currently used = number of student members. */
    public function seatsUsed(): int
    {
        return $this->members()->wherePivot('role', 'student')->count();
    }

    public function seatsAvailable(): int
    {
        return max(0, $this->seats_limit - $this->seatsUsed());
    }
}
