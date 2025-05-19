<?php

namespace Rais\MomoSuite\Http\Controllers;

use Illuminate\Http\Request;
use Rais\MomoSuite\Models\Transaction;
use Rais\MomoSuite\Services\MomoService;

class WebhookController extends Controller
{
    protected MomoService $momoService;

    public function __construct(MomoService $momoService)
    {
        $this->momoService = $momoService;
    }

    public function handle(Request $request, string $provider)
    {
        $data = $request->all();

        try {
            $provider = $this->momoService->provider($provider);
            $result = $provider->handleWebhook($data);

            if (isset($data['transaction_id'])) {
                $transaction = Transaction::where('transaction_id', $data['transaction_id'])->first();

                if ($transaction) {
                    $oldStatus = $transaction->status;
                    $transaction->update([
                        'status' => $result['status'] ?? 'failed',
                        'callback_data' => $data,
                        'callback_received_at' => now(),
                        // we log the error if status is failed
                        'error_message' => $result['status'] === 'failed' ? $result['message'] : null,
                    ]);

                    // Log the status change
                    $transaction->addLog(
                        'callback_received',
                        $result['status'] ?? 'failed',
                        [
                            'old_status' => $oldStatus,
                            'new_status' => $result['status'] ?? 'failed',
                            'message' => $result['message'] ?? null,
                        ]
                    );
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            if (isset($transaction)) {
                $transaction->addLog(
                    'callback_error',
                    'failed',
                    [
                        'error' => $e->getMessage(),
                        'data' => $data,
                    ]
                );
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
