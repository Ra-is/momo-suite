<?php

namespace Rais\MomoSuite\Providers;

use Rais\MomoSuite\Contracts\PaymentProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class KorbaProvider extends BaseProvider implements PaymentProvider
{
    protected function getBaseUrl(): string
    {
        return $this->config['base_url'];
    }

    protected function generateKorbaHeaders(array $data): string
    {
        $message = '';
        $n = 0;
        ksort($data);
        foreach ($data as $x => $x_value) {
            if ($n == 0) {
                $message = $message . $x . '=' . $x_value;
            } else {
                $message = $message . '&' . $x . '=' . $x_value;
            }
            $n = $n + 1;
        }
        return $message;
    }

    protected function getHeaders(array $data): array
    {
        $message = $this->generateKorbaHeaders($data);
        $hashcode = hash_hmac('sha256', $message, $this->config['secret_key']);
        $authorization = "HMAC " . $this->config['client_key'] . ":" . $hashcode;

        return [
            'Authorization' => $authorization,
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json',
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
        $transactionId = $data['transaction_id'] ?? Str::uuid()->toString();

        return [
            'customer_number' => $data['phone'],
            'amount' => $data['amount'],
            'transaction_id' => $transactionId,
            'network_code' => $this->mapNetwork($data['network']),
            'callback_url' => route('momo.webhook.korba'),
            'description' => $data['reference'] ?? 'Payment',
            'client_id' => $this->config['client_id'],
        ];
    }

    protected function prepareReceiveData(array $data): array
    {
        $this->validateData($data);

        $transactionId = $data['transaction_id'] ?? Str::uuid()->toString();

        return [
            'customer_number' => $data['phone'],
            'amount' => $data['amount'],
            'transaction_id' => $transactionId,
            'network_code' => $this->mapNetwork($data['network']),
            'callback_url' => route('momo.webhook.korba'),
            'description' => $data['reference'] ?? 'Payment',
            'client_id' => $this->config['client_id'],
        ];
    }

    protected function processWebhook(array $data): array
    {
        try {
            // Validate required fields
            if (!isset($data['transaction_id'])) {
                throw new \InvalidArgumentException('Missing transaction_id in webhook data');
            }

            // Get and validate status
            $status = strtoupper($data['status'] ?? 'FAILED');
            if (!in_array($status, ['SUCCESS', 'FAILED'])) {
                throw new \InvalidArgumentException('Invalid status in webhook data');
            }

            // Map Korba status to our standard status
            $status = match ($status) {
                'SUCCESS' => 'success',
                'FAILED' => 'failed',
                default => 'failed',
            };

            return [
                'status' => $status,
                'message' => $data['message'] ?? 'No message provided',
                'transaction_id' => $data['transaction_id'],
                'raw_data' => $data,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
                'transaction_id' => $data['transaction_id'] ?? null,
                'raw_data' => $data,
            ];
        }
    }

    protected function mapNetwork(string $network): string
    {
        return match (strtoupper($network)) {
            'MTN' => 'MTN',
            'AIRTELTIGO' => 'AIR',
            'TELECEL' => 'VOD',
            default => throw new \InvalidArgumentException("Invalid network: {$network}"),
        };
    }

    public function send(array $data): array
    {
        try {
            $preparedData = $this->prepareSendData($data);
            $response = $this->client->post($this->getBaseUrl() . 'disburse/', [
                'headers' => $this->getHeaders($preparedData),
                'json' => $preparedData,
            ]);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $responseData = json_decode($response->getBody()->getContents(), true);
                return [
                    'success' => true,
                    'transaction_id' => $preparedData['transaction_id'],
                    'data' => $responseData,
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to process payment',
                'transaction_id' => $preparedData['transaction_id'],
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'transaction_id' => $data['transaction_id'] ?? null,
            ];
        }
    }

    public function receive(array $data): array
    {
        try {
            $preparedData = $this->prepareReceiveData($data);
            $response = $this->client->post($this->getBaseUrl() . 'collect/', [
                'headers' => $this->getHeaders($preparedData),
                'json' => $preparedData,
            ]);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $responseData = json_decode($response->getBody()->getContents(), true);
                return [
                    'success' => true,
                    'transaction_id' => $preparedData['transaction_id'],
                    'data' => $responseData,
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to process payment',
                'transaction_id' => $preparedData['transaction_id'],
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'transaction_id' => $data['transaction_id'] ?? null,
            ];
        }
    }

    public function mapResponseStatus(array $response): string
    {
        if (isset($response['status'])) {
            return match (strtolower($response['status'])) {
                'success' => 'success',
                'failed' => 'failed',
                default => 'pending',
            };
        }

        return 'pending';
    }

    public function mapResponseMessage(array $response): string
    {
        return $response['message'] ?? $response['detail'] ?? '';
    }

    public function checkStatus(string $transactionId): array
    {
        try {
            $data = [
                'transaction_id' => $transactionId,
                'client_id' => $this->config['client_id'],
            ];

            $response = $this->client->post($this->getBaseUrl() . 'transaction_status/', [
                'headers' => $this->getHeaders($data),
                'json' => $data,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                // Handle successful response
                return [
                    'success' => true,
                    'status' => $this->mapResponseStatus($responseData),
                    'message' => $this->mapResponseMessage($responseData),
                    'transaction_id' => $transactionId,
                    'provider_transaction_id' => $responseData['korba_trans_id'] ?? null,
                    'raw_response' => $responseData,
                ];
            }

            // Handle unsuccessful response
            return [
                'success' => false,
                'status' => 'failed',
                'message' => $responseData['error_message'] ?? 'Failed to check transaction status',
                'transaction_id' => $transactionId,
                'raw_response' => $responseData,
            ];
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
