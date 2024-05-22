<?php

namespace CartThrob\Services;

use CartThrob\Math\Number;

class NumberService
{
    /** @var array */
    private array $config;

    /**
     * NumberService constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param $value
     * @param null $decimalPoint
     * @return float|int
     */
    public function sanitize($value, $decimalPoint = null)
    {
        return Number::sanitize(
            $value,
            $decimalPoint ?? $this->config['number_format_defaults_dec_point']
        );
    }
}
