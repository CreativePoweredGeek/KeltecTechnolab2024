<?php

namespace CartThrob\Tags;

use CartThrob\GeneratesFormElementAttributes;
use EE_Session;

class CountrySelectTag extends Tag
{
    use GeneratesFormElementAttributes;

    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('locales');
        ee()->load->helper('form');
    }

    public function process()
    {
        $name = $this->param('name', 'country');
        $selected = $this->param('selected', $this->param('default'));

        $countries = ee()->locales->countries(
            $this->param('alpha2'),
            $this->param('country_codes', true)
        );

        ($this->param('orderby', 'name') === 'name') ? asort($countries, SORT_STRING) : ksort($countries, SORT_STRING);

        if ($this->param('add_blank')) {
            $blank = ['' => '---'];
            $countries = $blank + $countries;
        }

        $attrs = $this->generateFormAttrs(
            $this->param('id', null),
            $this->param('class', null),
            $this->param('onchange', null),
            $this->param('extra', null)
        );

        return form_dropdown($name, $countries, $selected, $attrs);
    }
}
