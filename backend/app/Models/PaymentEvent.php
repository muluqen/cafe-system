<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
   protected $fillable = [
    'order_id',
    'amount',
    'currency',
    'method',
    'provider',
    'provider_reference',
    'tx_ref',
    'status',
    'paid_at',
    'initiated_at',
    'payload',
];

    protected $casts = [
    'amount'       => 'decimal:2',
    'paid_at'      => 'datetime',
    'initiated_at' => 'datetime',
    'payload'      => 'array',
];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
