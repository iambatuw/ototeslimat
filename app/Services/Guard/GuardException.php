<?php

namespace App\Services\Guard;

use RuntimeException;
use Throwable;

/** Müşteriye gösterilebilir mesaj taşıyan hata; teknik ayrıntı yalnızca kayıtlara gider. */
class GuardException extends RuntimeException
{
    public function __construct(string $message, public readonly string $reason, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
