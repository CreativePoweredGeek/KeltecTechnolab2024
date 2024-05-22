<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Api_cartthrob_discount_plugins extends Api_cartthrob_plugins
{
    public function get_plugins()
    {
        ee()->load->helper(['data_formatting', 'file']);

        $plugins = [];

        $paths[] = CARTTHROB_DISCOUNT_PLUGIN_PATH;

        $language = set(
            ee()->session->userdata('language'),
            ee()->input->cookie('language'),
            ee()->config->item('deft_lang'),
            'english'
        );

        foreach ($paths as $i => $path) {
            if (!is_dir($path)) {
                continue;
            }

            foreach (get_filenames($path, true) as $file) {
                $class = basename($file, '.php');

                if (strpos($class, 'Cartthrob_discount_') !== 0 || strpos($class, '~') !== false) {
                    continue;
                }

                // exclude the first path, which is the base cartthrob plugin path
                if ($i !== 0) {
                    if ($language !== 'english' && file_exists($path . '../language/' . $language . '/' . $class . '_lang.php')) {
                        ee()->lang->load(strtolower($class), $language, false, true, $path . '../', false);
                    } else {
                        if (file_exists($path . '../language/english/' . $class . '_lang.php')) {
                            ee()->lang->load(strtolower($class), 'english', false, true, $path . '../', false);
                        }
                    }
                }

                $plugin = ee()->cartthrob->create_child(ee()->cartthrob, ee()->cartthrob->get_class($class));

                $plugins[$class] = get_object_vars($plugin);
            }
        }

        foreach (ee('cartthrob:PluginService')->{'get' . ucfirst('discount')}() as $plugin) {
            $className = get_class($plugin);
            $data = get_class_vars($className);
            $plugin = ee()->cartthrob->create_child(ee()->cartthrob, ee()->cartthrob->get_class($className));

            $plugins[$className] = get_object_vars($plugin);
            $plugins[$className]['type'] = $className;
        }

        return $plugins;
    }

    public function set_plugin_settings($plugin_settings)
    {
        if ($this->plugin) {
            $this->plugin->plugin_settings = $plugin_settings;
        }

        return $this;
    }

    public function global_settings($key = false)
    {
        if ($key === false) {
            return Cartthrob_discount::$global_settings;
        }

        return (isset(Cartthrob_discount::$global_settings[$key])) ? Cartthrob_discount::$global_settings[$key] : false;
    }
}
