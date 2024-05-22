<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

class Cartthrob_hooks extends Cartthrob_child
{
    public $hooks = [];
    public $hook;
    public $value;
    public $end = false;

    public $enabled = true;

    public function disable()
    {
        $this->enabled = false;
    }

    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * @param array $hooks
     * @param array $defaults
     * @return Cartthrob_hooks|void
     */
    public function initialize($hooks = [], $defaults = [])
    {
        return $this->set_hooks($hooks);
    }

    /**
     * @param $hooks
     * @return $this
     */
    public function set_hooks($hooks)
    {
        if (is_array($hooks)) {
            $this->hooks = $hooks;
        }

        return $this;
    }

    /**
     * @param $hook
     * @return $this
     */
    public function set_hook($hook)
    {
        $this->hook = $hook;

        return $this;
    }

    /**
     * @param $hook
     * @return $this
     */
    public function add_hook($hook)
    {
        if (is_array($hook)) {
            foreach ($hooks as $hook) {
                $this->add_hook($hook);
            }
        } else {
            $this->hooks[] = $hook;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function end()
    {
        return $this->end;
    }

    /**
     * @param bool $end
     * @return $this
     */
    public function set_end($end = true)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * @return bool
     */
    public function run()
    {
        $this->end = false;
        $this->value = null;

        if (in_array($this->hook, $this->hooks) && method_exists($this->core, $this->hook)) {
            $args = func_get_args();

            if (count($args) > 0) {
                $this->set_value(call_user_func_array([$this->core, $this->hook], $args));
            } else {
                // a little faster
                $this->set_value($this->core->{$this->hook}());
            }

            return true;
        }

        return false;
    }

    /**
     * @param $value
     * @return $this
     */
    public function set_value($value)
    {
        $this->value = $value;

        return $this;
    }
}
