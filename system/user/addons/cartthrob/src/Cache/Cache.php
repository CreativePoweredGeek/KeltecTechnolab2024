<?php

namespace CartThrob\Cache;

use EE_Session;
use Illuminate\Support\Arr;

class Cache
{
    /** @var EE_Session */
    private $session;

    public function __construct(EE_Session $session)
    {
        $this->session = $session;
    }

    /**
     * Get data from session cache
     *
     * @param string $class
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null, $class = 'cartthrob')
    {
        return Arr::get($this->session->cache, "{$class}.{$key}", $default);
    }

    /**
     * Check if data is in session cache
     *
     * @param $key
     * @param string $class
     * @return bool
     */
    public function has($key, $class = 'cartthrob')
    {
        return Arr::has($this->session->cache, "{$class}.{$key}");
    }

    /**
     * Set data to session cache
     *
     * @param string $class
     * @param string $key
     * @param mixed $val
     */
    public function set($key, $val, $class = 'cartthrob')
    {
        Arr::set($this->session->cache, "{$class}.{$key}", $val);
    }
}
