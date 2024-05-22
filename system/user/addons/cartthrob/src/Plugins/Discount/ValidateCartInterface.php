<?php

namespace CartThrob\Plugins\Discount;

interface ValidateCartInterface
{
    /**
     * @return bool
     */
    public function validateCart(): bool;
}
