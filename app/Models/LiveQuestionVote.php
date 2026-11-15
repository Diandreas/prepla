<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveQuestionVote extends Model
{
    protected $fillable = ['live_message_id', 'user_id'];
}
