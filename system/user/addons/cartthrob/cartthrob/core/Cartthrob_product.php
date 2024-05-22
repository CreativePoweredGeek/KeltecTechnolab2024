<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cartthrob_product extends Cartthrob_child
{
    protected $product_id;
    protected $price;
    protected $weight;
    protected $shipping;
    protected $title;
    protected $item_options;
    protected $meta;
    protected $inventory;
    protected $categories;
    protected $entry_id;
    protected $url_title;

    protected $defaults = [
        'product_id' => null,
        'price' => 0,
        'weight' => 0,
        'shipping' => 0,
        'title' => '',
        'item_options' => [],
        'meta' => [],
        'inventory' => 0,
        'categories' => [],
    ];

    /**
     * @return mixed
     */
    public function categories()
    {
        return $this->categories;
    }

    /**
     * @param bool $key
     * @return bool|mixed
     */
    public function meta($key = false)
    {
        $this->core->hooks->set_hook('product_meta');

        if ($this->core->hooks->run($this, $key) && $this->core->hooks->end()) {
            return $this->core->hooks->value();
        }

        if ($key === false) {
            return $this->meta;
        }

        return (isset($this->meta[$key])) ? $this->meta[$key] : false;
    }

    /**
     * @return mixed
     */
    public function title()
    {
        return $this->title;
    }

    /**
     * @param int $quantity
     * @return $this
     */
    public function reduce_inventory($quantity = 1)
    {
        $this->core->hooks->set_hook('product_reduce_inventory');

        $args = func_get_args();
        array_shift($args);

        if ($this->core->hooks->run($this, $quantity, $args) && $this->core->hooks->end()) {
            return $this;
        }

        $this->inventory -= $quantity;

        return $this;
    }

    /**
     * @param array $item_options
     * @return bool
     */
    public function in_stock($item_options = [])
    {
        return $this->inventory($item_options) > 0;
    }

    /**
     * @param array $item_options
     * @return mixed
     */
    public function inventory($item_options = [])
    {
        $this->core->hooks->set_hook('product_inventory');

        if ($this->core->hooks->run($this, $item_options) && $this->core->hooks->end()) {
            return $this->core->hooks->value();
        }

        return $this->inventory;
    }

    /**
     * @param bool $key
     * @return bool
     */
    public function item_options($key = false)
    {
        if ($key === false) {
            return $this->item_options;
        }

        return (isset($this->item_options[$key])) ? $this->item_options[$key] : false;
    }

    /**
     * @param $data
     * @return $this
     */
    public function set_item_options($data)
    {
        if (is_array($data)) {
            $this->item_options = array_merge($this->item_options, $data);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function product_id()
    {
        return $this->product_id;
    }

    /**
     * Get the product price, (option) w/ modifiers
     *
     * @param array|Cartthrob_item|false $item a cart item object or an array of item_options
     *
     * @return float
     */
    public function price($item = false)
    {
        $price = $this->price;

        if ($this->core->hooks->set_hook('product_price')->run($this, $item)) {
            if ($this->core->hooks->end()) {
                return $this->core->hooks->value();
            }

            if (!is_null($this->core->hooks->value())) {
                $price = $this->core->hooks->value();
            }
        }

        if (!is_numeric($price)) {
            $price = 0;
        }

        $item_options = [];

        if ($item instanceof Cartthrob_item) {
            $item_options = $item->item_options();
        } else {
            if (is_array($item)) {
                $item_options = $item;
            }
        }

        foreach ($item_options as $key => $value) {
            if (!isset($this->item_options[$key])) {
                continue;
            }

            foreach ($this->item_options[$key] as $row) {
                if ($row['option_value'] === $value) {
                    $price += (float)$row['price'];
                    break;
                }
            }
        }

        return $price;
    }

    /**
     * @param bool $item
     * @return float
     */
    public function weight($item = false)
    {
        $weight = (float)$this->weight;

        $item_options = [];

        if ($item instanceof Cartthrob_item) {
            $item_options = $item->item_options();
        } else {
            if (is_array($item)) {
                $item_options = $item;
            }
        }

        // one of the above might turn item options into a string. oops. fix it here.
        if (!is_array($item_options)) {
            $item_options = [];
        }
        foreach ($item_options as $key => $value) {
            if (!isset($this->item_options[$key])) {
                continue;
            }

            foreach ($this->item_options[$key] as $row) {
                if ($row['option_value'] === $value) {
                    if (isset($row['weight'])) {
                        $weight += (float)$row['weight'];
                    }
                    break;
                }
            }
        }

        return $weight;
    }

    /**
     * @return string
     */
    public function shipping()
    {
        if ($this->core->hooks->set_hook('product_shipping_start')->run() && $this->core->hooks->end()) {
            $shipping = $this->core->hooks->value();
        } else {
            $shipping = $this->shipping;

            if ($this->core->hooks->set_hook('product_shipping_end')->run($shipping) && $this->core->hooks->end()) {
                $shipping = $this->core->hooks->value();
            }
        }

        return $this->core->round($shipping);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = [];

        foreach ($this->defaults as $key => $value) {
            if (in_array($key, ['item'])) {
                continue;
            }

            $data[$key] = $this->$key;
        }

        return $data;
    }
}
