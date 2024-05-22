<?php

namespace CartThrob\Tags;

class CustomerInfoTag extends Tag
{
    public function process()
    {
        return $this->parseVariablesRow($this->globalVariables());
    }
}
