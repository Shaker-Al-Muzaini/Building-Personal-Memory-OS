<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecurringTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'category',
        'description',
        'frequency',
        'start_date',
        'next_date',
        'is_active'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
