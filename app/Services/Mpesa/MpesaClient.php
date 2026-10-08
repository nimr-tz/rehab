<?php

namespace App\Services\Mpesa;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use RuntimeException;

/**
 * Calls the Vodacom M-Pesa OpenAPI: sessions, C2B single-stage payments and
 * transaction status. The session ID is cached and reused until it expires.
 */
class MpesaClient
{
    private const SESSION_CACHE_KEY = 'mpesa.session';

    /**
     * Push a payment prompt to the customer's phone. The call returns once they
     * enter their PIN, decline, or the prompt times out.
     */
    public function c2bPayment(string $msisdn, string $amount, string $reference, string $conversationId, string $description): MpesaResult
    {
        return $this->call('POST', 'c2bPayment/singleStage/', [
            'input_Amount' => $amount,
            'input_CustomerMSISDN' => self::normalizeMsisdn($msisdn),
            'input_Country' => config('mpesa.country'),
            'input_Currency' => config('mpesa.currency'),
            'input_ServiceProviderCode' => config('mpesa.service_provider_code'),
            'input_TransactionReference' => $reference,
            'input_ThirdPartyConversationID' => $conversationId,
            'input_PurchasedItemsDesc' => $description,
        ]);
    }

    /**
     * Look up a transaction by M-Pesa's transaction ID, our conversation ID or
     * OpenAPI's conversation ID. The docs list GET; their samples use POST.
     */
    public function queryTransactionStatus(string $queryReference, string $conversationId, string $method = 'GET'): MpesaResult
    {
        return $this->call($method, 'queryTransactionStatus/', [
            'input_QueryReference' => $queryReference,
            'input_ServiceProviderCode' => config('mpesa.service_provider_code'),
            'input_ThirdPartyConversationID' => $conversationId,
            'input_Country' => config('mpesa.country'),
        ]);
    }

    /** Open a new session, replacing any cached one. */
    public function openSession(): MpesaResult
    {
        $result = $this->send('GET', 'getSession/', [], $this->encrypt(config('mpesa.api_key')));

        if ($result->successful() && $result->get('output_SessionID')) {
            Cache::put(self::SESSION_CACHE_KEY, [
                'id' => $result->get('output_SessionID'),
                'issued_at' => now()->getTimestamp(),
            ], now()->addMinutes(config('mpesa.session_minutes')));
        }

        return $result;
    }

    /**
     * Open a session ahead of time, so a payment does not wait for it to become
     * active. Does nothing while the cached session has five minutes or more left.
     */
    public function warmUp(): void
    {
        $session = Cache::get(self::SESSION_CACHE_KEY);
        $expiresAt = ($session['issued_at'] ?? 0) + config('mpesa.session_minutes') * 60;

        if ($expiresAt - now()->getTimestamp() < 300) {
            $this->openSession();
        }
    }

    public function forgetSession(): void
    {
        Cache::forget(self::SESSION_CACHE_KEY);
    }

    /** Encrypt a key with the platform's public key (RSA, PKCS#1 v1.5), as Base64. */
    public function encrypt(string $plain): string
    {
        $pem = "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split(trim((string) config('mpesa.public_key')), 64, "\n")
            ."-----END PUBLIC KEY-----\n";

        $key = openssl_pkey_get_public($pem);

        if ($key === false || ! openssl_public_encrypt($plain, $encrypted, $key, OPENSSL_PKCS1_PADDING)) {
            throw new RuntimeException('M-Pesa public key is missing or invalid.');
        }

        return base64_encode($encrypted);
    }

    /** Tanzanian numbers go to M-Pesa as 255XXXXXXXXX. */
    public static function normalizeMsisdn(string $msisdn): string
    {
        $digits = preg_replace('/\D/', '', $msisdn);

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return '255'.substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            return '255'.$digits;
        }

        return $digits;
    }

    private function call(string $method, string $path, array $params): MpesaResult
    {
        return $this->send($method, $path, $params, $this->encrypt($this->sessionId()));
    }

    private function sessionId(): string
    {
        $session = Cache::get(self::SESSION_CACHE_KEY);

        if (! $session) {
            $result = $this->openSession();

            if (! $result->successful()) {
                throw new RuntimeException("M-Pesa session failed: {$result->code} {$result->description}");
            }

            $session = Cache::get(self::SESSION_CACHE_KEY);
        }

        $wait = $session['issued_at'] + config('mpesa.session_warmup_seconds') - now()->getTimestamp();

        if ($wait > 0) {
            Sleep::for($wait)->seconds();
        }

        return $session['id'];
    }

    private function send(string $method, string $path, array $params, string $token): MpesaResult
    {
        $url = sprintf('%s/%s/ipg/v2/%s/%s', config('mpesa.host'), config('mpesa.environment'), config('mpesa.market'), $path);

        $response = Http::withHeaders(['Origin' => config('mpesa.origin')])
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout(config('mpesa.timeout_seconds'))
            ->send($method, $url, $method === 'GET' ? ['query' => $params] : ['json' => $params]);

        $body = $response->json() ?? [];

        return new MpesaResult(
            status: $response->status(),
            code: $body['output_ResponseCode'] ?? null,
            description: $body['output_ResponseDesc'] ?? null,
            body: $body,
        );
    }
}
