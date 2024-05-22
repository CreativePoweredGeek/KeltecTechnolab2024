<?php

use CartThrob\Services\MoneyService;

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

class Cartthrob_item_product extends Cartthrob_item
{
    protected $defaults = [
        'row_id' => null,
        'quantity' => 1,
        'product_id' => null,
        'site_id' => null,
        'shipping' => null,
        'weight' => null,
        'price' => null,
        'no_tax' => false,
        'no_shipping' => false,
        'item_options' => [],
        'meta' => [],
        'title' => null,
        'discounts' => [],
    ];

    /**
     * Get the product title
     *
     * @return string
     */
    public function title()
    {
        return (!$this->title) ? $this->product()->title() : $this->title;
    }

    /**
     * @param bool $force_create
     * @return bool|Cartthrob_child|Cartthrob_product
     */
    public function product($force_create = true)
    {
        if (!$product = $this->core->store->product($this->product_id)) {
            // create a NULLed product
            if ($force_create) {
                $product = Cartthrob_core::create_child($this->core, 'product');
            }
        }

        return $product;
    }

    /**
     * True if product inventory is greater than zero
     *
     * @return string
     */
    public function in_stock()
    {
        return $this->product()->in_stock($this->item_options());
    }

    /**
     * Get the product's inventory, checked against item_options
     *
     * @return string
     */
    public function inventory()
    {
        return $this->product()->inventory($this->item_options());
    }

    // shortcut to this item's corresponding product object

    /**
     * Get a value from the meta array, or
     * from the product's meta array, or
     * get the whole array by not specifying a key
     *
     * @param string|false $key
     * @return mixed|false
     */
    public function meta($key = false)
    {
        if ($key === false) {
            return array_merge(parent::meta(), $this->product()->meta());
        }

        $meta = parent::meta($key);

        if ($meta === false) {
            return $this->product()->meta($key);
        }

        return $meta;
    }

    /**
     * @return float|int
     */
    public function base_price()
    {
        $item = clone $this;

        $item->clear_item_options();

        return $this->product()->price($item);
    }

    /**
     * @return float|int
     */
    public function price()
    {
        if (!is_null($this->price)) {
            return $this->price;
        }

        return $this->product()->price($this);
    }

    /**
     * @return float|int
     */
    public function weight()
    {
        if (!is_null($this->weight)) {
            return $this->weight;
        }

        return $this->product()->weight($this);
    }

    /**
     * Get the item shipping cost
     *
     * @return float
     */
    public function shipping(): float
    {
        /** @var MoneyService $moneyService */
        $moneyService = ee('cartthrob:MoneyService');
        $shippingRate = $moneyService->fresh();

        if ($this->no_shipping) {
            return $moneyService->toFloat($shippingRate);
        }

        if ($this->core->hooks->set_hook('item_shipping_start')->run() && $this->core->hooks->end()) {
            $shippingRate = $moneyService->toMoney($this->core->hooks->value());
        } else {
            $value = (is_null($this->shipping)) ? $this->product()->shipping() * $this->quantity() : $this->shipping * $this->quantity();

            if ($this->core->hooks->set_hook('item_shipping_end')->run($value) && $this->core->hooks->end()) {
                $value = $this->core->hooks->value();
            }

            $shippingRate = $moneyService->toMoney($value);
        }

        return $moneyService->toFloat($shippingRate);
    }

    /**
     * @return array
     */
    public function data()
    {
        $data = $this->product()->toArray();

        if (isset($data['inventory'])) {
            $data['inventory'] = $this->product()->inventory($this->item_options);
        }

        foreach ($this->toArray() as $key => $value) {
            $data[$key] = $value;
        }

        return $data;
    }
}
