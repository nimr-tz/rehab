<?php

namespace App\Console\Commands;

use App\Services\Mpesa\MpesaClient;
use App\Services\Mpesa\MpesaResult;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Str;

/**
 * Runs one M-Pesa round trip against the sandbox: open a session, push a C2B
 * payment, then query its status. Nothing is written to the database.
 */
#[Signature('mpesa:sandbox
    {msisdn=000000000001 : Customer number (sandbox test number or a registered test phone)}
    {--amount=1000 : Amount in TZS}
    {--query-method=GET : HTTP method for the status query (GET or POST)}')]
#[Description('Try an M-Pesa payment round trip against the sandbox')]
class MpesaSandbox extends Command
{
    public function handle(MpesaClient $mpesa): int
    {
        if (app()->isProduction() || config('mpesa.environment') !== 'sandbox') {
            $this->error('This command only runs against the M-Pesa sandbox, outside production.');

            return self::FAILURE;
        }

        if (blank(config('mpesa.api_key')) || blank(config('mpesa.public_key'))) {
            $this->error('Set MPESA_API_KEY and MPESA_PUBLIC_KEY in .env first.');

            return self::FAILURE;
        }

        try {
            $this->components->task('Opening a session', fn () => $this->show($mpesa->openSession()));

            $this->components->info('Waiting '.config('mpesa.session_warmup_seconds').' seconds for the session to become active...');

            $conversationId = Str::uuid()->getHex()->toString();
            $reference = 'RHTEST'.now()->format('His');

            $payment = null;
            $this->components->task("Pushing TZS {$this->option('amount')} to {$this->argument('msisdn')}", function () use ($mpesa, $conversationId, $reference, &$payment) {
                $payment = $mpesa->c2bPayment($this->argument('msisdn'), $this->option('amount'), $reference, $conversationId, 'Summit registration test');

                return $this->show($payment);
            });

            $method = strtoupper($this->option('query-method'));
            $this->components->task("Querying the status ({$method})", fn () => $this->show(
                $mpesa->queryTransactionStatus($payment->get('output_TransactionID') ?? $conversationId, $conversationId, $method),
            ));
        } catch (ConnectionException $e) {
            $this->error('Could not reach M-Pesa: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function show(MpesaResult $result): bool
    {
        $this->newLine();
        $this->line("  HTTP {$result->status}");
        $this->line('  '.json_encode($result->body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $result->successful();
    }
}
