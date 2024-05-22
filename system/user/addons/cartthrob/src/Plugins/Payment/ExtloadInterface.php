<?php

namespace CartThrob\Plugins\Payment;

interface ExtloadInterface
{
    /**
     * @param array $data
     * @return void
     */
    public function extload(array $data): void;
}
