<?php

if (!function_exists('tag_param')) {
    /**
     * Get an ExpressionEngine tag parameter index
     *
     * @param $index
     * @param null $default
     * @return string|null
     */
    function tag_param($index, $default = null)
    {
        if (!isset(ee()->TMPL->tagparts[$index])) {
            return $default;
        }

        return ee()->TMPL->tagparts[$index];
    }
}

if (!function_exists('tag_param_equals')) {
    /**
     * Check if an ExpressionEngine tag parameter equals a provided value
     *
     * @param $index
     * @param $value
     * @param bool $strict
     * @return bool
     */
    function tag_param_equals($index, $value, $strict = false)
    {
        if ($strict) {
            return tag_param($index) === $value;
        }

        return tag_param($index) == $value;
    }
}
