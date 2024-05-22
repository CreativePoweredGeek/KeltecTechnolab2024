<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Locales
{
    /**
     * Locales constructor.
     */
    public function __construct()
    {
        if (file_exists(PATH_THIRD . 'cartthrob/config/my_locales.php')) {
            ee()->config->load('my_locales');
        } else {
            ee()->config->load('locales');
        }
    }

    /**
     * @param bool $country
     * @return array
     */
    public function states($country = false)
    {
        $states = [];

        if (!$country) {
            $country = ee()->config->item('default_state_country');
        }

        $all_states = ee()->config->item('states');

        if ($country && $all_states) {
            if (!is_array($country)) {
                $country = explode('|', $country);
            }

            foreach ($country as $key) {
                if (isset($all_states[$key])) {
                    $states = $states + $all_states[$key];
                }
            }
        }

        return $states;
    }

    /**
     * @param $country
     * @param bool $alpha2
     * @return bool|mixed
     */
    public function country_code($country, $alpha2 = false)
    {
        $countries = $this->all_countries($alpha2);

        if (!$key = array_search($country, $countries)) {
            return false;
        }

        return $countries[$key];
    }

    /**
     * @param bool $alpha2
     * @param bool $country_codes
     * @return array
     */
    public function all_countries($alpha2 = false, $country_codes = true)
    {
        return $this->countries($alpha2, $country_codes, true);
    }

    /**
     * @param bool $alpha2
     * @param bool $countryCodes
     * @param bool $all
     * @return array
     */
    public function countries($alpha2 = false, $countryCodes = true, $all = false)
    {
        $countries = [];
        $alpha2CountryCodes = [];
        $localesCountries = ee()->config->item('cartthrob:locales_countries');

        if ($alpha2 && $countryCodes) {
            $alpha2CountryCodes = ee()->config->item('country_codes');
        }

        foreach (ee()->config->item('countries') as $countryCode => $country) {
            if ($all || !$localesCountries || in_array($countryCode, $localesCountries)) {
                if (!$countryCodes) {
                    $key = $country;
                } elseif ($alpha2) {
                    $key = is_array($alpha2CountryCodes[$countryCode]) ? current($alpha2CountryCodes[$countryCode]) : $alpha2CountryCodes[$countryCode];
                } else {
                    $key = $countryCode;
                }

                $countries[$key] = $country;
            }
        }

        return $countries;
    }

    /**
     * Get the alpha2 representation of an alpha3 country code
     *
     * @param string $code
     * @return string
     */
    public function alpha2_country_code($code)
    {
        if (strlen($code) === 2) {
            return $code;
        }

        $countryCodes = $this->country_codes();

        if (!isset($countryCodes[$code])) {
            return $code;
        }

        return is_array($countryCodes[$code]) ? current($countryCodes[$code]) : $countryCodes[$code];
    }

    /**
     * @return mixed
     */
    public function country_codes()
    {
        return ee()->config->item('country_codes');
    }

    /**
     * @param $code
     * @return bool|int|mixed|string
     */
    public function country_from_country_code($code)
    {
        $code = $this->alpha3_country_code($code);
        $countries = $this->all_countries();

        return (isset($countries[$code])) ? $countries[$code] : $code;
    }

    /**
     * @param $code
     * @return bool|int|string
     */
    public function alpha3_country_code($code)
    {
        if (!$code) {
            return $code;
        }

        if (strlen($code) === 3) {
            return $code;
        }

        $code = strtoupper($code);

        $key = false;

        foreach ($this->country_codes() as $alpha3 => $alpha2) {
            if (is_array($alpha2)) {
                if (in_array($code, $alpha2)) {
                    $key = $alpha3;

                    break;
                }
            } else {
                if ($code === $alpha2) {
                    $key = $alpha3;

                    break;
                }
            }
        }

        return $key ? $key : $code;
    }

    /**
     * @param $code
     * @return null
     */
    public function iso_currency_code($code)
    {
        $codes = $this->currency_codes();
        if (array_key_exists($code, $codes)) {
            return $codes[$code][1];
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function currency_codes()
    {
        return ee()->config->item('currency_codes');
    }
}
