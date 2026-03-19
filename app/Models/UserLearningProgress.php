<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLearningProgress extends Model
{
    use HasFactory;

    protected $table = 'user_learning_progress';

    protected $fillable = [
        'user_id',
        'node_id',
        'status',
        'exercises_done',
        'exercises_required',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(LearningPathNode::class, 'node_id');
    }
}
