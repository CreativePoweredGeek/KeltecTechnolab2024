<?php

namespace CartThrob\Services;

use ExpressionEngine\Library\Security\XSS;
use ExpressionEngine\Service\Encrypt\Encrypt;

class EncryptionService
{
    /** @var Encrypt */
    private Encrypt $encrypt;

    /** @var XSS */
    private XSS $xss;

    /**
     * EncryptionService constructor.
     * @param Encrypt $encrypt
     * @param XSS $xss
     */
    public function __construct(Encrypt $encrypt, XSS $xss)
    {
        $this->encrypt = $encrypt;

        $this->xss = $xss;
    }

    /**
     * Encode
     *
     * Encodes the message string using bitwise XOR encoding.
     * The key is combined with a random hash, and then it
     * too gets converted using XOR. The whole thing is then run
     * through mcrypt (if supported) using the randomized key.
     * The end result is a double-encrypted message string
     * that is randomized with each call to this function,
     * even if the supplied message and key are the same.
     *
     * @param null $string
     * @param string    the string to encode
     * @return string|null
     */
    public function encode($string = null, $key = ''): ?string
    {
        if (is_null($string)) {
            return null;
        }

        return base64_encode(rawurlencode($this->encrypt->encode($string, $key)));
    }

    /**
     * Decode
     *
     * Reverses the above process
     *
     * @param null $string
     * @param string
     * @return string|null
     */
    public function decode($string = null, $key = ''): ?string
    {
        if (is_null($string)) {
            return null;
        }

        return $this->xss->clean($this->encrypt->decode(rawurldecode(base64_decode($string)), $key));
    }
}
