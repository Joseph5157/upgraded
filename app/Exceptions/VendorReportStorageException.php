<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class VendorReportStorageException extends RuntimeException
{
    public static function fromThrowable(Throwable $throwable, string $message): self
    {
        return new self($message, 0, $throwable);
    }
}
