<?php

namespace CartThrob\Tags;

use CartThrob\Math\Math;
use EE_Session;

class ArithmeticTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('number');
    }

    public function process()
    {
        $math = new Math();
        $format = $this->param('format');
        $debug = $this->param('debug');
        $showErrors = $this->param('show_errors', true);

        if ($this->param('expression') === false) {
            $result = $math->arithmetic($this->param('num1'), $this->param('num2'), $this->param('operator'));
        } elseif ($debug) {
            return $this->param('expression');
        } else {
            $result = $this->param('expression') ? $math->evaluate($this->param('expression')) : 0;
        }

        if ($result === false && $showErrors) {
            return $math->last_error;
        }

        return $format ? ee()->number->format($result) : $result;
    }
}
