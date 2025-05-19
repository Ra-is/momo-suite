<?php

namespace Rais\MomoSuite\Services;

use Rais\MomoSuite\Contracts\PaymentProvider;
use Rais\MomoSuite\Exceptions\InvalidProviderException;
use Rais\MomoSuite\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MomoService
{
    protected array $config;
    protected array $providers = [];
    protected string $currentProvider;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->currentProvider = $config['default'];
    }

    public function setProvider(string $provider): void
    {
        if (!isset($this->providers[$provider])) {
            throw new InvalidProviderException("Provider {$provider} not found or invalid");
        }
        $this->currentProvider = $provider;
    }

    public function getProvider(): string
    {
        return $this->currentProvider;
    }

    public function registerProvider(string $name, PaymentProvider $provider): void
    {
        $this->providers[$name] = $provider;
    }

    public function provider(string $name): PaymentProvider
    {
        if (!isset($this->providers[$name])) {
            throw new InvalidProviderException("Provider {$name} not found or invalid");
        }

        return $this->providers[$name];
    }

    public function receive(array $data): array
    {
        try {
            $provider = $this->currentProvider;
            $transaction = $this->createTransaction($provider, $data, 'receive');

            // Add the transaction_id to the data before sending to provider
            $data['transaction_id'] = $transaction->transaction_id;

            $result = $this->provider($provider)->receive($data);

            $this->updateTransaction($transaction, $data, $result);

            return $result;
        } catch (\Exception $e) {
            Log::error('MomoService - Receive Error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    public function send(array $data): array
    {
        try {
            $provider = $this->currentProvider;
            $transaction = $this->createTransaction($provider, $data, 'send');

            // Add the transaction_id to the data before sending to provider
            $data['transaction_id'] = $transaction->transaction_id;

            $result = $this->provider($provider)->send($data);

            $this->updateTransaction($transaction, $data, $result);

            return $result;
        } catch (\Exception $e) {

            throw $e;
        }
    }

    protected function createTransaction(string $provider, array $data, string $type = 'receive'): Transaction
    {
        // Ensure we have a transaction_id
        $transactionId = $data['transaction_id'] ?? Str::uuid()->toString();

        $transaction = Transaction::create([
            'provider' => $provider,
            'transaction_id' => $transactionId,
            'reference' => $data['reference'] ?? $transactionId,
            'amount' => $data['amount'],
            'phone' => $data['phone'],
            'network' => $data['network'],
            'status' => 'pending',
            'type' => $type,
            'currency' => $data['currency'] ?? 'GHS',
            'meta' => $data['meta'] ?? null,
            'request' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $transaction;
    }

    protected function updateTransaction(Transaction $transaction, array $data, array $result): void
    {
        // Let the provider handle its own response structure
        $status = $this->provider($transaction->provider)->mapResponseStatus($result);
        $message = $this->provider($transaction->provider)->mapResponseMessage($result);

        $updateData = [
            'response' => $result,
        ];

        // Only set error_message for failed transactions
        if ($status === 'failed') {
            $updateData['status'] = $status;
            $updateData['callback_received_at'] = now();
            $updateData['error_message'] = $message;
        }

        $transaction->update($updateData);

        $transaction->addLog(
            'api_response',
            $status,
            ['response' => $result]
        );
    }

    /**
     * Verify OTP for Paystack transactions
     * @param array $data ['otp' => string, 'reference' => string]
     * @return array
     */
    public function verifyOtp(array $data): array
    {
        if ($this->currentProvider !== 'paystack') {
            throw new \InvalidArgumentException('OTP verification is only supported for Paystack provider.');
        }
        if (!isset($data['reference'])) {
            throw new \InvalidArgumentException('Reference is required for OTP verification.');
        }
        // Check if the reference exists in the transaction table (only transaction_id)
        $transaction = \Rais\MomoSuite\Models\Transaction::where('transaction_id', $data['reference'])->first();
        if (!$transaction) {
            throw new \InvalidArgumentException('No transaction found for the provided reference.');
        }
        $provider = $this->provider('paystack');
        if (!method_exists($provider, 'verifyOtp')) {
            throw new \BadMethodCallException('OTP verification is not supported for this provider.');
        }

        // let us check the stats if it is not pending
        if ($transaction->status !== 'pending') {
            throw new \InvalidArgumentException('Transaction is not pending.');
        }
        $result = $provider->verifyOtp($data);
        // Use normalized status from provider
        $status = $result['status'];
        $message = $result['message'] ?? null;
        $updateData = [
            'response' => $result,
            'status' => $status,
            'error_message' => $status === 'failed' ? $message : null,
        ];
        $transaction->update($updateData);
        $transaction->addLog(
            'otp_verification',
            $status,
            [

                'reference' => $data['reference'],
                'response' => $result,
            ]
        );
        return $result;
    }
}
