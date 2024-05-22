<?php

declare (strict_types=1);
namespace CartThrob\Dependency\Money\Exchange;

use CartThrob\Dependency\Exchanger\Contract\ExchangeRateProvider;
use CartThrob\Dependency\Exchanger\CurrencyPair as ExchangerCurrencyPair;
use CartThrob\Dependency\Exchanger\Exception\Exception as ExchangerException;
use CartThrob\Dependency\Exchanger\ExchangeRateQuery;
use CartThrob\Dependency\Money\Currency;
use CartThrob\Dependency\Money\CurrencyPair;
use CartThrob\Dependency\Money\Exception\UnresolvableCurrencyPairException;
use CartThrob\Dependency\Money\Exchange;
use function assert;
use function is_numeric;
use function sprintf;
/**
 * Provides a way to get exchange rate from a third-party source and return a currency pair.
 */
final class ExchangerExchange implements Exchange
{
    private ExchangeRateProvider $exchanger;
    public function __construct(ExchangeRateProvider $exchanger)
    {
        $this->exchanger = $exchanger;
    }
    public function quote(Currency $baseCurrency, Currency $counterCurrency) : CurrencyPair
    {
        try {
            $query = new ExchangeRateQuery(new ExchangerCurrencyPair($baseCurrency->getCode(), $counterCurrency->getCode()));
            $rate = $this->exchanger->getExchangeRate($query);
        } catch (ExchangerException) {
            throw UnresolvableCurrencyPairException::createFromCurrencies($baseCurrency, $counterCurrency);
        }
        $rateValue = sprintf('%.14F', $rate->getValue());
        assert(is_numeric($rateValue));
        return new CurrencyPair($baseCurrency, $counterCurrency, $rateValue);
    }
}
