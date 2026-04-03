<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DictionaryWord extends Model
{
    protected $fillable = [
        'word',
        'language',
        'definition',
        'example',
        'translation',
        'skill_level',
    ];

    public function userProgress()
    {
        return $this->hasMany(UserWordProgress::class);
    }
}
