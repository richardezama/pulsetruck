<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App;

class Vehicle extends Model
{
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'company_id');
    }
}
