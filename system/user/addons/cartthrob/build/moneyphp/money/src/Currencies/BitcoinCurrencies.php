<?php

declare (strict_types=1);
namespace CartThrob\Dependency\Money\Currencies;

use ArrayIterator;
use CartThrob\Dependency\Money\Currencies;
use CartThrob\Dependency\Money\Currency;
use CartThrob\Dependency\Money\Exception\UnknownCurrencyException;
use Traversable;
final class BitcoinCurrencies implements Currencies
{
    public const CODE = 'XBT';
    public const SYMBOL = "Ƀ";
    public function contains(Currency $currency) : bool
    {
        return $currency->getCode() === self::CODE;
    }
    public function subunitFor(Currency $currency) : int
    {
        if ($currency->getCode() !== self::CODE) {
            throw new UnknownCurrencyException($currency->getCode() . ' is not bitcoin and is not supported by this currency repository');
        }
        return 8;
    }
    /** {@inheritDoc} */
    public function getIterator() : Traversable
    {
        return new ArrayIterator([new Currency(self::CODE)]);
    }
}
