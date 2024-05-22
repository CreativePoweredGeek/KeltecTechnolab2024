<?php

namespace CartThrob\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * Cart Model
 */
class Cart extends AbstractModel
{
    /**
     * @todo Add rules to validate related data
     * @var string[]
     */
    protected static $_validation_rules = [
        'cart' => 'required|alphaNumeric',
        'timestamp' => 'required',
    ];

    protected static $_primary_key = 'id';
    protected static $_table_name = 'cartthrob_cart';

    protected $id;
    protected $cart;
    protected $timestamp;
    protected $url;

    protected static $_events = [
        'beforeInsert',
        'beforeUpdate',
    ];

    protected function get__cart()
    {
        if (is_string($this->getRawProperty('cart'))) {
            $cart = _unserialize(ee('Encrypt')->decode($this->getRawProperty('cart')));
            if (!is_array($cart)) {
                $cart = [];
            }

            return $cart;
        }

        return $this->cart;
    }

    protected function preProcessWrite()
    {
        if (is_array($this->cart)) {
            $this->cart = ee('Encrypt')->encode(serialize($this->cart));
        }

        if ($this->url == '') {
            $this->url = null;
        }
    }

    /**
     * Preprocess create
     */
    public function onBeforeInsert()
    {
        $this->preProcessWrite();
        $this->timestamp = time();
    }

    /**
     * Preprocess update
     */
    public function onBeforeUpdate()
    {
        $this->preProcessWrite();
    }
}
