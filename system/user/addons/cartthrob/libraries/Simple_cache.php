<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

if (class_exists('Simple_cache')) {
    return;
}

class Simple_cache
{
    /**
     * @param $name
     * @param $data
     * @param string $dir
     * @return mixed
     */
    public function set($name, $data, $dir = '')
    {
        if (!$dir) {
            $dir = APPPATH . 'cache';
        }

        if (strpos($name, '/') !== false) {
            $subdir = pathinfo($name, PATHINFO_DIRNAME);

            $name = pathinfo($name, PATHINFO_BASENAME);

            if ($subdir && substr($subdir, 0, 1) !== '/') {
                $subdir = '/' . $subdir;
            }

            $dir .= $subdir;
        }

        ee()->load->helper('file');

        if (!is_dir($dir)) {
            mkdir($dir, DIR_WRITE_MODE);
            @chmod($dir, DIR_WRITE_MODE);
        }

        $cache = [
            'timestamp' => time(),
            'data' => $data,
        ];

        if (write_file($dir . '/' . $name, serialize($cache))) {
            @chmod($dir . '/' . $name, FILE_WRITE_MODE);
        }

        return $data;
    }

    /**
     * @param $name
     * @param int $cache_expire
     * @param string $dir
     * @return bool
     */
    public function get($name, $cache_expire = 86400, $dir = '')
    {
        if (!$dir) {
            $dir = APPPATH . 'cache';
        }

        if (substr($name, 0, 1) === '/') {
            $name = substr($name, 1);
        }

        ee()->load->helper('file');

        $contents = read_file($dir . '/' . $name);

        if ($contents !== false) {
            $cache = unserialize($contents);

            if (($cache['timestamp'] + $cache_expire) > time()) {
                return $cache['data'];
            }
        }

        return false;
    }
}
