<?php

declare (strict_types=1);
namespace CartThrob\Dependency\Money\Exception;

use DomainException;
use CartThrob\Dependency\Money\Exception;
/**
 * Thrown when trying to get ISO currency that does not exists.
 */
final class UnknownCurrencyException extends DomainException implements Exception
{
}
