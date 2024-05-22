<?php

declare (strict_types=1);
namespace CartThrob\Dependency\Money\Exception;

use InvalidArgumentException as CoreInvalidArgumentException;
use CartThrob\Dependency\Money\Exception;
final class InvalidArgumentException extends CoreInvalidArgumentException implements Exception
{
    /** @psalm-pure */
    public static function divisionByZero() : self
    {
        return new self('Cannot compute division with a zero divisor');
    }
    /** @psalm-pure */
    public static function moduloByZero() : self
    {
        return new self('Cannot compute modulo with a zero divisor');
    }
}
