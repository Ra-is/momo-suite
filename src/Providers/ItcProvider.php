<?php

namespace Rais\MomoSuite\Providers;

use Rais\MomoSuite\Contracts\PaymentProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;
use Rais\MomoSuite\Models\Transaction;
use Illuminate\Support\Facades\Validator;

class ItcProvider extends BaseProvider implements PaymentProvider
{
    private $result_codes_messages = [
        "03" => "Processing payment",
        "01" => "Payment successful",
        "100" => "Payment failed",
        "400" => "Invalid request",
        "107" => "API credentials are invalid",
        "112" => "Service unavailable. Try again later",
        "131" => "Request timed out",
        "121" => "Not allowed to access this service",
        "300" => "Insufficient Funds In merchant account",
        "110" => "Duplicate Transaction"
    ];


    private $callback_codes_messages = [
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

    protected function getBaseUrl(): string
    {
        return $this->config['base_url'];
    }

    protected function getHeaders(array $data): array
    {
        return [
            'Content-Type' => 'application/json',
            'x-key' => $this->config['api_key'],
            'x-country' => 'GH'
        ];
    }

    protected function validateData(array $data): void
    {
        $validator = Validator::make($data, [
            'phone' => 'required|regex:/^0[0-9]{9}$/',
            'amount' => 'required|numeric|gt:0',
            'network' => 'required|in:MTN,AIRTELTIGO,TELECEL',
            'meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }
    }

    protected function prepareSendData(array $data): array
    {
        $this->validateData($data);
        return [
            'refNo' => $data['transaction_id'] ?? Str::uuid()->toString(),
            'productId' => $this->config['product_id_credit'],
            'transflowId' => $this->config['transflow_id'],
            'msisdn' => '233' . substr($data['phone'], -9),
            'network' => $this->mapNetwork($data['network']),
            'amount' => (string) $data['amount'],
            'narration' => $data['reference'] ?? 'Payment',
            'currency' => 'GHS',
        ];
    }

    protected function prepareReceiveData(array $data): array
    {
        $this->validateData($data);
        return [
            'refNo' => $data['transaction_id'] ?? Str::uuid()->toString(),
            'productId' => $this->config['product_id_debit'],
            'transflowId' => $this->config['transflow_id'],
            'msisdn' => '233' . substr($data['phone'], -9),
            'network' => $this->mapNetwork($data['network']),
            'amount' => (string) $data['amount'],
            'narration' => $data['reference'] ?? 'Payment',
            'currency' => 'GHS',
        ];
    }

    protected function mapNetwork(string $network): string
    {
        return match (strtoupper($network)) {
            'MTN' => 'MTN',
            'TELECEL' => 'VODAFONE',
            'AIRTELTIGO' => 'AIRTELTIGO',
            default => throw new \InvalidArgumentException("Invalid network: {$network}"),
        };
    }

    public function send(array $data): array
    {
        try {
            $preparedData = $this->prepareSendData($data);
            $response = $this->client->post($this->getBaseUrl() . '/credit', [
                'headers' => $this->getHeaders($preparedData),
                'json' => $preparedData,
                'verify' => false
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (isset($responseData['responseCode']) && $responseData['responseCode'] == '03') {
                return [
                    'success' => true,
                    'message' => 'Transaction Initiated',
                    //'status' => 'pending',
                    'transaction_id' => $preparedData['refNo'],
                    'data' => $responseData
                ];
            }

            return [
                'success' => false,
                'message' => $this->result_codes_messages[$responseData['responseCode']] ?? 'Transaction failed',
                'status' => 'failed',
                'transaction_id' => $preparedData['refNo'],
                'error_code' => $responseData['responseCode'] ?? '601',
                'data' => $responseData
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 'failed',
                'transaction_id' => $data['transaction_id'] ?? null,
            ];
        }
    }

    public function receive(array $data): array
    {
        try {
            $preparedData = $this->prepareReceiveData($data);

            $response = $this->client->post($this->getBaseUrl() . '/debit', [
                'headers' => $this->getHeaders($preparedData),
                'json' => $preparedData,
                'verify' => false
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (isset($responseData['responseCode']) && $responseData['responseCode'] == '03') {
                return [
                    'success' => true,
                    'message' => 'Transaction Initiated',
                    'status' => 'pending',
                    'transaction_id' => $preparedData['refNo'],
                    'data' => $responseData
                ];
            }

            return [
                'success' => false,
                'message' => $this->result_codes_messages[$responseData['responseCode']] ?? 'Transaction failed',
                'status' => 'failed',
                'transaction_id' => $preparedData['refNo'],
                'error_code' => $responseData['responseCode'] ?? '',
                'data' => $responseData
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 'failed',
                'transaction_id' => $data['transaction_id'] ?? null,
            ];
        }
    }

    protected function processWebhook(array $data): array
    {
        $status = match ($data['responseCode'] ?? '') {
            '01' => 'success',
            '03' => 'pending',
            default => 'failed'
        };

        return [
            'status' => $status,
            'message' => $this->callback_codes_messages[$data['responseCode']] ?? 'Unknown status',
            'transaction_id' => $data['refNo'] ?? null,
        ];
    }

    public function mapResponseStatus(array $response): string
    {
        $responseCode = $response['data']['responseCode'] ?? '';
        return match ($responseCode) {
            '01' => 'success',
            '03' => 'pending',
            default => 'failed'
        };
    }

    public function mapResponseMessage(array $response): string
    {
        $responseCode = $response['responseCode'] ?? null;

        if ($responseCode && isset($this->result_codes_messages[$responseCode])) {
            return $this->result_codes_messages[$responseCode];
        }

        return $response['message'] ?? 'Unknown transaction status';
    }

    public function checkStatus(string $transactionId): array
    {
        try {
            // Find the transaction to determine type
            $transaction = Transaction::where('transaction_id', $transactionId)->first();
            if (!$transaction) {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => 'Transaction not found',
                    'transaction_id' => $transactionId,
                ];
            }

            // Determine payload based on type
            if ($transaction->type === 'send') {
                $payload = [
                    'productId' => $this->config['product_id_credit'],
                    'transflowId' => $this->config['transflow_id'],
                    'refNo' => $transactionId,
                ];
            } else {
                $payload = [
                    'productId' => $this->config['product_id_debit'],
                    'transflowId' => $this->config['transflow_id'],
                    'refNo' => $transactionId,
                ];
            }

            $url = rtrim($this->getBaseUrl(), '/') . '/check-transaction-status/' . $transactionId;
            $response = $this->client->post($url, [
                'headers' => $this->getHeaders($payload),
                'json' => $payload,
                'verify' => false,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            $responseCode = $responseData['responseCode'] ?? '';

            // Safer approach: Only treat '01' as success, '03' as pending, everything else as failed
            switch ($responseCode) {
                case '01':
                    return [
                        'success' => true,
                        'status' => 'success',
                        'message' => $responseData['responseMessage'] ?? 'Transaction Successful',
                        'transaction_id' => $transactionId,
                        'provider_transaction_id' => $responseData['uniwalletTransactionId'] ?? null,
                        'raw_response' => $responseData,
                    ];
                case '03':
                    return [
                        'success' => true,
                        'status' => 'pending',
                        'message' => $responseData['responseMessage'] ?? 'Transaction Pending',
                        'transaction_id' => $transactionId,
                        'provider_transaction_id' => $responseData['uniwalletTransactionId'] ?? null,
                        'raw_response' => $responseData,
                    ];
                default:
                    return [
                        'success' => true,
                        'status' => 'failed',
                        'message' => $responseData['responseMessage'] ?? 'Transaction Failed',
                        'transaction_id' => $transactionId,
                        'provider_transaction_id' => $responseData['uniwalletTransactionId'] ?? null,
                        'raw_response' => $responseData,
                    ];
            }
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ];
        }
    }
}
