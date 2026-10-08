<?php

namespace Tests\Unit;

use App\Services\Mpesa\MpesaClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/** The M-Pesa client: key encryption, sessions and request shapes. Payments are high-risk, so every rule is tested. */
class MpesaClientTest extends TestCase
{
    // A throwaway key pair stands in for Vodacom's, so the test can decrypt what the client sends.
    private string $privateKey = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->privateKey = file_get_contents(base_path('tests/fixtures/mpesa/test-private.pem'));
        $publicPem = file_get_contents(base_path('tests/fixtures/mpesa/test-public.pem'));

        config([
            'mpesa.environment' => 'sandbox',
            'mpesa.api_key' => 'test-api-key',
            'mpesa.public_key' => preg_replace('/-----[^-]+-----|\s/', '', $publicPem),
            'mpesa.origin' => '*',
            'mpesa.service_provider_code' => '000000',
            'mpesa.session_warmup_seconds' => 30,
        ]);

        Cache::flush();
        Sleep::fake();
    }

    public function test_encrypts_with_the_public_key(): void
    {
        $this->assertSame('test-api-key', $this->decrypt(app(MpesaClient::class)->encrypt('test-api-key')));
    }

    public function test_opens_a_session_with_the_encrypted_api_key(): void
    {
        Http::fake(['*/getSession/' => Http::response($this->sessionBody('S1'))]);

        $result = app(MpesaClient::class)->openSession();

        $this->assertTrue($result->successful());
        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === 'https://openapi.m-pesa.com/sandbox/ipg/v2/vodacomTZN/getSession/'
            && $request->header('Origin') === ['*']
            && $this->decrypt($this->bearer($request)) === 'test-api-key');
    }

    public function test_c2b_payment_sends_the_encrypted_session_and_tanzanian_fields(): void
    {
        Http::fake([
            '*/getSession/' => Http::response($this->sessionBody('S1')),
            '*/c2bPayment/singleStage/' => Http::response([
                'output_ResponseCode' => 'INS-0',
                'output_ResponseDesc' => 'Request processed successfully',
                'output_TransactionID' => '49XCD123F6',
                'output_ConversationID' => 'conv',
                'output_ThirdPartyConversationID' => 'ours',
            ], 201),
        ]);

        $result = app(MpesaClient::class)->c2bPayment('0712 345 678', '150000', 'RH27000123', 'ours', 'Summit registration');

        $this->assertTrue($result->successful());
        $this->assertSame('49XCD123F6', $result->get('output_TransactionID'));
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'c2bPayment/singleStage/')
            && $request->method() === 'POST'
            && $this->decrypt($this->bearer($request)) === 'S1'
            && $request['input_CustomerMSISDN'] === '255712345678'
            && $request['input_Amount'] === '150000'
            && $request['input_Country'] === 'TZN'
            && $request['input_Currency'] === 'TZS'
            && $request['input_ServiceProviderCode'] === '000000'
            && $request['input_TransactionReference'] === 'RH27000123'
            && $request['input_ThirdPartyConversationID'] === 'ours');
    }

    public function test_waits_for_a_new_session_to_become_active(): void
    {
        Http::fake([
            '*/getSession/' => Http::response($this->sessionBody('S1')),
            '*/c2bPayment/singleStage/' => Http::response(['output_ResponseCode' => 'INS-0'], 201),
        ]);

        app(MpesaClient::class)->c2bPayment('000000000001', '10', 'R1', 'C1', 'Test');

        Sleep::assertSleptTimes(1);
    }

    public function test_reuses_the_cached_session(): void
    {
        Http::fake([
            '*/getSession/' => Http::response($this->sessionBody('S1')),
            '*/c2bPayment/singleStage/' => Http::response(['output_ResponseCode' => 'INS-0'], 201),
        ]);

        $mpesa = app(MpesaClient::class);
        $mpesa->c2bPayment('000000000001', '10', 'R1', 'C1', 'Test');
        $mpesa->c2bPayment('000000000001', '10', 'R2', 'C2', 'Test');

        Http::assertSentCount(3);
    }

    public function test_warm_up_opens_a_session_only_when_none_is_ready(): void
    {
        Http::fake(['*/getSession/' => Http::response($this->sessionBody('S1'))]);
        $mpesa = app(MpesaClient::class);

        $mpesa->warmUp();
        $mpesa->warmUp();
        Http::assertSentCount(1);

        // Close to the end of its lifetime: a new one is opened.
        $this->travel(config('mpesa.session_minutes') - 4)->minutes();
        $mpesa->warmUp();
        Http::assertSentCount(2);
    }

    public function test_failed_payments_carry_the_error_code(): void
    {
        Http::fake([
            '*/getSession/' => Http::response($this->sessionBody('S1')),
            '*/c2bPayment/singleStage/' => Http::response(['output_ResponseCode' => 'INS-2006', 'output_ResponseDesc' => 'Insufficient balance'], 422),
        ]);

        $result = app(MpesaClient::class)->c2bPayment('000000000008', '10', 'R1', 'C1', 'Test');

        $this->assertFalse($result->successful());
        $this->assertSame(422, $result->status);
        $this->assertSame('INS-2006', $result->code);
    }

    public function test_a_failed_session_stops_the_payment(): void
    {
        Http::fake(['*/getSession/' => Http::response(['output_ResponseCode' => 'INS-989', 'output_ResponseDesc' => 'Session Creation Failed'], 400)]);

        $this->expectExceptionMessage('INS-989');

        app(MpesaClient::class)->c2bPayment('000000000001', '10', 'R1', 'C1', 'Test');
    }

    public function test_status_query_sends_parameters_in_the_query_string(): void
    {
        Http::fake([
            '*/getSession/' => Http::response($this->sessionBody('S1')),
            '*/queryTransactionStatus/*' => Http::response(['output_ResponseCode' => 'INS-0', 'output_ResponseTransactionStatus' => 'Completed']),
        ]);

        $result = app(MpesaClient::class)->queryTransactionStatus('49XCD123F6', 'ours');

        $this->assertSame('Completed', $result->get('output_ResponseTransactionStatus'));
        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && str_contains($request->url(), 'queryTransactionStatus/?')
            && $request['input_QueryReference'] === '49XCD123F6'
            && $request['input_Country'] === 'TZN');
    }

    public function test_normalizes_tanzanian_numbers(): void
    {
        $this->assertSame('255712345678', MpesaClient::normalizeMsisdn('0712345678'));
        $this->assertSame('255712345678', MpesaClient::normalizeMsisdn('+255 712 345 678'));
        $this->assertSame('255712345678', MpesaClient::normalizeMsisdn('712345678'));
        $this->assertSame('000000000001', MpesaClient::normalizeMsisdn('000000000001'));
    }

    private function sessionBody(string $id): array
    {
        return ['output_ResponseCode' => 'INS-0', 'output_ResponseDesc' => 'Request processed successfully', 'output_SessionID' => $id];
    }

    private function bearer(Request $request): string
    {
        return substr($request->header('Authorization')[0], strlen('Bearer '));
    }

    private function decrypt(string $encrypted): string
    {
        openssl_private_decrypt(base64_decode($encrypted), $plain, $this->privateKey, OPENSSL_PKCS1_PADDING);

        return $plain;
    }
}
