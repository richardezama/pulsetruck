<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App;

class Reward extends Model
{
   
    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }
      
    public function product()
    {
        return $this->belongsTo('App\Models\RewardProduct','item_id');
    }
}
