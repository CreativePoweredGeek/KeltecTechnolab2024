<?php

use CartThrob\Math\Number;
use CartThrob\Math\Round;
use CartThrob\Services\MoneyService;

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

abstract class Cartthrob_item extends Cartthrob_child
{
    protected $row_id;
    protected $quantity;
    protected $product_id;
    protected $entry_id;
    protected $site_id;
    protected $price;
    protected $weight;
    protected $shipping;
    protected $title;
    protected $no_tax;
    protected $no_shipping;
    protected $meta;
    protected $item_options;
    protected $parent_item;
    protected $discounts;

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
        'meta' => [],
        'parent_item' => false,
        'discounts' => [],
    ];

    /**
     * @param array $params
     * @param array $defaults
     */
    public function initialize($params = [], $defaults = [])
    {
        if (isset($params['discounts']) && is_array($params['discounts'])) {
            foreach ($params['discounts'] as $row) {
                $this->discounts[] = Cartthrob_core::create_child($this->core, 'registered_discount', $row);
            }
        }

        unset($params['discounts']);

        parent::initialize($params, $defaults);
    }

    /**
     * @param $amount
     * @param string $reason
     * @param null $meta
     */
    public function add_discount($amount, $reason = '', $meta = null)
    {
        $caller = $this->core->caller(0);

        $discount_plugin = null;

        if (isset($caller['object']) && $caller['object'] instanceof Cartthrob_discount) {
            $discount_plugin = $caller['object'];
        }

        $coupon_code = $discount_plugin ? $discount_plugin->coupon_code() : false;

        $this->discounts[] = Cartthrob_core::create_child($this->core, 'registered_discount', [
            'amount' => $amount,
            'reason' => $discount_plugin ? sprintf($reason, $this->core->lang($discount_plugin->title)) : $reason,
            'meta' => $meta,
            'coupon_code' => $coupon_code,
        ]);
    }

    /**
     * @return mixed
     */
    public function discounts()
    {
        return $this->discounts;
    }

    /**
     * @return float|int
     */
    public function discounted_subtotal()
    {
        return $this->discounted_price_subtotal();
    }

    /**
     * @return float|int
     */
    public function discounted_price_subtotal()
    {
        return $this->core->round($this->discounted_price()) * $this->quantity();
    }

    /**
     * @return int|string
     */
    public function discounted_price()
    {
        $discounted_price = $this->price() - $this->discount();

        return $discounted_price >= 0 ? $this->core->round($discounted_price) : 0;
    }

    /**
     * Get the item price
     *
     * @return int|float
     */
    public function price()
    {
        if (empty($this->price)) {
            return 0;
        }

        return $this->core->round($this->price);
    }

    /**
     * @return int
     */
    public function discount()
    {
        $discount = 0;

        foreach ($this->discounts as &$registered_discount) {
            // it's not a valid coupon, get rid of it
            if ($registered_discount->coupon_code() && !in_array($registered_discount->coupon_code(),
                $this->core->cart->coupon_codes())) {
                unset($registered_discount);

                continue;
            }

            $discount += $registered_discount->amount();
        }

        // reset array keys in case a discount was removed
        $this->discounts = array_values($this->discounts);

        return $discount;
    }

    /**
     * Get the item's quantity
     *
     * @return int
     */
    public function quantity()
    {
        return $this->quantity;
    }

    /**
     * @param $row_id
     * @return bool
     */
    public function sub_item($row_id)
    {
        return false;
    }

    /**
     * @return mixed
     */
    public function parent_item()
    {
        return $this->parent_item;
    }

    /**
     * @return bool
     */
    public function is_sub_item()
    {
        return !empty($this->parent_item);
    }

    /**
     * @param $parent_item
     * @return $this
     */
    public function set_parent_item($parent_item)
    {
        $this->parent_item = &$parent_item;

        return $this;
    }

    /**
     * Get the product id
     *
     * @return int
     */
    public function product_id()
    {
        return $this->product_id;
    }

    /**
     * Get the site id
     *
     * @return int
     */
    public function site_id()
    {
        return $this->site_id;
    }

    /**
     * True if inventory is not zero
     *
     * @return bool
     */
    public function in_stock()
    {
        return true;
    }

    /**
     * Get the inventory for this product
     *
     * @return int
     */
    public function inventory()
    {
        return PHP_INT_MAX;
    }

    /**
     * Get a value from the item options array, or
     * get the whole array by not specifying a key
     *
     * @param string|false $key
     * @return mixed|false
     */
    public function item_options($key = false)
    {
        if ($key === false) {
            return $this->item_options;
        }

        return $this->item_options[$key] ?? false;
    }

    /**
     * Set a value in the item options array,
     * or set many values by providing an array.
     *
     * @param array|string $key
     * @param mixed $value
     * @return Cartthrob_item
     */
    public function set_item_options($key, $value = false)
    {
        if (is_null($value)) {
            unset($this->item_options[$key]);

            return $this;
        }

        if (!is_array($key)) {
            $key = [$key => $value];
        }

        $this->item_options = array_merge($this->item_options, $key);

        return $this;
    }

    /**
     * Empty the item_options array
     *
     * @return Cartthrob_item
     */
    public function clear_item_options()
    {
        $this->item_options = [];

        return $this;
    }

    /**
     * Get a value from the meta array, or
     * get the whole array by not specifying a key
     *
     * @param string|false $key
     * @return mixed|false
     */
    public function meta($key = false)
    {
        if ($key === false) {
            return $this->meta;
        }

        return (isset($this->meta[$key])) ? $this->meta[$key] : false;
    }

    /**
     * Set a value in the meta array,
     * or set many values by providing an array.
     *
     * @param array|string $key
     * @param mixed $value
     * @return Cartthrob_item
     */
    public function set_meta($key, $value = false)
    {
        if (!is_array($key)) {
            $key = [$key => $value];
        }

        if (!is_array($this->meta)) {
            $this->meta = [];
        }
        $this->meta = array_merge($this->meta, $key);

        return $this;
    }

    /**
     * Set this item's row id
     *
     * @param int $row_id
     * @return Cartthrob_item
     */
    public function set_row_id($row_id)
    {
        $this->row_id = $row_id;

        return $this;
    }

    /**
     * Change this item's quantity
     * a) removes if quantity is 0
     * b) checks global quantity limit
     * c) checks split items by quantity preference
     *
     * @param int $quantity
     * @return Cartthrob_item
     */
    public function set_quantity($quantity = 0)
    {
        $quantity = Number::sanitize($quantity);
        $quantity = abs($this->core->store->config('allow_fractional_quantities') ? $quantity : Round::roundDown($quantity, 0));

        if ($quantity <= 0) {
            $this->remove();

            return $this;
        }

        if ($quantity != $this->quantity()) {
            if ($this->core->store->config('global_item_limit') && $quantity > $this->core->store->config('global_item_limit')) {
                $quantity = $this->core->store->config('global_item_limit');
            }

            if ($this->core->store->config('product_split_items_by_quantity') && $quantity > 1) {
                for ($i = 2; $i <= $quantity; $i++) {
                    $this->core->cart->duplicate_item($this->row_id());
                }
            } else {
                $this->quantity = $quantity;
            }
        }

        return $this;
    }

    /**
     * Remove this item from the cart
     */
    public function remove()
    {
        $this->core->cart->remove_item($this->row_id);
    }

    /**
     * Get the row id
     *
     * @return int
     */
    public function row_id()
    {
        return $this->row_id;
    }

    /**
     * Increase the item's quantity
     *
     * @param int $quantity
     * @return Cartthrob_item
     */
    public function add_quantity($quantity = 1)
    {
        $quantity = abs(Number::sanitize($quantity));
        $this->quantity += $this->core->store->config('allow_fractional_quantities') ? $quantity : Round::roundDown($quantity, 0);

        return $this;
    }

    /**
     * Decrease the item's quantity
     *
     * @param int $quantity
     * @return Cartthrob_item
     */
    public function remove_quantity($quantity = 1)
    {
        $quantity = abs(Number::sanitize($quantity));
        $this->quantity -= $this->core->store->config('allow_fractional_quantities') ? $quantity : Round::roundDown($quantity, 0);

        return $this;
    }

    /**
     * Update the item's attributes with an array
     *
     * @param array $data
     */
    public function update(array $data)
    {
        // don't want to update the site id of the item. it shoudl be set once, and not modified.
        if (isset($data['site_id'])) {
            unset($data['site_id']);
        }

        foreach ($data as $key => $value) {
            if (!in_array($key, $this->default_keys())) {
                continue;
            }

            if (is_array($value) && is_array($this->{$key})) {
                $this->{$key} = $this->core->array_merge($this->{$key}, $value);
            } else {
                $method = 'set_' . $key;

                if (method_exists($this, $method)) {
                    $this->{$method}($value);
                } else {
                    $this->{$key} = $value;
                }
            }
        }
    }

    /**
     * Set the product id
     *
     * @param int $product_id
     * @return Cartthrob_item
     */
    public function set_product_id($product_id)
    {
        $this->product_id = $product_id;

        return $this;
    }

    /**
     * Get the item title
     *
     * @return string
     */
    public function title()
    {
        return $this->title;
    }

    /**
     * @return float|int
     */
    public function subtotal()
    {
        return $this->price_subtotal();
    }

    /**
     * Get the item price * quantity
     *
     * @return int|float
     */
    public function price_subtotal()
    {
        return $this->core->round($this->price()) * $this->quantity();
    }

    // alias for base price subtotal

    /**
     * Get the item base price * quantity
     *
     * @return int|float
     */
    public function base_price_subtotal()
    {
        return $this->core->round($this->base_price()) * $this->quantity();
    }

    /**
     * Get the item base price
     *
     * @return int|float
     */
    public function base_price()
    {
        return $this->core->round($this->price());
    }

    /**
     * Get the item price w/ tax
     *
     * @return int|float
     */
    public function taxed_price()
    {
        return $this->core->round($this->tax() + $this->price());
    }

    /**
     * @return int|mixed
     */
    public function tax()
    {
        if (!$this->no_tax && $this->core->store->config('tax_plugin')) {
            if ($plugin = $this->core->store->plugin($this->core->store->config('tax_plugin'))) {
                if (method_exists($plugin, 'get_tax')) {
                    return $this->get_item_tax($plugin, $this->price());
                }
            }
        }

        return 0;
    }

    /**
     * get_item_tax
     *
     * gets the item tax, and rounds if appropriate to the configuration
     *
     * @param object $plugin
     * @param $cost
     * @return mixed
     */
    private function get_item_tax($plugin, $cost)
    {
        $tax = $plugin->get_tax($cost, $this);

        if ($this->core->store->config('round_tax_only_on_subtotal')) {
            return $tax;
        } else {
            return $this->core->round($tax);
        }
    }

    /**
     * Get the item price w/ tax * quantity
     *
     * @return int|float
     */
    public function taxed_price_subtotal()
    {
        return $this->core->round(($this->tax() * $this->quantity()) + ($this->price() * $this->quantity()));
    }

    /**
     * Get the item base price w/ tax
     *
     * @return int|float
     */
    public function taxed_base_price()
    {
        return $this->core->round($this->base_tax() + $this->base_price());
    }

    /**
     * @return int|mixed
     */
    public function base_tax()
    {
        if (!$this->no_tax && $this->core->store->config('tax_plugin')) {
            if ($plugin = $this->core->store->plugin($this->core->store->config('tax_plugin'))) {
                if (method_exists($plugin, 'get_tax')) {
                    return $this->get_item_tax($plugin, $this->base_price());
                }
            }
        }

        return 0;
    }

    /**
     * Get the item base price w/ tax * quantity
     *
     * @return int|float
     */
    public function taxed_base_price_subtotal()
    {
        return $this->core->round($this->base_tax() + $this->base_price()) * $this->quantity();
    }

    /**
     * Get the item weight
     *
     * @return int|float
     */
    public function weight()
    {
        return $this->weight;
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
            $value = $this->shipping * $this->quantity();

            if ($this->core->hooks->set_hook('item_shipping_end')->run($value) && $this->core->hooks->end()) {
                $value = $this->core->hooks->value();
            }

            $shippingRate = $moneyService->toMoney($value);
        }

        return $moneyService->toFloat($shippingRate);
    }

    /**
     * Set the item title
     *
     * @param string $title
     * @return Cartthrob_item
     */
    public function set_title($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the item price
     *
     * @param $price
     * @return Cartthrob_item
     */
    public function set_price($price)
    {
        $this->price = Number::sanitize($price);

        return $this;
    }

    /**
     * Set the item weight
     *
     * @param $weight
     * @return Cartthrob_item
     */
    public function set_weight($weight)
    {
        $this->weight = abs(Number::sanitize($weight));

        return $this;
    }

    /**
     * @param bool $tax_exempt if set to TRUE, the item is not taxable
     */
    public function set_tax_exempt($tax_exempt = true)
    {
        $this->no_tax = (bool)$tax_exempt;
    }

    /**
     * @param bool $shipping_exempt if set to TRUE, the item is not taxable
     */
    public function set_shipping_exempt($shipping_exempt = true)
    {
        $this->no_shipping = (bool)$shipping_exempt;
    }

    /**
     * Set the item shipping cost
     *
     * @param $shipping
     * @return Cartthrob_item
     */
    public function set_shipping($shipping)
    {
        $this->shipping = abs(Number::sanitize($shipping));

        return $this;
    }

    /**
     * True if item not marked no_shipping
     *
     * @return bool
     */
    public function is_shippable()
    {
        return !$this->no_shipping;
    }

    /**
     * True if item not marked no_tax
     *
     * @return bool
     */
    public function is_taxable()
    {
        return !$this->no_tax;
    }

    /**
     * Validate/sanitize parameters when initializing
     *
     * @param array $params
     *
     * @return Cartthrob_item
     */
    public function prepare_params(&$params)
    {
        if (!is_array($params)) {
            return $this;
        }

        $numeric = ['quantity', 'price', 'weight', 'shipping'];

        foreach ($numeric as $key) {
            if (isset($params[$key])) {
                $params[$key] = abs(Number::sanitize($params[$key]));
            }
        }

        if (isset($params['quantity'])) {
            if ($this->core->store->config('global_item_limit') != false && $params['quantity'] > $this->core->store->config('global_item_limit')) {
                $params['quantity'] = $this->core->store->config('global_item_limit');
            }
        }

        if (isset($params['item_options']) && is_array($params['item_options']) && is_array($this->item_options)) {
            $params['item_options'] = array_merge($this->item_options, $params['item_options']);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function data()
    {
        return $this->toArray();
    }

    /**
     * Convert self to array
     *
     * @param bool $stripDefaults To minimize the size of the array, you can set to true to not save its values that are the default values
     * @return array
     */
    public function toArray($stripDefaults = false)
    {
        $data = parent::toArray();

        if ($this->sub_items()) {
            foreach ($this->sub_items() as $rowId => $subItem) {
                /* @var Cartthrob_item $subItem */
                $data['sub_items'][$rowId] = $subItem->toArray();
            }
        }

        if ($this->discounts) {
            $data['discounts'] = [];

            foreach ($this->discounts as $discount) {
                /** @var Cartthrob_discount $discount */
                if (!in_array($discount->toArray(), $data['discounts'])) {
                    $data['discounts'][] = $discount->toArray();
                }
            }
        }

        if ($this->subclass()) {
            $data['class'] = $this->subclass();
        }

        if ($stripDefaults) {
            foreach ($this->defaults as $key => $value) {
                if (isset($data[$key]) && $data[$key] === $value) {
                    unset($data[$key]);
                }
            }
        }

        if (!empty($data['product_id']) && !isset($data['entry_id'])) {
            $data['entry_id'] = $data['product_id'];
        }

        return $data;
    }

    /**
     * sub_items
     *
     * for non-package items this is always FALSE
     *
     * @return bool|array array of sub-items, false if there are no sub-items
     */
    public function sub_items()
    {
        return false;
    }
}
