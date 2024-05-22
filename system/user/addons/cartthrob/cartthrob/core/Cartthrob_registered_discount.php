<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

class Cartthrob_registered_discount extends Cartthrob_child
{
    protected $amount;
    protected $reason;
    protected $meta;
    protected $coupon_code;

    protected $defaults = [
        'amount' => 0,
        'reason' => '',
        'meta' => null,
        'coupon_code' => false,
    ];

    /**
     * @return mixed
     */
    public function amount()
    {
        return $this->amount;
    }

    /**
     * @return mixed
     */
    public function reason()
    {
        return $this->reason;
    }

    /**
     * @return mixed
     */
    public function meta()
    {
        return $this->meta;
    }

    /**
     * @return mixed
     */
    public function coupon_code()
    {
        return $this->coupon_code;
    }
}
