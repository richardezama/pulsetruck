<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App;

class Purchase extends Model
{

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

}
