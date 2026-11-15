<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LivePollAnswer extends Model
{
    protected $fillable = ['live_poll_id', 'user_id', 'option'];
}
