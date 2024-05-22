<?php

use CartThrob\Dependency\Illuminate\Support\Arr;

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

abstract class Cartthrob_child
{
    /** @var Cartthrob_core */
    protected $core;
    protected $defaults = [];
    protected $errors = [];

    protected ?string $parent_class = null;

    protected ?string $subclass = null;

    public function errors()
    {
        return $this->errors;
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
     * @return $this
     */
    public function clear_errors()
    {
        $this->errors = [];

        return $this;
    }

    /**
     * @param $method
     * @param $args
     * @return bool|mixed
     */
    public function __call($method, $args)
    {
        if ($this->parent_class()) {
            $_method = $this->parent_class() . '_' . $method;
        } else {
            $_method = Cartthrob_core::get_class($this) . '_' . $method;
        }

        try {
            if (!in_array($_method, get_class_methods($this->core))) {
                throw new Exception('Call to undefined method %s::%s() in %s on line %s');
            } elseif (!is_callable([$this->core, $_method])) {
                throw new Exception('Call to private method %s::%s() in %s on line %s');
            }
        } catch (Exception $e) {
            $backtrace = $e->getTrace();
            $backtrace = $backtrace[1];

            return trigger_error(sprintf($e->getMessage(), $backtrace['class'], $backtrace['function'],
                $backtrace['file'], $backtrace['line']));
        }

        array_push($args, $this);

        return call_user_func_array([$this->core, $_method], $args);
    }

    /**
     * @return bool
     */
    public function parent_class()
    {
        if (is_null($this->parent_class)) {
            $classname = Cartthrob_core::get_class($this);

            $parts = explode('_', $classname);

            if (count($parts) > 1) {
                $this->parent_class = $parts[0];
            }
        }

        return $this->parent_class;
    }

    /**
     * @param bool $key
     * @return array|mixed
     */
    public function defaults($key = false)
    {
        if ($key === false) {
            return $this->defaults;
        }

        return Arr::get($this->defaults, $key, false);
    }

    /**
     * @return array
     */
    public function default_keys()
    {
        return array_keys($this->defaults);
    }

    /**
     * @return bool
     */
    public function is_null()
    {
        foreach ($this->defaults as $key => $value) {
            if ($this->$key !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }

    /**
     * @param $data
     */
    public function unserialize($data)
    {
        $this->initialize(unserialize($data));
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = [];

        foreach ($this->defaults as $key => $value) {
            $data[$key] = $this->{$key};
        }

        return $data;
    }

    /**
     * @param array $params
     * @param array $defaults
     */
    public function initialize($params = [], $defaults = [])
    {
        $this->set_defaults($defaults);
        $this->prepare_params($params);

        foreach ($this->defaults as $key => $value) {
            $this->$key = Arr::get($params, $key, $value);
        }
    }

    /**
     * @param $key
     * @param null $value
     */
    public function set_defaults($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set_defaults($k, $v);
            }
        } else {
            $this->defaults[$key] = $value;
        }
    }

    /**
     * @param $params
     * @return $this
     */
    public function prepare_params(&$params)
    {
        return $this;
    }

    /**
     * @param Cartthrob_core $core
     */
    public function set_core($core)
    {
        $this->core = $core;
    }

    /**
     * @return bool|string
     */
    public function subclass()
    {
        if (is_null($this->subclass)) {
            if ($parent_class = $this->parent_class()) {
                $this->subclass = substr(Cartthrob_core::get_class($this), strlen($parent_class) + 1);
            }
        }

        return $this->subclass;
    }
}
