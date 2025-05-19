<?php

namespace Rais\MomoSuite\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Transaction extends Model
{
    use HasUuids;

    protected $table = 'momo_transactions';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'json',
        'request' => 'json',
        'response' => 'json',
        'callback_data' => 'json',
        'callback_received_at' => 'datetime',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(TransactionLog::class);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByTransactionId($query, string $transactionId)
    {
        return $query->where('transaction_id', $transactionId);
    }

    public function scopeByReference($query, string $reference)
    {
        return $query->where('reference', $reference);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function addLog(string $event, ?string $status = null, ?array $data = null)
    {
        return $this->logs()->create([
            'event' => $event,
            'status' => $status,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
