<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWordProgress extends Model
{
    protected $fillable = [
        'user_id',
        'dictionary_word_id',
        'status',
        'last_reviewed_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dictionaryWord()
    {
        return $this->belongsTo(DictionaryWord::class, 'dictionary_word_id');
    }
}
