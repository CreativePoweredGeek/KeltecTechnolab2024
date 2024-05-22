<?php

namespace CartThrob\Tags;

use EE_Session;
use ExpressionEngine\Service\Encrypt\Encrypt;

class ViewDecryptedStringTag extends Tag
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
        if (!$this->param('string')) {
            return '';
        }

        return $this->encrypt->decode(
            rawurldecode(base64_decode($this->param('string'))),
            $this->param('key')
        );
    }
}
