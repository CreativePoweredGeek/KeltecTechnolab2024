<?php

namespace CartThrob\Tags;

use EE_Session;

class ViewFormattedNumberTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('number');
    }

    /**
     * Formats a number
     */
    public function process()
    {
        return ee()->number->format($this->param('number'));
    }
}
