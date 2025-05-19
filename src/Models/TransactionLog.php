<?php

namespace Rais\MomoSuite\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TransactionLog extends Model
{
    use HasUuids;

    protected $table = 'momo_transaction_logs';
    protected $guarded = [];
    protected $casts = [
        'data' => 'array',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
