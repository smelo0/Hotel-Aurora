<?php
declare(strict_types=1);

const WOMPI_PUBLIC_KEY = 'pub_prod_oIXAfuJfGglWlmd5PAqrhWirh3VU66RQ';

function wompiPrivateKey(): string
{
    return trim((string) (getenv('WOMPI_PRIVATE_KEY') ?: ''));
}

function wompiApiUrl(string $transactionId): string
{
    return 'https://production.wompi.co/v1/transactions/' . rawurlencode($transactionId);
}

function verificarTransaccionWompi(string $transactionId, int $amountInCents): array
{
    $privateKey = wompiPrivateKey();
    if ($privateKey === '') {
        throw new RuntimeException('wompi_private_key_missing');
    }

    $curl = curl_init(wompiApiUrl($transactionId));
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $privateKey,
            'Accept: application/json',
        ],
    ]);

    $response = curl_exec($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($response === false || $curlError !== '' || $httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException('wompi_transaction_unavailable');
    }

    $payload = json_decode($response, true);
    $transaction = $payload['data'] ?? null;
    if (!is_array($transaction)) {
        throw new RuntimeException('wompi_transaction_invalid');
    }

    if (($transaction['status'] ?? '') !== 'APPROVED') {
        throw new RuntimeException('wompi_transaction_not_approved');
    }

    if (($transaction['currency'] ?? '') !== 'COP'
        || (int) ($transaction['amount_in_cents'] ?? 0) !== $amountInCents) {
        throw new RuntimeException('wompi_transaction_amount_mismatch');
    }

    return $transaction;
}
