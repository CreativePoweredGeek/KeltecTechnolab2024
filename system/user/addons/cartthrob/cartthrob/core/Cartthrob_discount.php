<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

abstract class Cartthrob_discount extends Cartthrob_child
{
    public static $global_settings = [
        [
            'type' => 'textarea',
            'short_name' => 'used_by',
            'name' => 'discount_redeemed_by',
            'note' => 'discount_redeemed_by_note',
        ],
        [
            'type' => 'text',
            'short_name' => 'per_user_limit',
            'name' => 'discount_per_user_limit',
            'note' => 'discount_per_user_limit_note',
            'size' => '50px',
        ],
        [
            'type' => 'text',
            'short_name' => 'discount_limit',
            'name' => 'discount_limit',
            'note' => 'discount_limit_note',
            'size' => '50px',
        ],
        [
            'type' => 'text',
            'short_name' => 'member_groups',
            'name' => 'discount_limit_by_member_group',
            'note' => 'discount_limit_by_member_group_note',
        ],
        [
            'type' => 'text',
            'short_name' => 'member_ids',
            'name' => 'discount_limit_by_member_id',
            'note' => 'discount_limit_by_member_id_note',
        ],
    ];
    public $title = '';
    public $settings = [];
    public $plugin_settings = [];
    protected $error;
    protected $coupon_code = false;

    /**
     * @param array $plugin_settings
     * @param array $defaults
     * @return $this|void
     */
    public function initialize($plugin_settings = [], $defaults = [])
    {
        if (is_array($plugin_settings)) {
            $this->plugin_settings = $plugin_settings;
        }

        $this->type = Cartthrob_core::get_class($this);

        return $this;
    }

    /**
     * @param $key
     * @param bool $default
     * @return array|bool|mixed
     */
    public function plugin_settings($key, $default = false)
    {
        if ($key === false) {
            return $this->plugin_settings;
        }

        return (isset($this->plugin_settings[$key])) ? $this->plugin_settings[$key] : $default;
    }

    /**
     * @param $plugin_settings
     * @return $this
     */
    public function set_plugin_settings($plugin_settings)
    {
        $this->plugin_settings = $plugin_settings;

        return $this;
    }

    /**
     * @return bool
     */
    public function coupon_code()
    {
        return $this->coupon_code;
    }

    /**
     * @param $coupon_code
     * @return $this
     */
    public function set_coupon_code($coupon_code)
    {
        if (is_string($coupon_code)) {
            $this->coupon_code = $coupon_code;
        }

        return $this;
    }

    /**
     * @param $error
     * @return $this|Cartthrob_child
     */
    public function set_error($error)
    {
        if (is_string($error)) {
            $this->error = $error;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function error()
    {
        return $this->error;
    }

    abstract public function get_discount();

    protected function createString($string, $data)
    {
        foreach ($data as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            $string = str_replace('{{' . $key . '}}', $value, $string);
        }

        return $string;
    }
}
