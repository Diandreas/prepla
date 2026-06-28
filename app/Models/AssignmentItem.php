<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AssignmentItem extends Model
{
    protected $fillable = [
        'assignment_id',
        'itemable_type',
        'itemable_id',
        'sort_order',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /** An Exercise or a Lesson. */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }
}
