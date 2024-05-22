<?php

namespace CartThrob\Tags;

use EE_Session;

class ViewCountryNameTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('locales');
    }

    public function process()
    {
        $countries = ee()->locales->all_countries();

        return isset($countries[$this->param('country_code')]) ? $countries[$this->param('country_code')] : '';
    }
}
