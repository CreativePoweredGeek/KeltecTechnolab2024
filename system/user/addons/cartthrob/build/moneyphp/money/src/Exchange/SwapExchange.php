<?php

declare (strict_types=1);
namespace CartThrob\Dependency\Money\Exchange;

use CartThrob\Dependency\Exchanger\Exception\Exception as ExchangerException;
use CartThrob\Dependency\Money\Currency;
use CartThrob\Dependency\Money\CurrencyPair;
use CartThrob\Dependency\Money\Exception\UnresolvableCurrencyPairException;
use CartThrob\Dependency\Money\Exchange;
use CartThrob\Dependency\Swap\Swap;
/**
 * Provides a way to get exchange rate from a third-party source and return a currency pair.
 */
final class SwapExchange implements Exchange
{
    private Swap $swap;
    public function __construct(Swap $swap)
    {
        $this->swap = $swap;
    }
    public function quote(Currency $baseCurrency, Currency $counterCurrency) : CurrencyPair
    {
        try {
            $rate = $this->swap->latest($baseCurrency->getCode() . '/' . $counterCurrency->getCode());
        } catch (ExchangerException) {
            throw UnresolvableCurrencyPairException::createFromCurrencies($baseCurrency, $counterCurrency);
        }
        return new CurrencyPair($baseCurrency, $counterCurrency, (string) $rate->getValue());
    }
}
