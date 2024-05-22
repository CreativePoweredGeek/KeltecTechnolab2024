<?php

if (!function_exists('debug')) {
    function debug($data)
    {
        if (in_array('xdebug', get_loaded_extensions())) {
            var_dump($data);
        } else {
            echo '<pre>' . print_r($data, true) . '</pre>';
        }
    }

    function backtrace($limit = false)
    {
        $backtrace = debug_backtrace(false);

        array_shift($backtrace);
        array_shift($backtrace);

        $return = [];

        foreach ($backtrace as $i => $data) {
            unset($data['args']);

            $return[] = $data;

            debug($data);

            if ($limit !== false && $i === ($limit - 1)) {
                break;
            }
        }

        return $return;
    }

    function caller($which = 0)
    {
        $which += 2;

        $backtrace = debug_backtrace(false);

        return (isset($backtrace[$which]['function'])) ? $backtrace[$which]['function'] : false;
    }
}
