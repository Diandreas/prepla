<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un examen blanc de centre rendu par un eleve, avec son score par section. */
class MockExamPassage extends Model
{
    protected $fillable = ['mock_exam_id', 'user_id', 'assignment_id', 'temps_passe', 'score', 'scores_sections'];

    protected function casts(): array
    {
        return ['scores_sections' => 'array'];
    }

    public function mockExam(): BelongsTo
    {
        return $this->belongsTo(MockExam::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
