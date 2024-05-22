<?php

use CartThrob\Services\MoneyService;

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

class Cartthrob_item_package extends Cartthrob_item
{
    protected $sub_items;

    protected $defaults = [
        'row_id' => null,
        'quantity' => 1,
        'product_id' => null,
        'site_id' => null,
        'price' => 0,
        'weight' => 0,
        'shipping' => 0,
        'title' => '',
        'no_tax' => false,
        'no_shipping' => false,
        'item_options' => [],
        'sub_items' => [],
        'discounts' => [],
    ];

    /**
     * @param array $params
     * @param array $defaults
     */
    public function initialize($params = [], $defaults = [])
    {
        parent::initialize($params);

        if (isset($params['sub_items'])) {
            $this->set_sub_items($params['sub_items']);
        }
    }

    /**
     * @param $items
     */
    protected function set_sub_items($items)
    {
        foreach ($items as $item) {
            $class = (isset($item['class'])) ? $item['class'] : 'default';

            $this->sub_items[$item['row_id']] = Cartthrob_core::create_child($this->core, 'item_' . $class, $item, $this->core->item_defaults);
            $this->sub_items[$item['row_id']]->set_parent_item($this);
        }
    }

    /**
     * @param $row_id
     * @return bool
     */
    public function sub_item($row_id)
    {
        return (isset($this->sub_items[$row_id])) ? $this->sub_items[$row_id] : false;
    }

    /**
     * @return float|int
     */
    public function price()
    {
        // if the price is set explicitly via the product, then return it
        if (is_numeric($this->product()->price())) {
            return $this->product()->price($this->item_options());
        }

        $price = 0;

        foreach ($this->sub_items() as $row_id => $item) {
            $price += $item->price_subtotal();
        }

        return $price;
    }

    // @TODO add fixed pricing too

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
     * @return array|bool
     */
    public function sub_items()
    {
        return $this->sub_items;
    }

    /**
     * @return float|int
     */
    public function taxed_price()
    {
        if (is_numeric($this->product()->price())) {
            // @TODO if possible make this use item's methods of getting taxes. this may not always work if the item has a specific tax class
            return $this->product()->price($this) * (1 + $this->core->store->tax_rate());
        }

        $price = 0;

        foreach ($this->sub_items() as $item) {
            $price += $item->taxed_price_subtotal();
        }

        return $price;
    }

    /**
     * @return bool
     */
    public function in_stock()
    {
        return $this->inventory() > 0;
    }

    /**
     * @return bool|float|int
     */
    public function inventory()
    {
        $inventory = false;

        foreach ($this->sub_items() as $row_id => $item) {
            if (!$item->product_id()) {
                continue;
            }

            $_inventory = floor($item->inventory($item->item_options()) / $item->quantity());

            if ($inventory === false || $_inventory < $inventory) {
                $inventory = $_inventory;
            }
        }

        return ($inventory === false) ? parent::inventory() : $inventory;
    }

    /**
     * @return float|int
     */
    public function weight()
    {
        // if the price is set explicitly via the product, then return it
        if (is_numeric($this->product()->weight())) {
            return $this->product()->weight($this);
        }

        $weight = 0;

        foreach ($this->sub_items() as $item) {
            $weight += $item->weight() * $item->quantity();
        }

        return $weight;
    }

    /**
     * @return array
     */
    public function data()
    {
        $data = $this->product()->toArray();

        foreach ($this->toArray() as $key => $value) {
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * Get the product title
     *
     * @return string
     */
    public function title()
    {
        return $this->product()->title();
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
            $parent = parent::meta();
            if (!$parent) {
                $parent = [];
            }

            return array_merge($parent, $this->product()->meta());
        }

        $meta = parent::meta($key);

        if ($meta === false) {
            return $this->product()->meta($key);
        }

        return $meta;
    }

    /* item_product */

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
     * Update the item's attributes with an array
     *
     * @param array $data
     * @return Cartthrob_item
     */
    public function update($data)
    {
        $sub_items = (isset($data['sub_items'])) ? $data['sub_items'] : [];

        unset($data['sub_items']);

        parent::update($data);

        foreach ($sub_items as $row_id => $item) {
            if (isset($this->sub_items[$row_id])) {
                $this->sub_items[$row_id]->update($item);
            }
        }

        // @TODO do something with sub_items
    }
}
