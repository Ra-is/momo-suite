<?php

namespace Rais\MomoSuite\Providers;

use Rais\MomoSuite\Contracts\PaymentProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

abstract class BaseProvider implements PaymentProvider
{
    protected array $config;
    protected Client $client;
    protected string $baseUrl;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client();
        $this->baseUrl = $this->getBaseUrl();
    }

    abstract protected function getBaseUrl(): string;

    public function send(array $data): array
    {
        try {
            $response = $this->client->post($this->baseUrl . '/send', [
                'headers' => $this->getHeaders($data),
                'json' => $this->prepareSendData($data),
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function receive(array $data): array
    {
        try {
            $response = $this->client->post($this->baseUrl . '/receive', [
                'headers' => $this->getHeaders($data),
                'json' => $this->prepareReceiveData($data),
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function handleWebhook(array $data): array
    {
        return $this->processWebhook($data);
    }

    abstract protected function getHeaders(array $data): array;

    abstract protected function prepareSendData(array $data): array;
    abstract protected function prepareReceiveData(array $data): array;
    abstract protected function processWebhook(array $data): array;

    public function mapResponseStatus(array $response): string
    {
        return 'pending'; // Default implementation, override in child classes
    }

    public function mapResponseMessage(array $response): string
    {
        return $response['message'] ?? ''; // Default implementation, override in child classes
    }
}
