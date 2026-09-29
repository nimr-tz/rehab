<?php

namespace App\Payments;

use App\Models\PaymentTransaction;

/**
 * Read-only view of config/payments.php: which methods, bank accounts and
 * mobile money operators are actually usable for a given currency.
 */
class PaymentOptions
{
    public function bankAccount(string $currency): ?array
    {
        if (! config('payments.methods.bank_transfer.enabled')) {
            return null;
        }

        $account = config("payments.methods.bank_transfer.accounts.{$currency}");

        return ! empty($account['account_number']) ? $account : null;
    }

    /**
     * @return array<string, array{label:string, pay_number:string, account_name:?string}>
     */
    public function mobileMoneyProviders(string $currency): array
    {
        if (! config('payments.methods.mobile_money.enabled')) {
            return [];
        }

        if (! in_array($currency, config('payments.methods.mobile_money.currencies', []), true)) {
            return [];
        }

        return collect(config('payments.methods.mobile_money.providers', []))
            ->filter(fn ($provider) => ! empty($provider['pay_number']))
            ->all();
    }

    /**
     * Methods a payer can use for the given currency, keyed by method.
     *
     * @return array<string, string>
     */
    public function methodsFor(string $currency): array
    {
        $methods = [];

        if ($this->bankAccount($currency)) {
            $methods[PaymentTransaction::METHOD_BANK_TRANSFER] = config('payments.methods.bank_transfer.label');
        }

        if ($this->mobileMoneyProviders($currency)) {
            $methods[PaymentTransaction::METHOD_MOBILE_MONEY] = config('payments.methods.mobile_money.label');
        }

        return $methods;
    }

    public function requiresProof(string $method): bool
    {
        return (bool) config("payments.methods.{$method}.requires_proof", false);
    }

    public function fee(?string $category): ?array
    {
        $fee = config("payments.registration_fees.{$category}");

        return $fee ?: null;
    }
}
