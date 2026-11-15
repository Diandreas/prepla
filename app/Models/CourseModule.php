<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseModule extends Model
{
    protected $fillable = ['course_path_id', 'position', 'titre', 'description'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function path(): BelongsTo
    {
        return $this->belongsTo(CoursePath::class, 'course_path_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CourseSession::class)->orderBy('position')->orderBy('id');
    }
}
