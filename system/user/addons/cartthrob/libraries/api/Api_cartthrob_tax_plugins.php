<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Api_cartthrob_tax_plugins extends Api_cartthrob_plugins
{
    public function __construct()
    {
        $this->set_plugin(ee()->cartthrob->store->config('tax_plugin'));
    }

    public function html()
    {
        return ($this->plugin()) ? $this->plugin()->html : '';
    }

    public function overview()
    {
        return ($this->plugin()) ? $this->plugin()->overview : '';
    }

    public function tax_name()
    {
        if ($this->plugin() && method_exists($this->plugin(), 'tax_name')) {
            return $this->plugin()->tax_name();
        }

        return '';
    }

    public function tax_rate()
    {
        if ($this->plugin() && method_exists($this->plugin(), 'tax_rate')) {
            return $this->plugin()->tax_rate();
        }

        return '';
    }
}
