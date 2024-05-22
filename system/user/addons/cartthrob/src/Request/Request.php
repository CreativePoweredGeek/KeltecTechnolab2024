<?php

namespace CartThrob\Request;

use CartThrob\Dependency\Illuminate\Support\Arr;
use ExpressionEngine\Library\Security\XSS;
use ExpressionEngine\Service\Encrypt\Encrypt;

class Request
{
    /** @var XSS */
    private $xss;

    /** @var Encrypt */
    private $encrypt;

    public function __construct(XSS $xss, Encrypt $encrypt)
    {
        $this->xss = $xss;
        $this->encrypt = $encrypt;
    }

    /**
     * Retrieve an input item from the request
     *
     * @param string|null $key
     * @param mixed $default
     * @param bool $xss
     * @return mixed
     */
    public function input($key = null, $default = null, $xss = true)
    {
        $input = $_POST + $_GET;

        array_walk_recursive($input, function ($item) use ($xss) {
            $item = trim($item);

            if ($xss) {
                $item = $this->xss->clean($item);
            }

            return $item;
        });

        return Arr::get($input, $key, $default);
    }

    /**
     * Retrieve and decode input
     *
     * @param string $key
     * @param mixed $default
     * @param bool $boolean
     * @return mixed
     */
    public function decode(string $key, $default = null, bool $boolean = false)
    {
        if (!$this->has($key)) {
            return $default;
        }

        if (isset($_POST[$key])) {
            $data = $this->xss->clean(
                $this->encrypt->decode(Arr::get($_POST, $key))
            );
        } elseif (isset($_GET[$key])) {
            $data = $this->xss->clean(
                $this->encrypt->decode(Arr::get($_GET, $key))
            );
        } else {
            return $default;
        }

        return $boolean ? $this->boolString($data) : $data;
    }

    /**
     * Retrieve input as a boolean value
     *
     * @param string $key
     * @param bool $default
     * @return bool
     */
    public function boolean(string $key, $default = false): bool
    {
        return $this->boolString($this->input($key, $default));
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key): bool
    {
        return Arr::has($this->input(), $key);
    }

    /**
     * @param $data
     * @return bool
     */
    private function boolString($data): bool
    {
        if (is_bool($data)) {
            return $data;
        }

        switch (strtolower($data)) {
            case 'true':
            case 't':
            case 'yes':
            case 'y':
            case 'on':
            case '1':
                return true;

            case 'false':
            case 'f':
            case 'no':
            case 'n':
            case 'off':
            case '0':
            default:
                return false;
        }
    }
}
