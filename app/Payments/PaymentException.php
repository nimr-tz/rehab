<?php

namespace App\Payments;

use RuntimeException;

/**
 * A payment action that cannot proceed; the message is safe to show the user.
 */
class PaymentException extends RuntimeException {}
