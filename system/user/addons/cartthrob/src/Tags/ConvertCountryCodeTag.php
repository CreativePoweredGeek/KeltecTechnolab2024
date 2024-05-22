<?php

namespace CartThrob\Tags;

use EE_Session;

class ConvertCountryCodeTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('locales');
    }

    public function process()
    {
        $country_code = $this->param('country_code');
        $code = ee()->locales->alpha3_country_code($country_code);
        $countries = ee()->locales->all_countries();

        return $countries[$code] ?? $country_code;
    }
}
