<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'product', 'amount', 'currency', 'status', 'analytics_consent',
        'ga_client_id', 'ga_session_id', 'paid_at', 'ga_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'analytics_consent' => 'boolean',
            'paid_at' => 'datetime',
            'ga_sent_at' => 'datetime',
        ];
    }
}
