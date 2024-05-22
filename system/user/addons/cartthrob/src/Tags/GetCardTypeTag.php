<?php

namespace CartThrob\Tags;

class GetCardTypeTag extends Tag
{
    public function process()
    {
        $card = new \CartThrob\Dependency\Omnipay\Common\CreditCard(['number' => $this->param('number')]);

        return $card->getBrand() ?? 'Unknown Card Type';
    }
}
