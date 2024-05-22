<?php

namespace CartThrob\Actions;

use CartThrob\HasVariables;
use CartThrob\Request\Request;
use EE_Session;

abstract class Action
{
    use HasVariables;

    /** @var EE_Session */
    protected $session;
    /** @var Request */
    protected $request;

    public function __construct(EE_Session $session, Request $request)
    {
        $this->session = $session;
        $this->request = $request;

        ee()->load->library(['form_builder']);
    }

    abstract public function process();
}
