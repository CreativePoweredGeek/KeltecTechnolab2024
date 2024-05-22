<?php

namespace CartThrob\Tags;

use CartThrob\GeneratesFormElementAttributes;
use EE_Session;

class StateSelectTag extends Tag
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
        $name = $this->param('name', 'state');
        $abbrev_label = $this->param('abbrev_label');
        $abbrev_value = $this->param('abbrev_value', true);
        $states = ee()->locales->states($this->param('country_code'));
        $states_converted = [];
        $selected = $this->param('selected', $this->param('default'));

        if ($this->param('add_blank')) {
            $blank = ['' => '---'];
            $states = $blank + $states;
        }

        foreach ($states as $abbrev => $state) {
            $value = ($abbrev_value) ? $abbrev : $state;
            $states_converted[$value] = ($abbrev_label) ? $abbrev : $state;
        }

        $attrs = $this->generateFormAttrs(
            $this->param('id', null),
            $this->param('class', null),
            $this->param('onchange', null),
            $this->param('extra', null)
        );

        return form_dropdown($name, $states_converted, $selected, $attrs);
    }
}
