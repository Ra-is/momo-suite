<?php

namespace Rais\MomoSuite\Http\Controllers;

use Illuminate\Http\Request;
use Rais\MomoSuite\Models\Transaction;
use Rais\MomoSuite\Services\MomoService;
use Rais\MomoSuite\Http\Controllers\Traits\HandlesWebhooks;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    use HandlesWebhooks;

    protected MomoService $momoService;

    public function __construct(MomoService $momoService)
    {
        $this->momoService = $momoService;
    }

    public function handle(Request $request)
    {
        $data = $request->all();


        try {
            $provider = $this->momoService->provider('paystack');
            $result = $provider->handleWebhook($data);

            if (isset($data['data']['reference'])) {
                $transaction = Transaction::where('transaction_id', $data['data']['reference'])->first();

                if ($transaction) {
                    $this->updateTransaction($transaction, $data, $result);
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            if (isset($transaction)) {
                $this->logError($transaction, $e, $data);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    protected function isDuplicateCallback(string $reference): bool
    {
        return Transaction::where('reference', $reference)
            ->whereNotNull('callback_received_at')
            ->exists();
    }

    protected function mapStatus(?string $status): string
    {
        return match ($status) {
            'success' => 'success',
            'pending' => 'pending',
            default => 'failed'
        };
    }
}
