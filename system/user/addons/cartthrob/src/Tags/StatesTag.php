<?php

namespace CartThrob\Tags;

use EE_Session;

class StatesTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('locales');
    }

    /**
     * Swaps abbrev, and state from list in templates
     */
    public function process()
    {
        $data = [];
        $country_code = $this->param('country_code');

        foreach (ee()->locales->states($country_code) as $abbrev => $state) {
            $data[] = compact('abbrev', 'state');
        }

        return $this->parseVariables($data);
    }
}
