<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

abstract class Api_cartthrob_plugins
{
    protected $paths = [];
    protected $plugins = [];
    protected $plugin;

    public function set_plugin($plugin)
    {
        if ($plugin) {
            $this->plugin = $plugin;

            if (!isset($this->plugins[$this->plugin])) {
                $this->plugins[$this->plugin] = ee()->cartthrob->create_child(ee()->cartthrob, $this->plugin);
            }
        }

        return $this;
    }

    public function plugin()
    {
        if (!$this->plugin || !isset($this->plugins[$this->plugin])) {
            return false;
        }

        return $this->plugins[$this->plugin];
    }
}
