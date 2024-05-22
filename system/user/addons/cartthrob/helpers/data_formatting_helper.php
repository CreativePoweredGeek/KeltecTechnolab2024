<?php

use function CartThrob\Dependency\BenTools\CartesianProduct\cartesian_product;

use CartThrob\Dependency\Omnipay\Common\CreditCard;

if (!function_exists('set')) {
    /**
     * @return mixed
     */
    function set()
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            if ($arg) {
                return $arg;
            }
        }

        return end($args);
    }
}

if (!function_exists('cartesian')) {
    /**
     * @param $data
     * @return array
     */
    function cartesian($data = []): array
    {
        return cartesian_product($data)->asArray();
    }
}

if (!function_exists('cartesian_to_price')) {
    /**
     * @param $input
     * @return array
     */
    function cartesian_to_price($input)
    {
        $prices = [];

        foreach ($input as $key => $value) {
            $prices[$key] = 0;
            foreach ($value as $k => $price) {
                $price = trim($price);
                $price += 0; // cast as number;
                $prices[$key] += trim($price);
            }
        }

        return $prices;
    }
}

if (!function_exists('_array_merge')) {
    /**
     * @param $a
     * @param $b
     * @return mixed
     */
    function _array_merge($a, $b)
    {
        foreach ($b as $key => $value) {
            if (is_array($value) && isset($a[$key])) {
                $a[$key] = @_array_merge($a[$key], $value);
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }
}

if (!function_exists('array_key_prefix')) {
    /**
     * @param array $array
     * @param string $prefix
     * @return array
     */
    function array_key_prefix(array $array, $prefix = '')
    {
        $return = [];

        foreach ($array as $key => $value) {
            $return[$prefix . $key] = $value;
        }

        return $return;
    }
}

if (!function_exists('array_value')) {
    /**
     * Get a value nested in a multi-dimensional array
     *
     * @param $array
     * @return mixed
     */
    function array_value($array)
    {
        if (!is_array($array)) {
            return false;
        }

        $args = func_get_args();

        array_shift($args);

        foreach ($args as $key) {
            if (isset($array[$key])) {
                $array = $array[$key];
            } else {
                return false;
            }
        }

        return $array;
    }
}

if (!function_exists('sanitize_credit_card_number')) {
    /**
     * Strips all non-numeric formatting from a string
     *
     * @param string|int $number
     * @return string
     */
    function sanitize_credit_card_number($number): string
    {
        return (new CreditCard())
            ->setNumber($number)
            ->getNumber();
    }
}

if (!function_exists('param_string_to_array')) {
    /**
     * @param $string
     * @return array
     */
    function param_string_to_array($string)
    {
        $values = [];

        if ($string) {
            foreach (explode('|', $string) as $value) {
                if (strpos($value, ':') !== false) {
                    $value = explode(':', $value);

                    $values[$value[0]] = $value[1];
                } else {
                    $values[$value] = $value;
                }
            }
        }

        return $values;
    }
}

if (!function_exists('_build_array')) {
    /**
     * recursively builds array out of xml
     * set the build type as "complete" and this will build a complete array
     * even in cases where there are multiple child nodes at the same level.
     * The default will only output one child node at a time. For our purposes
     * this is generally fine, most of the xml returned from gateway processes
     * do not contain multiple child nodes at the same level.
     *
     * @param string $xml_data
     * @param string $count
     * @param string $build_type basic / complete
     * @return array
     */
    function _build_array($xml_data, &$count, $build_type = 'basic')
    {
        $child = [];

        if (isset($xml_data[$count]['value'])) {
            array_push($child, $xml_data[$count]['value']);
        }
        if ($count == 0) {
            $name = @$xml_data[0]['tag'];

            if (!empty($xml_data[0]['attributes'])) {
                foreach ($xml_data[0]['attributes'] as $key => $value) {
                    $child[$key] = $value;
                }
            }
        }

        while ($count++ < count($xml_data)) {
            switch ($xml_data[$count]['type']) {
                case 'cdata':
                    @array_push($child, $xml_data[$count]['value']);
                    break;
                case 'complete':
                    $name = $xml_data[$count]['tag'];
                    if (!empty($name)) {
                        if (isset($xml_data[$count]['value'])) {
                            if ($build_type == 'complete') {
                                $child[$name][]['data'] = $xml_data[$count]['value'];
                            } else {
                                $child[$name]['data'] = $xml_data[$count]['value'];
                            }
                        } else {
                            $child[$name] = '';
                        }
                        if (isset($xml_data[$count]['attributes'])) {
                            foreach ($xml_data[$count]['attributes'] as $key => $value) {
                                $curr = count($child[$name]);
                                if ($build_type == 'complete') {
                                    $child[$name][$curr - 1][$key] = $value;
                                } else {
                                    $child[$name][$key] = $value;
                                }
                            }
                        }
                        if (empty($new_count)) {
                            $new_count = 1;
                        } else {
                            $new_count++;
                        }
                    }
                    break;
                case 'open':
                    $name = $xml_data[$count]['tag'];
                    if (isset($child[$name])) {
                        $size = count($child[$name]);
                    } else {
                        $size = 0;
                    }
                    $child[$name][$size] = _build_array($xml_data, $count);
                    break;
                case 'close':
                    return $child;
                    break;
            }
        }

        return $child;
    }
}

if (!function_exists('bool_string')) {
    /**
     * @param $string
     * @param bool $default
     * @return bool
     */
    function bool_string($string, $default = false)
    {
        if (is_null($string)) {
            $string = '';
        }

        switch (strtolower($string)) {
            case 'true':
            case 't':
            case 'yes':
            case 'y':
            case 'on':
            case '1':
                return true;
                break;
            case 'false':
            case 'f':
            case 'no':
            case 'n':
            case 'off':
            case '0':
                return false;
                break;
            default:
                return $default;
        }
    }
}

if (!function_exists('_unserialize')) {
    /**
     * Unserialize data, and always return an array
     *
     * @param mixed $data
     * @param mixed $base64_decode = FALSE
     * @return array
     */
    function _unserialize($data, $base64_decode = false)
    {
        if (is_array($data)) {
            return $data;
        }

        if ($base64_decode && !is_null($data)) {
            $data = base64_decode($data);
        }

        if (is_null($data) || false === ($data = @unserialize($data))) {
            return [];
        }

        return $data;
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML special characters in a string.
     *
     * @param string $value
     * @return string
     */
    function e($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('year2')) {
    /**
     * Return a year in 2-digit format
     *
     * @param $year
     * @return string
     */
    function year2($year)
    {
        if (strlen($year > 2)) {
            return substr($year, -2);
        }

        return str_pad($year, 2, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('year4')) {
    /**
     * Return a year in 4-digit format
     *
     * @param $year
     * @return string
     */
    function year4($year)
    {
        $length = strlen($year);

        switch ($length) {
            case 3:
                return '2' . $year;
            case 2:
                return '20' . $year;
            case 1:
                return '200' . $year;
            case $length > 4:
                return substr($year, -4);
        }

        return $year;
    }
}
