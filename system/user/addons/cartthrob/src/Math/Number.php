<?php

namespace CartThrob\Math;

use InvalidArgumentException;

class Number
{
    /**
     * @param $value
     * @param string $decimalPoint
     * @return int|float
     */
    public static function sanitize($value, $decimalPoint = '.')
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        } elseif (empty($value)) {
            return 0;
        }

        $originalValue = $value;

        if ($decimalPoint === '.') {
            $decimalPoint = '\\.';
        }

        $regex = '/[^\d\-' . $decimalPoint . ']/';
        $value = preg_replace($regex, '', $value);

        // Standardize decimal point to a period
        $value = str_replace($decimalPoint, '.', $value);

        if (!is_numeric($value)) {
            throw new InvalidArgumentException('The provided value [Type: ' . gettype($originalValue) . ' - Value: ' . $originalValue . '"] is not numeric.');
        }

        $intVal = @intval($value);
        $floatVal = @floatval($value);

        return $value === (string)$intVal ? $intVal : $floatVal;
    }
}
