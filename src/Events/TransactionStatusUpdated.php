<?php

namespace Rais\MomoSuite\Events;

use Rais\MomoSuite\Models\Transaction;

class TransactionStatusUpdated
{
    public Transaction $transaction;
    public string $oldStatus;
    public string $newStatus;

    public function __construct(Transaction $transaction, string $oldStatus, string $newStatus)
    {
        $this->transaction = $transaction;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
}
