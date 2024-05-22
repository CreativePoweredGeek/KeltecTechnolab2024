<?php

namespace CartThrob\Plugins;

use CartThrob\Traits\ValidateTrait;
use Cartthrob_child;
use Exception;
use ExpressionEngine\Service\Validation\ValidationAware;
use ReflectionClass;

abstract class Plugin extends Cartthrob_child implements ValidationAware
{
    use ValidateTrait;

    protected $core;

    public $title;
    public $short_title;
    public $overview;
    public $note;
    public $settings = [];

    public function initialize($params = [], $defaults = [])
    {
    }

    /**
     * @param $key
     * @param $default
     * @return mixed
     */
    public function settings($key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * @param $core
     * @return $this
     */
    public function set_core($core)
    {
        if (is_object($core)) {
            $this->core = $core;
        }

        return $this;
    }

    /**
     * Register the plugin with CartThrob
     */
    public function register(): void
    {
        try {
            $path = (new ReflectionClass($this))->getFileName();
            $baseName = str_replace(['_ext', '_ft', '_mcp', '_upd'], '', get_class($this));
            $idiom = set(ee()->session->userdata('language'), ee()->input->cookie('language'), ee()->config->item('deft_lang'), 'english');

            ee()->lang->load(
                $langfile = str_replace('\\', DIRECTORY_SEPARATOR, strtolower($baseName)),
                $idiom,
                $return = false,
                $add_suffix = true,
                $alt_path = dirname($path) . '/',
                $show_errors = false
            );
        } catch (Exception $e) {
            // NOOP
        }

        ee('cartthrob:PluginService')->register($this);
    }

    /**
     * @param $key
     * @param bool $default
     * @return array|bool|mixed
     */
    public function getSetting($key, $default = false)
    {
        $settings = $this->core->store->config(get_class($this) . '_settings');

        if ($key === false) {
            return ($settings) ? $settings : $default;
        }

        return (isset($settings[$key])) ? $settings[$key] : $default;
    }
}
