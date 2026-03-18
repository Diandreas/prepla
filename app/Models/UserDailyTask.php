<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDailyTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'tasks',
        'completed_count',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'tasks' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
