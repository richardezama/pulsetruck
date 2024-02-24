<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App;

class Driver extends Model
{

    public function user()
    {
        return $this->belongsTo(User::class,"user_id");
    }
}
