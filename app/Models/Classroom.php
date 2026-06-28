<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'center_id',
        'name',
        'level',
        'exam_id',
        'invite_code',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'classroom_user')
            ->withPivot('role_in_class')
            ->withTimestamps();
    }

    public function students(): BelongsToMany
    {
        return $this->members()->wherePivot('role_in_class', 'student');
    }

    public function teachers(): BelongsToMany
    {
        return $this->members()->wherePivot('role_in_class', 'teacher');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /** Generate a short, unique, human-friendly invite code. */
    public static function generateInviteCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('invite_code', $code)->exists());

        return $code;
    }
}
