<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Api_cartthrob_price_plugins extends Api
{
    public $default_plugin;
    public $plugins = [];
    public $paths = [];
    public $current_plugin = false;

    public function __construct()
    {
        parent::__construct();

        $this->paths[] = CARTTHROB_PRICE_PLUGIN_PATH;
    }

    public function add_path($path)
    {
        if (!is_dir($path)) {
            return false;
        }

        if (!in_array($path, $this->paths)) {
            $this->paths[] = $path;
        }

        return true;
    }

    public function set_current_plugin($classname)
    {
        if (!isset($this->plugins[$classname])) {
            return false;
        }

        $this->current_plugin = $classname;

        return true;
    }

    public function &default_plugin()
    {
        return $this->default_plugin;
    }

    public function default_global_settings($key = false)
    {
        if (!$this->default_plugin) {
            return false;
        }

        $global_settings = $this->default_plugin->global_settings();

        if ($key !== false) {
            return $global_settings;
        }

        return (isset($global_settings[$key])) ? $global_settings[$key] : false;
    }

    public function &current_plugin()
    {
        if (!$this->current_plugin) {
            return false;
        }

        return $this->plugin($this->current_plugin);
    }

    public function &plugin($classname)
    {
        foreach ($this->plugins as $plugin) {
            if ($plugin->classname == $classname) {
                return $plugin;
            }
        }

        return false;
    }

    public function load_plugins($classes = false, $add = false)
    {
        if (!$add) {
            $this->plugins = [];
        }

        if ($classes !== false) {
            if (!is_array($classes)) {
                $classes = [$classes];
            }

            foreach ($classes as $key => $value) {
                if (!preg_match('/^Cartthrob_/')) {
                    $classes[$key] = 'Cartthrob_' . $value;
                }
            }
        }

        ee()->load->helper('file');

        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            foreach (get_filenames($path, true) as $file) {
                if (!preg_match('/^cartthrob\.(.*)/', basename($file, '.php'), $match)) {
                    continue;
                } else {
                    $type = $match[1];

                    $classname = 'Cartthrob_' . $type;
                }

                if ($classes !== false && !in_array($classname, $classes)) {
                    continue;
                }

                require_once $file;

                if (!class_exists($classname)) {
                    continue;
                }

                $plugin = new $classname();

                $this->plugins[$type] = $plugin;
            }
        }
    }

    public function &plugins()
    {
        return $this->plugins;
    }

    /**
     * @param $classname
     * @param $data
     * @return bool
     */
    public function set_plugin_data($classname, $data): bool
    {
        if (!isset($this->plugins[$classname])) {
            return false;
        }

        $this->plugins[$classname]->data = $data;

        return true;
    }
}
