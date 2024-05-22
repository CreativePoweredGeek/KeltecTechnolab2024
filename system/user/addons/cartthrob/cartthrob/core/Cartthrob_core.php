<?php

use CartThrob\Math\Number;

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

abstract class Cartthrob_core
{
    public static array $_drivers = ['core', 'payment'];
    public static array $_plugins = ['shipping', 'discount', 'price', 'tax'];
    public static array $_utilities = ['registered_discount'];

    /** @var Cartthrob_cart */
    public $cart;

    /** @var Cartthrob_store */
    public $store;

    /** @var Cartthrob_hooks */
    public $hooks;
    public $cart_defaults = [];
    public $item_defaults = [];
    public $product_defaults = [];
    public $customer_info_defaults = [];
    protected $config = [];
    private $cache;
    private $lang = [];
    private $errors = [];

    /**
     * @param $driver
     * @param array $params
     * @return mixed
     */
    public static function instance($driver, $params = [])
    {
        if (empty($driver)) {
            Cartthrob_core::core_error('No driver specified.');
        }

        spl_autoload_register('Cartthrob_core::autoload');

        $driver = 'Cartthrob_core_' . $driver;

        $instance = new $driver($params);

        if (isset($params['config'])) {
            $instance->config = $params['config'];
        }

        // the sequence is important here!

        $instance->set_child('hooks', $instance->hooks);

        $instance->set_child('store');

        $cart = (isset($params['cart'])) ? $params['cart'] : [];

        $instance->set_child('cart', $cart);

        spl_autoload_unregister('Cartthrob_core::autoload');

        return $instance;
    }

    /**
     * @param $error
     */
    public static function core_error($error)
    {
        trigger_error($error);
    }

    /**
     * @param $class
     */
    public static function autoload($class)
    {
        if (!str_starts_with($class, 'Cartthrob_')) {
            return;
        }

        $short_class = Cartthrob_core::get_class($class);

        // grab first "node" of class name
        $parts = explode('_', $short_class);
        $type = current($parts);

        $class = 'Cartthrob_' . $short_class;

        if (in_array($short_class, self::$_utilities)) {
            $paths = [CARTTHROB_CORE_PATH . "Cartthrob_{$short_class}.php"];
        } else {
            $paths = [CARTTHROB_CORE_PATH . "Cartthrob_{$type}.php"];

            if (in_array($type, Cartthrob_core::$_drivers)) {
                $paths[] = CARTTHROB_DRIVER_PATH . "{$type}/{$class}.php";
            } else {
                if (in_array($type, Cartthrob_core::$_plugins)) {
                    $paths[] = CARTTHROB_PLUGIN_PATH . "{$type}/{$class}.php";
                } else {
                    if (count($parts) > 1) {
                        $paths[] = CARTTHROB_CORE_PATH . 'Cartthrob_child.php';
                        $paths[] = CARTTHROB_CORE_PATH . "{$type}/{$class}.php";
                    }
                }
            }
        }

        foreach ($paths as $path) {
            if (!file_exists($path)) {
                Cartthrob_core::core_error(sprintf('File %s not found.', basename($path)));
            }

            require_once $path;
        }

        if (!class_exists($class)) {
            Cartthrob_core::core_error(sprintf('Class %s not found.', $class));
        }
    }

    /**
     * @param $class
     * @return bool|string
     */
    public static function get_class($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (strpos($class, 'Cartthrob_') === 0) {
            $class = substr($class, 10);
        }

        return $class;
    }

    /**
     * @param null $args
     * @return array|bool|mixed
     */
    public function config($args = null)
    {
        $args = (is_array($args)) ? $args : func_get_args();

        $config = $this->config;

        foreach ($args as $key) {
            if (isset($config[$key])) {
                $config = $config[$key];
            } else {
                return false;
            }
        }

        return $config;
    }

