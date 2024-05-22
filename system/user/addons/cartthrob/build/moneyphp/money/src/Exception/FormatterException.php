<?php

declare (strict_types=1);
namespace CartThrob\Dependency\Money\Exception;

use CartThrob\Dependency\Money\Exception;
use RuntimeException;
/**
 * Thrown when a Money object cannot be formatted into a string.
 */
final class FormatterException extends RuntimeException implements Exception
{
}
