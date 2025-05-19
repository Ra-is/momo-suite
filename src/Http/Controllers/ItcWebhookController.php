<?php

namespace Rais\MomoSuite\Http\Controllers;

use Illuminate\Http\Request;
use Rais\MomoSuite\Models\Transaction;
use Rais\MomoSuite\Services\MomoService;
use Rais\MomoSuite\Http\Controllers\Traits\HandlesWebhooks;

class ItcWebhookController extends Controller
{
    use HandlesWebhooks;

    protected MomoService $momoService;

    private $result_codes_messages = [
        "03" => "Processing payment",
        "01" => "Payment successful",
        "100" => "You did not authorize your transaction. Please try again.",
        "400" => "Insufficient Funds to support this transaction",
        "107" => "API credentials are invalid",
        "112" => "Service unavailable. Try again later",
        "131" => "Request timed out",
        "121" => "Not allowed to access this service",
        "300" => "Insufficient Funds In merchant account",
        "110" => "Duplicate Transaction",
        "529" => "You do not have sufficient funds to support this transaction. Please load your wallet with enough funds and try again.",
        "527" => "You do not have a mobile money wallet. Please try again later.",
        "515" => "You do not have a mobile money wallet. Please try again later.",
        "682" => "An internal error caused the operation to fail",
        "779" => "Some other transactional operation is being performed on the wallet therefore this transaction can not be completed at this time"
    ];

    public function __construct(MomoService $momoService)
    {
        $this->momoService = $momoService;
    }

    public function handle(Request $request)
    {
        $data = $request->all();
        $transactionId = $request->get('refNo');

        try {
            // Check if transaction exists and hasn't been processed
            $transaction = Transaction::where('transaction_id', $transactionId)
                ->first();

            if (!$transaction) {
                return response()->json([
                    'responseCode' => '01',
                    'responseMessage' => 'Transaction not found or already processed'
                ]);
            }

            // Process the webhook data
            $provider = $this->momoService->provider('itc');
            $result = $provider->handleWebhook($data);

            // Update transaction status
            $this->updateTransaction($transaction, $data, $result);

            // Return acknowledgment
            return response()->json([
                'responseCode' => '01',
                'responseMessage' => 'Callback Acknowledged'
            ]);
        } catch (\Exception $e) {
            if (isset($transaction)) {
                $this->logError($transaction, $e, $data);
            }


            // Return acknowledgment
            return response()->json([
                'responseCode' => '01',
                'responseMessage' => 'Callback Acknowledged'
            ]);
        }
    }
}
