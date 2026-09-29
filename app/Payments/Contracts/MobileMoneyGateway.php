<?php

namespace App\Payments\Contracts;

use App\Models\PaymentTransaction;
use Illuminate\Http\Request;

/**
 * Automated mobile money integration (push-to-pay + asynchronous callback).
 *
 * A driver is registered in config('payments.mobile_money.gateways') and
 * selected with PAYMENT_MOBILE_MONEY_GATEWAY. Until one is configured,
 * mobile money is confirmed manually through PaymentService::submit().
 */
interface MobileMoneyGateway
{
    /**
     * Ask the operator to prompt the payer's phone. The transaction is created
     * with status "pending"; store any provider IDs in gateway_payload.
     */
    public function initiate(PaymentTransaction $transaction, string $phone): void;

    /**
     * Authenticate and parse a provider callback. Must verify the provider's
     * signature/credentials and return the matching transaction with its
     * status updated, or null if the request is not genuine.
     */
    public function handleCallback(Request $request): ?PaymentTransaction;
}
