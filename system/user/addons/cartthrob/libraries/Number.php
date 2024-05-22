<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use CartThrob\Math\Number as NumberClass;

// requires EE and CartThrob
class Number
{
    public $decimals = 0;
    public $dec_point = '.';
    public $thousands_sep = ',';
    public $allow_negative = true;
    public $prefix = '';
    public $format = true;
    public $prefix_position = 'BEFORE';

    /**
     * Number constructor.
     */
    public function __construct()
    {
        ee()->load->model('cartthrob_settings_model');

        $this->reset();
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->decimals = ee()->config->item('cartthrob:number_format_defaults_decimals');

        $this->dec_point = ee()->config->item('cartthrob:number_format_defaults_dec_point');

        $this->thousands_sep = ee()->config->item('cartthrob:number_format_defaults_thousands_sep');

        $this->prefix = ee()->config->item('cartthrob:number_format_defaults_prefix');

        $this->prefix_position = (ee()->config->item('cartthrob:number_format_defaults_prefix_position') ? ee()->config->item('cartthrob:number_format_defaults_prefix_position') : 'BEFORE');

        $this->allow_negative = true;

        return $this;
    }

    /**
     * @param bool $allow_negative
     * @return $this
     */
    public function set_allow_negative($allow_negative = true)
    {
        $this->allow_negative = $allow_negative;

        return $this;
    }

    /**
     * @param bool $format
     * @return $this
     */
    public function set_format($format = true)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @param string $position
     * @return $this
     */
    public function set_prefix_position($position = 'AFTER')
    {
        $this->prefix_position = $position;

        return $this;
    }

    /**
     * @param $dec_point
     * @return $this
     */
    public function set_dec_point($dec_point)
    {
        $this->dec_point = $dec_point;

        return $this;
    }

    /**
     * @param $decimals
     * @return $this
     */
    public function set_decimals($decimals)
    {
        $this->decimals = $decimals;

        return $this;
    }

    /**
     * @param $thousands_sep
     * @return $this
     */
    public function set_thousands_sep($thousands_sep)
    {
        $this->thousands_sep = $thousands_sep;

        return $this;
    }

    /**
     * @param $prefix
     * @return $this
     */
    public function set_prefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Formats a number
     *
     * @param int ee()->TMPL->fetch_param('number')
     * @param int ee()->TMPL->fetch_param('decimals')
     * @param string ee()->TMPL->fetch_param('dec_point')
     * @param string ee()->TMPL->fetch_param('thousands_sep')
     * @param string ee()->TMPL->fetch_param('prefix')
     * @return string
     **/
    public function format($number)
    {
        if (isset(ee()->TMPL) && isset(ee()->TMPL->tagparams)) {
            $this->set_params(ee()->TMPL->tagparams);
        }

        $number = NumberClass::sanitize($number);

        if (!$this->allow_negative) {
            $number = abs($number);
        }

        if (!$this->format) {
            return $number;
        }

        $prefix = $this->prefix;

        $space = (isset(ee()->TMPL) && bool_string(ee()->TMPL->fetch_param('add_space_after_prefix'))) ? ' ' : '';

        if ($number < 0) {
            $prefix = '-' . $prefix;

            $number *= -1;
        }
        if ($this->prefix_position == 'AFTER') {
            $number = number_format($number, $this->decimals, $this->dec_point, $this->thousands_sep) . ' ' . $prefix;
        } else {
            $number = $prefix . $space . number_format($number, $this->decimals, $this->dec_point,
                $this->thousands_sep);
        }

        $this->reset();

        return $number;
    }

    /**
     * @param $params
     * @return $this
     */
    public function set_params($params)
    {
        if (is_array($params)) {
            $defaults = get_class_vars(__CLASS__);

            foreach ($params as $key => $value) {
                if (array_key_exists($key, $defaults)) {
                    if (is_bool($defaults[$key]) && !is_bool($value)) {
                        $this->$key = bool_string($value);
                    } else {
                        $this->$key = $value;
                    }
                }
            }
        }

        return $this;
    }
}
