<?php

namespace CartThrob\Math;

use InvalidArgumentException;

class Round
{
    public const ROUND_STD = 'round_standard';
    public const ROUND_UP = 'round_up';
    public const ROUND_DOWN = 'round_down';
    public const ROUND_NEAREST = 'round_nearest';

    /**
     * Round a number based on the rules
     *
     * @param mixed $value
     * @param string $type
     * @param int $precision
     * @param array $options
     * @return float
     */
    public static function calc($value, $type = self::ROUND_STD, int $precision = 2, array $options = []): float
    {
        switch ($type) {
            case self::ROUND_UP:
                return static::roundUp($value, $precision);
            case self::ROUND_DOWN:
                return static::roundDown($value, $precision);
            case self::ROUND_NEAREST:
                return static::roundNearest($value, $precision, $options);
            case self::ROUND_STD:
            default:
                return static::roundNearest($value, $precision, $options = ['rounding' => 0.001]);
        }
    }

    /**
     * @param $value
     * @param int $precision
     * @return float
     */
    public static function roundUp($value, int $precision): float
    {
        $value = Number::sanitize($value);
        $sign = $value < 0 ? -1 : 1;
        $multiplier = pow(10, abs($precision));

        if ($precision < 0) {
            $multiplier = 1 / $multiplier;
        }

        $roundedValue = ceil(abs($value) * $multiplier) / $multiplier;

        return $sign * $roundedValue;
    }

    /**
     * @param $value
     * @param int $precision
     * @return float
     */
    public static function roundDown($value, int $precision): float
    {
        $value = Number::sanitize($value);
        $sign = $value < 0 ? -1 : 1;
        $multiplier = pow(10, abs($precision));

        if ($precision < 0) {
            $multiplier = 1 / $multiplier;
        }

        $roundedValue = floor(abs($value) * $multiplier) / $multiplier;

        return $sign * $roundedValue;
    }

    /**
     * @param $value
     * @param int $precision
     * @param array $options
     * @return float
     */
    public static function roundNearest($value, int $precision, $options): float
    {
        if (!isset($options['rounding'])) {
            throw new InvalidArgumentException('You must provide a `rounding` option when rounding to the nearest value.');
        } elseif ($options['rounding'] <= 0) {
            throw new InvalidArgumentException('Your must provide a `rounding` option greater than zero.');
        }

        $multiplier = pow(10, abs($precision));

        if ($precision < 0) {
            $multiplier = 1 / $multiplier;
        }

        $rounding = $options['rounding'];
        $divisor = 1 / $rounding;
        $value = Number::sanitize($value);
        $value = round($value * $multiplier) / $multiplier;

        return round($value * $divisor) / $divisor;
    }
}
