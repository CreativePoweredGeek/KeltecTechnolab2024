<?php

if (!function_exists('alpha2_country_code')) {
    /**
     * Get the alpha2 country code for
     * @param $code
     * @return string
     */
    function alpha2_country_code($code)
    {
        ee()->load->library('locales');

        return ee()->locales->alpha2_country_code($code);
    }
}

if (!function_exists('alpha3_country_code')) {
    /**
     * Get the alpha3 country code for
     * @param $code
     * @return string
     */
    function alpha3_country_code($code)
    {
        ee()->load->library('locales');

        return ee()->locales->alpha3_country_code($code);
    }
}
