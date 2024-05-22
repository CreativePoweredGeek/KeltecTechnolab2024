<?php

namespace CartThrob\Services;

use CartThrob\Dependency\Money\Currency;
use CartThrob\Dependency\Money\Money;
use CartThrob\Math\Round;

class MoneyService
{
    /** @var int */
    private int $precision;

    /** @var Currency */
    private Currency $currency;

    /** @var array */
    private array $config;

    /**
     * MoneyService constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->precision = (int)$this->config['number_format_defaults_decimals'];
        $this->currency = new Currency($this->config['number_format_defaults_currency_code']);
    }

    /**
     * Get a zeroed Money object
     *
     * @return Money
     */
    public function fresh(): Money
    {
        return new Money(0, $this->currency);
    }

    /**
     * Convert a int/float/string to a Money object
     *
     * @param mixed $value
     * @return Money
     */
    public function toMoney($value): Money
    {
        $amount = strval($this->round($value) * pow(10, $this->precision));

        return new Money($amount, $this->currency);
    }

    /**
     * Convert a Money object to a float
     *
     * @param Money $money
     * @return float
     */
    public function toFloat(Money $money): float
    {
        return $money->getAmount() / pow(10, $this->precision);
    }

    /**
     * Round a number based on the CartThrob settings
     *
     * @param $value
     * @return float
     */
    public function round($value): float
    {
        $roundingType = $this->config['rounding_default'];
        $options = [];

        if ($roundingType == Round::ROUND_NEAREST) {
            $nearestValue = (float)$this->config['rounding_nearest_value'];
            $options = ['rounding' => $nearestValue];
        }

        return Round::calc($value, $roundingType, $this->precision, $options);
    }
}
