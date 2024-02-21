<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App;

class Expense extends Model
{
    public function type()
    {
        return $this->belongsTo(Expensetype::class, 'expense_type');
    }
}