    /**
     * @param $key
     * @param bool $value
     * @return $this
     */
    public function set_config($key, $value = false)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->config[$k] = $v;
            }
        } else {
            $this->config[$key] = $value;
        }

        return $this;
    }

    /**
     * @param $override_config
     */
    public function override_config($override_config)
    {
        if (!is_array($override_config)) {
            return;
        }

        $this->config = $this->array_merge($this->config, $override_config);
    }

    /**
     * @param $a
     * @param $b
     * @return mixed
     */
    public function array_merge($a, $b)
    {
        foreach ($b as $key => $value) {
            if (is_array($value) && isset($a[$key])) {
                $a[$key] = $this->array_merge($a[$key], $value);
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }

    /**
     * @param $key
     * @return bool
     */
    public function cache_pop($key)
    {
        $data = $this->cache($key);

        $this->clear_cache($key);

        return $data;
    }

    /**
     * @param $key
     * @return bool
     */
    public function cache($key)
    {
        if (is_array($key) && $key) {
            $cache = &$this->cache;

            foreach ($key as $value) {
                if (!isset($cache[$value])) {
                    return false;
                }

                $cache = $cache[$value];
            }

            return $cache;
        }

        return (isset($this->cache[$key])) ? $this->cache[$key] : false;
    }

    /**
     * @param bool $key
     */
    public function clear_cache($key = false)
    {
        if ($key === false) {
            $this->cache = [];
        } else {
            if (is_array($key) && count($key) > 1) {
                $cache = &$this->cache;

                for ($i = 0; $i < count($key) - 1; $i++) {
                    if (!isset($cache[$key[$i]])) {
                        return;
                    }

                    $cache = &$cache[$key[$i]];
                }

                unset($cache[end($key)]);
            } else {
                unset($this->cache[$key]);
            }
        }
    }

    /**
     * @param $error
     * @return $this
     */
    public function set_error($error)
    {
        $this->errors[] = $error;

        return $this;
    }

    /**
     * @param $errors
     * @return $this
     */
    public function set_errors($errors)
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * @return $this
     */
    public function clear_errors()
    {
        $this->errors = [];

        return $this;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function lang($key)
    {
        return (isset($this->lang[$key])) ? $this->lang[$key] : $key;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function set_cache($key, $value)
    {
        if (!is_array($key)) {
            $key = [$key];
        }

        $cache = &$this->cache;

        foreach ($key as $k) {
            if (!isset($cache[$k])) {
                $cache[$k] = null;
            }

            $cache = &$cache[$k];
        }

        $cache = $value;

        return $this;
    }

    /**
     * @param $name
     * @param array $params
     * @return $this
     */
    public function set_child($name, $params = [])
    {
        static $children = ['hooks', 'store', 'cart'];

        if (!in_array($name, $children)) {
            return $this;
        }

        $this->$name = Cartthrob_core::create_child($this, $name, $params);
    }

    /**
     * @param Cartthrob_core $core
     * @param string $className
     * @param array $params
     * @param array $defaults
     * @return Cartthrob_child
     */
    public static function create_child($core, $className, $params = [], $defaults = [])
    {
        spl_autoload_register('Cartthrob_core::autoload');

        $className = 'Cartthrob_' . Cartthrob_core::get_class($className);

        /** @var Cartthrob_child $child */
        $child = new $className();
        $child->set_core($core);
        $child->initialize($params, $defaults);

        spl_autoload_unregister('Cartthrob_core::autoload');

        return $child;
    }

    /**
     * @param $value
     * @return string
     */
    public function round($value): string
    {
        $value = Number::sanitize($value);
        $precision = (int)ee()->cartthrob->store->config('number_format_defaults_decimals');

        return number_format(ee('cartthrob:MoneyService')->round($value), $precision, '.', '');
    }

    /**
     * @param $msg
     * @TODO What is this?
     */
    public function log($msg)
    {
    }

    /**
     * @param int $which
     * @return bool
     */
    public function caller($which = 0)
    {
        $which += 2;

        $backtrace = debug_backtrace();

        return (isset($backtrace[$which])) ? $backtrace[$which] : false;
    }

    /* abstract methods */
    abstract public function get_product($product_id);

    abstract public function get_categories();

    abstract public function save_cart();

    abstract public function validate_coupon_code($coupon_code);
}
