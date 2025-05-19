<?php

namespace Rais\MomoSuite\Http\Controllers;

use Illuminate\Http\Request;
use Rais\MomoSuite\Models\Transaction;
use Rais\MomoSuite\Services\MomoService;
use Rais\MomoSuite\Http\Controllers\Traits\HandlesWebhooks;

class KorbaWebhookController extends Controller
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
            $provider = $this->momoService->provider('korba');
            $result = $provider->handleWebhook($data);

            if (isset($data['transaction_id'])) {
                $transaction = Transaction::where('transaction_id', $data['transaction_id'])->first();

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
}
