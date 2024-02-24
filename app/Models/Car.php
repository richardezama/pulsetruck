<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App;

class Car extends Model
{
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function make()
    {
        return $this->belongsTo(Brand::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function type()
    {
        return $this->belongsTo(Types::class,"type_id");
    }

}
