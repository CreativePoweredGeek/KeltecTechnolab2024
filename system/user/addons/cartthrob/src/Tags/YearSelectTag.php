<?php

namespace CartThrob\Tags;

use CartThrob\GeneratesFormElementAttributes;
use EE_Session;

class YearSelectTag extends Tag
{
    use GeneratesFormElementAttributes;

    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->helper('form');
    }

    public function process()
    {
        $selected = null;
        $name = $this->param('name', 'expiration_year');
        $selected = $this->param('selected', date('Y'));
        $years = is_numeric($this->param('years')) ? $this->param('years') : 5;
        $start_year = is_numeric($this->param('start_year')) ? $this->param('start_year') : date('Y');
        $final_year = $start_year + $years;
        $data = [];

        for ($year = $start_year; $year < $final_year; $year++) {
            $data[$year] = $year;
        }

        $attrs = $this->generateFormAttrs(
            $this->param('id', null),
            $this->param('class', null),
            $this->param('onchange', null),
            $this->param('extra', null)
        );

        return form_dropdown($name, $data, $selected, $attrs);
    }
}
