<?php

namespace Rais\MomoSuite\Providers;

use Rais\MomoSuite\Contracts\PaymentProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class PaystackProvider extends BaseProvider implements PaymentProvider
{
    protected function getBaseUrl(): string
    {
        return $this->config['base_url'];
    }

    protected function getHeaders(array $data): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config['secret_key'],
            'Content-Type' => 'application/json',
        ];
    }

    protected function validateData(array $data): void
    {
        $validator = Validator::make($data, [
            'phone' => 'required|regex:/^0[0-9]{9}$/',
            'amount' => 'required|numeric|gt:0',
            'email' => 'required|email',
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

        // Use the transaction_id from the data or generate a new one
        $transactionId = $data['transaction_id'] ?? Str::uuid()->toString();


        return [
            'amount' => $data['amount'] * 100, // Convert to pesewas
            'email' => $data['email'],
            'currency' => 'GHS',
            'mobile_money' => [
                'phone' => $data['phone'],
                'provider' => $this->mapNetwork($data['network']),
            ],
            'reference' => $transactionId, // Use the transaction_id as reference
        ];
    }

    protected function prepareReceiveData(array $data): array
    {
        return $this->prepareSendData($data);
    }

    protected function mapNetwork(string $network): string
    {
        return match (strtoupper($network)) {
            'MTN' => 'MTN',
            'AIRTELTIGO' => 'ATL',
            'TELECEL' => 'VOD',
            default => throw new \InvalidArgumentException("Invalid network: {$network}"),
        };
    }

    public function send(array $data): array
    {
        $this->validateData($data);
        try {
            // Step 1: Create transfer recipient
            $payload = [
                'type' => 'mobile_money',
                'name' => $data['customer_name'] ?? 'Recipient',
                'account_number' => $data['phone'],
                'bank_code' => $this->mapNetwork($data['network']),
                'currency' => 'GHS',
            ];

            $recipientResponse = $this->client->post($this->getBaseUrl() . '/transferrecipient', [
                'headers' => $this->getHeaders($data),
                'json' => $payload,
            ]);

            $recipientData = json_decode($recipientResponse->getBody()->getContents(), true);

            if ($recipientData['status'] !== true || empty($recipientData['data']['recipient_code'])) {
                return [
                    'success' => false,
                    'message' => $recipientData['message'] ?? 'Failed to create transfer recipient',
                    'status' => 'failed',
                    'transaction_id' => $data['transaction_id'] ?? null,
                    'data' => $recipientData,
                ];
            }

            // Step 2: Initiate transfer
            $transferPayload = [
                'source' => 'balance',
                'amount' => (int)($data['amount'] * 100), // GHS to pesewas
                'recipient' => $recipientData['data']['recipient_code'],
                'reason' => $data['reference'] ?? 'Payout',
                'currency' => 'GHS',
                'reference' => $data['transaction_id'] ?? Str::uuid()->toString(),
            ];

            $transferResponse = $this->client->post($this->getBaseUrl() . '/transfer', [
                'headers' => $this->getHeaders($data),
                'json' => $transferPayload,
            ]);

            $transferData = json_decode($transferResponse->getBody()->getContents(), true);

            if ($transferData['status'] === true) {
                return [
                    'success' => true,
                    'message' => $transferData['message'] ?? 'Transfer initiated',
                    'status' => 'pending',
                    'transaction_id' => $data['transaction_id'] ?? null,
                    'data' => [
                        'recipient' => $recipientData['data'],
                        'transfer' => $transferData['data'] ?? [],
                    ],
                ];
            }

            return [
                'success' => false,
                'message' => $transferData['message'] ?? 'Failed to initiate transfer',
                'status' => 'failed',
                'transaction_id' => $data['transaction_id'] ?? null,
                'data' => [
                    'recipient' => $recipientData['data'],
                    'transfer' => $transferData,
                ],
            ];
        } catch (GuzzleException $e) {
            $fullMessage = $e->getMessage();
            $responseBody = '';
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
            } else {
            }
            return [
                'success' => false,
                'message' => $fullMessage,
                'status' => 'failed',
                'transaction_id' => $data['transaction_id'] ?? null,
                'error_response' => $responseBody,
            ];
        }
    }

    public function receive(array $data): array
    {
        $this->validateData($data);
        try {
            $preparedData = $this->prepareReceiveData($data);
            $response = $this->client->post($this->getBaseUrl() . '/charge', [
                'headers' => $this->getHeaders($preparedData),
                'json' => $preparedData,
            ]);
            $responseData = json_decode($response->getBody()->getContents(), true);

            if ($responseData['status'] === true) {

                return [
                    'success' => true,
                    'message' => $responseData['data']['display_text'] ?? $responseData['data']['gateway_response'] ?? $responseData['message'] ?? 'Transaction pending',
                    'status' => 'pending',
                    'transaction_id' => $preparedData['reference'],
                    'data' => $responseData['data']
                ];
            }

            return [
                'success' => false,
                'message' => $responseData['data']['gateway_response'] ?? $responseData['message'] ?? 'Transaction failed',
                'status' => 'failed',
                'transaction_id' => $preparedData['reference'],
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
        // Check if this is a charge or transfer event
        if (!in_array($data['event'], ['charge.success', 'transfer.success', 'transfer.failed', 'transfer.reversed'])) {
            return [
                'status' => 'failed',
                'message' => 'Invalid event type: ' . ($data['event'] ?? 'unknown'),
                'transaction_id' => $data['data']['reference'] ?? null,
            ];
        }

        $eventData = $data['data'];

        // Handle transfer events
        if (str_starts_with($data['event'], 'transfer.')) {
            // First check the event type
            $eventStatus = match ($data['event']) {
                'transfer.success' => 'success',
                'transfer.failed' => 'failed',
                'transfer.reversed' => 'failed', // Treat reversed as failed since money was returned
                default => 'failed'
            };

            // Then check the data.status for more granular status
            $dataStatus = match ($eventData['status'] ?? '') {
                'success' => 'success',
                'pending' => 'pending',
                'reversed' => 'failed',
                'failed' => 'failed',
                'abandoned' => 'failed',
                'blocked' => 'failed',
                'rejected' => 'failed',
                'otp' => 'pending',
                'received' => 'pending',
                default => 'failed'
            };

            // Use the more specific status from data.status
            $status = $dataStatus;

            return [
                'status' => $status,
                'message' => $eventData['reason'] ?? 'Transfer status check',
                'transaction_id' => $eventData['reference'],
                'amount' => $eventData['amount'] / 100, // Convert back to GHS
                'network' => $eventData['recipient']['details']['bank_code'] ?? null,
                'phone' => $eventData['recipient']['details']['account_number'] ?? null,
                'data' => $eventData
            ];
        }

        // Handle charge.success event
        $status = match ($eventData['status']) {
            'success' => 'success',
            'pending' => 'pending',
            default => 'failed'
        };

        return [
            'status' => $status,
            'message' => $eventData['gateway_response'] ?? $eventData['message'] ?? 'Unknown status',
            'transaction_id' => $eventData['reference'],
            'amount' => $eventData['amount'] / 100, // Convert back to GHS
            'network' => $eventData['channel'] === 'mobile_money' ? 'MTN' : null,
            'phone' => $eventData['metadata']['custom_fields'][0]['value'] ?? null,
            'data' => $eventData
        ];
    }

    public function mapResponseStatus(array $response): string
    {
        return match ($response['status'] ?? '') {
            'success' => 'success',
            'pending' => 'pending',
            default => 'failed'
        };
    }

    public function mapResponseMessage(array $response): string
    {
        return $response['message'] ?? '';
    }

    public function checkStatus(string $transactionId): array
    {
        try {
            $url = $this->getBaseUrl() . '/transaction/verify/' . $transactionId;

            $response = $this->client->get($url, [
                'headers' => $this->getHeaders([]),
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            // Check if API call was successful
            if (!$responseData['status']) {
                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Failed to verify transaction',
                    'raw_response' => $responseData,
                ];
            }

            // Get transaction status from data object
            $transactionData = $responseData['data'];
            $status = match ($transactionData['status']) {
                'success' => 'success',
                'failed' => 'failed',
                default => 'pending',
            };

            // Convert amount from kobo to GHS (divide by 100)
            $amount = $transactionData['amount'] / 100;
            $fees = $transactionData['fees'] / 100;

            return [
                'success' => true,
                'status' => $status,
                'message' => $transactionData['gateway_response'] ?? $responseData['message'],
                'provider_transaction_id' => $transactionData['id'],
                'total_amount' => $amount,
                'charged_amount' => $fees,
                'raw_response' => $responseData,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'raw_response' => null,
            ];
        }
    }

    /**
     * Verify OTP for a Paystack charge
     * @param array $data ['otp' => string, 'reference' => string]
     * @return array
     */
    public function verifyOtp(array $data): array
    {
        $this->validateOtpData($data);
        try {
            $response = $this->client->post($this->getBaseUrl() . '/charge/submit_otp', [
                'headers' => $this->getHeaders($data),
                'json' => [
                    'otp' => $data['otp'],
                    'reference' => $data['reference'],
                ],
                'verify' => false,
            ]);
            $responseData = json_decode($response->getBody()->getContents(), true);

            // Normalize status
            $status = 'pending';
            if (
                ($responseData['status'] === false) ||
                (isset($responseData['data']['status']) && $responseData['data']['status'] === 'failed')
            ) {
                $status = 'failed';
            }

            return [
                'status' => $status,
                'message' => $responseData['message'] ?? '',
                'data' => $responseData['data'] ?? [],
                'raw' => $responseData,
            ];
        } catch (GuzzleException $e) {
            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => [],
                'raw' => [],
            ];
        }
    }

    protected function validateOtpData(array $data): void
    {
        $validator = Validator::make($data, [
            'otp' => 'required|string',
            'reference' => 'required|string',
        ]);
        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }
    }
}
