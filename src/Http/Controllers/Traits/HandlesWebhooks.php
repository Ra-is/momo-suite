<?php

namespace Rais\MomoSuite\Http\Controllers\Traits;

use Rais\MomoSuite\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Rais\MomoSuite\Events\TransactionStatusUpdated;

trait HandlesWebhooks
{
    protected function updateTransaction(Transaction $transaction, array $data, array $result): void
    {
        $oldStatus = $transaction->status;

        // Check if this is the first callback
        if ($transaction->callback_received_at === null) {
            // First callback - update the transaction
            $transaction->update([
                'status' => $result['status'] ?? 'failed',
                'callback_data' => $data,
                'callback_received_at' => now(),
                // let us update the error message if only it has failed 
                'error_message' => $result['status'] === 'failed' ? $result['message'] : null,
            ]);

            // Log the initial status change
            $transaction->addLog(
                'callback_received',
                $result['status'] ?? 'failed',
                [
                    'old_status' => $oldStatus,
                    'new_status' => $result['status'] ?? 'failed',
                    'message' => $result['message'] ?? null,
                    'is_initial_callback' => true,
                ]
            );

            event(new TransactionStatusUpdated($transaction, $oldStatus, $transaction->status));
        } else {
            // Subsequent callback - only log the status
            $transaction->addLog(
                'subsequent_callback_received',
                $result['status'] ?? 'failed',
                [
                    'current_status' => $oldStatus,
                    'callback_status' => $result['status'] ?? 'failed',
                    'message' => $result['message'] ?? null,
                    'is_initial_callback' => false,
                ]
            );
        }
    }

    protected function logError(Transaction $transaction, \Exception $e, array $data): void
    {
        $transaction->addLog(
            'callback_error',
            'failed',
            [
                'error' => $e->getMessage(),
                'data' => $data,
            ]
        );
    }
}
