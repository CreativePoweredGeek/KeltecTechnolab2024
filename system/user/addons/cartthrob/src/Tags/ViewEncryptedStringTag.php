<?php

namespace CartThrob\Tags;

use EE_Session;
use ExpressionEngine\Service\Encrypt\Encrypt;

class ViewEncryptedStringTag extends Tag
{
    /** @var Encrypt */
    private $encrypt;

    public function __construct(EE_Session $session, Encrypt $encrypt)
    {
        parent::__construct($session);

        $this->encrypt = $encrypt;
    }

    public function process()
    {
        if (!$this->hasParam('string')) {
            return '';
        }

        return base64_encode(rawurlencode($this->encrypt->encode($this->param('string'), $this->param('key'))));
    }
}
