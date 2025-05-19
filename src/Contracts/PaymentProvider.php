<?php

namespace Rais\MomoSuite\Contracts;

interface PaymentProvider
{
    public function send(array $data): array;
    public function receive(array $data): array;
    public function handleWebhook(array $data): array;
    public function mapResponseStatus(array $response): string;
    public function mapResponseMessage(array $response): string;
}
