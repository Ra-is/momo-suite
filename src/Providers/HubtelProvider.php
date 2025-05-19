<?php

namespace Rais\MomoSuite\Providers;

use Rais\MomoSuite\Contracts\PaymentProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;
use Rais\MomoSuite\Providers\BaseProvider;
use Illuminate\Support\Facades\Log;
use Rais\MomoSuite\Models\Transaction;
use Illuminate\Support\Facades\Validator;

class HubtelProvider extends BaseProvider implements PaymentProvider
{
    protected Client $client;
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client();
    }

    protected function getBaseUrl(): string
    {
        // This is required by BaseProvider but we don't use it directly
        // as we have separate URLs for receive and send operations
        return $this->config['receive_url'];
    }

    protected function getReceiveUrl(): string
    {
        return $this->config['receive_url'];
    }

    protected function getSendUrl(): string
    {
        return $this->config['send_url'];
    }

    protected function getHeaders(array $data = []): array
    {
        $authString = base64_encode($this->config['username'] . ':' . $this->config['password']);

        return [
            'Authorization' => 'Basic ' . $authString,
            'Content-Type' => 'application/json',
        ];
    }


    protected function validateData(array $data): void
    {
        $validator = Validator::make($data, [
            'phone' => 'required|regex:/^0[0-9]{9}$/',
            'amount' => 'required|numeric|gt:0',
            'customer_name' => 'required|string|max:255',
            'network' => 'required|in:MTN,AIRTELTIGO,TELECEL',
            'meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }
    }

    protected function prepareReceiveData(array $data): array
    {
        $this->validateData($data);

        $transactionId = $data['transaction_id'] ?? Str::uuid()->toString();

        return [
            'CustomerName' => $data['customer_name'] ?? 'Customer',
            'CustomerMsisdn' => "233" . substr($data['phone'], -9),
            'Channel' => $this->mapNetwork($data['network']),
            'Amount' => $data['amount'],
            'PrimaryCallbackURL' => route('momo.webhook.hubtel'),
            'Description' => $data['reference'] ?? 'Payment',
            'ClientReference' => $transactionId,
        ];
    }

    protected function prepareSendData(array $data): array
    {
        $this->validateData($data);

        $transactionId = $data['transaction_id'] ?? Str::uuid()->toString();

        return [
            'RecipientName' => $data['customer_name'] ?? 'Recipient',
            'RecipientMsisdn' => "233" . substr($data['phone'], -9),
            'Channel' => $this->mapNetwork($data['network']),
            'Amount' => $data['amount'],
            'PrimaryCallbackURL' => route('momo.webhook.hubtel'),
            'Description' => $data['reference'] ?? 'Payment',
            'ClientReference' => $transactionId,
        ];
    }

    protected function processWebhook(array $data): array
    {
        $statusCode = $data['ResponseCode'] ?? null;
        $description = $data['Data']['Description'] ?? 'No message provided';

        $status = match ($statusCode) {
            '0000' => 'success',
            '0001' => 'pending',
            default => 'failed'
        };

        // Log specific error cases
        if ($statusCode === '0005') {
        } elseif ($statusCode === '4075') {
        }

        return [
            'status' => $status,
            'message' => $description,
            'transaction_id' => $data['Data']['ClientReference'] ?? null,
            'charged_amount' => $data['Data']['Charges'] ?? 0.00,
            'total_amount' => $data['Data']['AmountDebited'] ?? $data['Data']['Amount'] ?? 0.00,
        ];
    }

    protected function mapNetwork(string $network): string
    {
        return match (strtoupper($network)) {
            'MTN' => 'mtn-gh',
            'TELECEL' => 'vodafone-gh',
            'AIRTELTIGO' => 'tigo-gh',
            default => throw new \InvalidArgumentException("Invalid network: {$network}"),
        };
    }

    public function receive(array $data): array
    {
        try {


            $preparedData = $this->prepareReceiveData($data);
            $url = $this->getReceiveUrl() . $this->config['pos_id_sales'] . '/receive/mobilemoney';

            $response = $this->client->post($url, [
                'headers' => $this->getHeaders(),
                'json' => $preparedData,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (isset($responseData['ResponseCode']) && $responseData['ResponseCode'] === '0001') {
                return [
                    'success' => true,
                    'transaction_id' => $preparedData['ClientReference'],
                    'data' => $responseData['Data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => $responseData['Data'] ?? 'Failed to process payment',
                'transaction_id' => $preparedData['ClientReference'],
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'transaction_id' => $data['transaction_id'] ?? null,
            ];
        }
    }

    public function send(array $data): array
    {
        try {
            if (!isset($data['customer_name'])) {
                throw new \InvalidArgumentException('customer_name is required for Hubtel provider');
            }

            $preparedData = $this->prepareSendData($data);
            $url = $this->getSendUrl() . $this->config['pos_id_deposit'] . '/send/mobilemoney';

            $response = $this->client->post($url, [
                'headers' => $this->getHeaders(),
                'json' => $preparedData,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (isset($responseData['ResponseCode']) && $responseData['ResponseCode'] === '0001') {
                return [
                    'success' => true,
                    'transaction_id' => $preparedData['ClientReference'],
                    'data' => $responseData['Data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => $responseData['Data'] ?? 'Failed to process payment',
                'transaction_id' => $preparedData['ClientReference'],
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
        if (isset($response['Data']['Status'])) {
            return match (strtolower($response['Data']['Status'])) {
                'successful' => 'success',
                'failed' => 'failed',
                default => 'pending',
            };
        }

        return 'pending';
    }

    public function mapResponseMessage(array $response): string
    {
        return $response['Data']['Description'] ?? '';
    }

    public function checkStatus(string $transactionId): array
    {
        try {
            // Get the transaction to determine the type
            $transaction = Transaction::where('transaction_id', $transactionId)->first();
            if (!$transaction) {
                return [
                    'success' => false,
                    'message' => 'Transaction not found',
                ];
            }

            $authString = base64_encode($this->config['username'] . ':' . $this->config['password']);
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $authString,
            ];

            // Determine URL and status type based on transaction type
            if ($transaction->type === 'receive') {
                $posId = $this->config['pos_id_sales'];
                $statusType = 'sales';
                $url = $this->config['receive_status_url'] . $posId . '/status?clientReference=' . $transactionId;
            } else {
                $posId = $this->config['pos_id_deposit'];
                $statusType = 'deposit';
                $url = $this->config['send_status_url'] . $posId . '/transactions/status?clientReference=' . $transactionId;
            }

            // Make GET request
            $response = $this->client->get($url, [
                'headers' => $headers,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (!isset($responseData['Data'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid response from Hubtel',
                    'raw_response' => $responseData,
                ];
            }

            // Process response based on status type
            if ($statusType === 'sales') {
                $status = $responseData['Data']['transactionStatus'] ?? '';
                $status = match ($status) {
                    'success' => 'success',
                    'failed' => 'failed',
                    default => 'pending',
                };

                $amount = $responseData['Data']['amount'] ?? 0.00;
                $charges = $responseData['Data']['charges'] ?? 0.00;
            } elseif ($statusType === 'deposit') {
                $status = $responseData['Data']['status'] ?? '';
                $status = match ($status) {
                    'paid' => 'success',
                    'Unpaid' => 'failed',
                    default => 'pending',
                };

                $amount = $responseData['Data']['Amount'] ?? 0.00;
                $charges = $responseData['Data']['Fees'] ?? 0.00;
            }

            return [
                'success' => true,
                'status' => $status,
                'message' => $responseData['Data']['Description'] ?? 'Status check completed',
                'provider_transaction_id' => $responseData['Data']['TransactionId'] ?? null,
                'total_amount' => $amount,
                'charged_amount' => $charges,
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
}
