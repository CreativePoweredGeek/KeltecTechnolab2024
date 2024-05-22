<?php

use CartThrob\Events\Event;

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

class Cartthrob_core_ee extends Cartthrob_core
{
    public $item_defaults = [
        'entry_id' => null,
        // 'expiration_date' => NULL,
        // 'license_number' => NULL
    ];

    public $product_defaults = [
        'entry_id' => null,
        'url_title' => null,
    ];

    public $hooks = [
        'cart_total_start',
        'cart_total_end',
        'cart_discount_start',
        'cart_tax_end',
        'cart_shipping_end',
        'product_reduce_inventory',
        'product_meta',
        'product_price',
        'product_inventory',
        'quantity_in_cart',
    ];

    private $cart_hash;

    /**
     * Cartthrob_core_ee constructor.
     */
    public function __construct()
    {
        ee()->load->model('cartthrob_settings_model');

        $this->config = &ee()->cartthrob_settings_model->get_settings();

        $this->customer_info_defaults = $this->config('customer_info_defaults');

        ee()->lang->loadfile('cartthrob_errors', 'cartthrob');

        ee()->lang->loadfile('cartthrob', 'cartthrob');
    }

    /**
     * @param null $args
     * @return array|bool|mixed
     */
    public function config($args = null)
    {
        $args = (is_array($args)) ? $args : func_get_args();

        // this shouldn't really ever happen, but this will pick it up from the cache
        if (!$args) {
            return ee()->cartthrob_settings_model->get_settings();
        }

        if (!$config_key = array_shift($args)) {
            return false;
        }

        $config = ee()->config->item('cartthrob:' . $config_key);

        foreach ($args as $key) {
            if (isset($config[$key])) {
                $config = $config[$key];
            } else {
                return false;
            }
        }

        return $config;
    }

    /**
     * @param $key
     * @param bool $value
     * @return $this|Cartthrob_core
     */
    public function set_config($key, $value = false)
    {
        ee()->cartthrob_settings_model->set_item($key, $value);

        return $this;
    }

    /**
     * @param $override_config
     * @return $this|void
     */
    public function override_config($override_config)
    {
        if (!is_array($override_config)) {
            return $this;
        }

        foreach ($override_config as $key => $value) {
            ee()->cartthrob_settings_model->set_item($key, $value);
        }

        return $this;
    }

    /**
     * @param $msg
     */
    public function log($msg)
    {
        ee()->load->model('log_model');
        ee()->log_model->log($msg);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function lang($key)
    {
        return ee()->lang->line($key);
    }

    /**
     * @return array
     */
    public function get_hooks()
    {
        return [
            'cart_total_start',
            'cart_total_end',
            'cart_discount_start',
            'cart_tax_end',
            'cart_shipping_end',
            'product_reduce_inventory',
            'product_meta',
            'product_price',
            'product_inventory',
            'quantity_in_cart',
        ];
    }

    /**
     * @param $entry_id
     * @return Cartthrob_child
     */
    public function get_product($entry_id)
    {
        ee()->load->model('product_model');

        $product = self::create_child($this, 'product', ee()->product_model->get_product($entry_id), $this->product_defaults);
        $product->set_item_options(ee()->product_model->get_all_price_modifiers($entry_id));

        return $product;
    }

    /**
     * @return array
     */
    public function get_categories()
    {
        ee()->load->model('product_model');

        $categories = [];

        foreach (ee()->product_model->get_categories() as $category) {
            $categories[$category['category_id']] = $category['category_name'];
        }

        return $categories;
    }

    public function action_complete()
    {
        $this->save_cart();

        if (ee()->input->is_ajax_request()) {
            $cart_info = $this->cart_info();

            $json_keys = [
                'cart_total',
                'cart_subtotal',
                'cart_discount',
                'cart_tax',
                'cart_shipping',
                'total_items',
                'total_unique_items',
            ];

            foreach ($json_keys as $key) {
                ee()->session->set_flashdata($key, $cart_info[$key]);
            }
        }
    }

    public function save_cart()
    {
        ee()->load->model('cart_model');

        $id = ee()->cart_model->update($this->cart->id(), $this->cart_array(), ee()->functions->fetch_current_uri());

        $this->cart->set_id($id);
    }

    /**
     * @return array
     */
    public function cart_array()
    {
        $cart = $this->cart->toArray();

        // let's strip the array of data that matches the default data
        // to minimize the size of the array before we save it
        foreach ($cart as $key => $value) {
            if ($value === $this->cart->defaults($key)) {
                unset($cart[$key]);
            }
        }

        if (isset($cart['items'])) {
            foreach ($cart['items'] as $row_id => $item) {
                foreach ($item as $key => $value) {
                    if ($value === $this->cart->item($row_id)->defaults($key)) {
                        unset($cart['items'][$row_id][$key]);
                    }
                }
            }
        }

        return $cart;
    }

    /**
     * @return array
     */
    public function cart_info(): array
    {
        ee()->load->library(['number']);

        return [
            'cart_id' => $this->cart->id(),
            'total_unique_items' => $this->cart->count(),
            'cart_tax_name' => $this->store->tax_name(),
            // this should really be set per item
            'total_items' => $this->cart->count_all(),
            'cart_subtotal' => ee()->number->format($this->cart->subtotal()),
            'cart_subtotal_plus_tax' => ee()->number->format($this->cart->subtotal_with_tax()),
            'cart_subtotal:plus_tax' => ee()->number->format($this->cart->subtotal_with_tax()),
            'cart_tax' => ee()->number->format($this->cart->tax()),
            'cart_shipping' => ee()->number->format($this->cart->shipping()),
            'cart_shipping_plus_tax' => ee()->number->format($this->cart->shipping_plus_tax()),
            'cart_shipping:plus_tax' => ee()->number->format($this->cart->shipping_plus_tax()),
            'cart_discount' => ee()->number->format($this->cart->discount()),
            'cart_total' => ee()->number->format($this->cart->total()),
            'cart_total:plus_tax' => ee()->number->format($this->cart->total()),
            // already includes tax, but what the hell.
            'cart_total_plus_tax' => ee()->number->format($this->cart->total()),
            // already includes tax, but what the hell.
            'cart_subtotal_numeric' => $this->cart->subtotal(),
            'cart_tax_numeric' => $this->cart->tax(),
            'cart_shipping_numeric' => $this->cart->shipping(),
            'cart_discount_numeric' => $this->cart->discount(),
            'cart_total_numeric' => $this->cart->total(),
            'cart_weight' => $this->cart->weight(),
            'cart_tax_rate' => $this->store->tax_rate(),
            // this should really be set per item
            'cart_entry_ids' => implode('|', $this->cart->product_ids()),
            'shipping_option' => $this->cart->shipping_info('shipping_option'),
        ];
    }

    /**
     * @return $this
     */
    public function process_inventory()
    {
        $inventory_reduce = [];

        foreach ($this->cart->items() as $item) {
            if ($item->product_id() && $product = $this->store->product($item->product_id())) {
                $product->reduce_inventory($item->quantity(), $item->item_options());
            }

            if ($item->sub_items()) {
                foreach ($item->sub_items() as $sub_item) {
                    if ($sub_item->product_id() && $product = $this->store->product($sub_item->product_id())) {
                        // we should make it possible to set the sub item quantity in the package select.
                        // right now it's not possible to set,
                        // but the default value is 1. So X * package quantity will correctly reduce inventory in either case.
                        $product->reduce_inventory($sub_item->quantity() * $item->quantity(),
                            $sub_item->item_options());
                    }
                }
            }

            if (is_array($item->meta('inventory_reduce'))) {
                foreach ($item->meta('inventory_reduce') as $entry_id => $quantity) {
                    $inventory_reduce[] = [
                        'entry_id' => $entry_id,
                        'quantity' => $quantity,
                    ];
                }
            }
        }

        foreach ($inventory_reduce as $row) {
            if ($product = $this->store->product($row['entry_id'])) {
                $product->reduce_inventory($row['quantity']);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function process_discounts()
    {
        loadCartThrobPath();

        ee()->load->model('discount_model');

        ee()->load->model('coupon_code_model');

        ee()->discount_model->process_discounts();

        ee()->coupon_code_model->process();

        return $this;
    }

    /**
     * @param $coupon_code
     * @return mixed
     */
    public function validate_coupon_code($coupon_code)
    {
        ee()->load->model('coupon_code_model');

        return ee()->coupon_code_model->validate($coupon_code);
    }

    /**
     * @param $coupon_code
     * @return mixed
     */
    public function get_coupon_code_data($coupon_code)
    {
        ee()->load->model('coupon_code_model');

        return ee()->coupon_code_model->get($coupon_code);
    }

    /**
     * @return mixed
     */
    public function get_discount_data()
    {
        ee()->load->model('discount_model');

        return ee()->discount_model->get_valid_discounts();
    }

    /**
     * @param $params
     */
    public function set_config_customer_info($params)
    {
        if (!empty($params['field']) && isset($params['value'])) {
            if (preg_match('/^customer_(.*)/', $params['field'], $match)) {
                $params['field'] = $match[1];
            }

            $this->cart->set_customer_info($params['field'], $params['value']);

            ee()->load->model(['member_model', 'customer_model']);

            if (ee()->session->userdata('member_id') && $this->store->config('save_member_data') && $field_id = $this->store->config('member_' . $params['field'] . '_field')) {
                if (is_numeric($field_id)) {
                    $update_member_d = ee('Model')->get('Member', ee()->session->userdata('member_id'))->first();
                    $update_member_d->set(['m_field_id_' . $field_id => $params['value']]);
                    $update_member_d->save();
                } else {
                    $update_member_d = ee('Model')->get('Member', ee()->session->userdata('member_id'))->first();
                    $update_member_d->set([$field_id => $params['value']]);
                    $update_member_d->save();
                }
            }
        }
    }

    /**
     * _set_config_shipping_plugin
     *
     * sets the selected shipping plugin
     *
     * @param string $params shipping parameter short_name (ie. by_weight_ups_xml)
     */
    public function set_config_shipping_plugin($params)
    {
        if (isset($params['value'])) {
            if (strpos($params['value'], 'shipping_') !== 0) {
                $params['value'] = 'shipping_' . $params['value'];
            }

            $this->cart->set_config('shipping_plugin', 'Cartthrob_' . $params['value']);

            $this->cart->shipping(true);
        }
    }

    /**
     * @param $params
     */
    public function set_config_price_field($params)
    {
        if (empty($params['field'])) {
            if (empty($params['value'])) {
                return;
            } else {
                $params['field'] = $params['value'];
            }
        }

        if (empty($params['channel_id']) && empty($params['channel'])) {
            return;
        }

        ee()->load->model('cartthrob_field_model');

        if (!($field_id = ee()->cartthrob_field_model->get_field_id($params['field']))) {
            return;
        }
        if (!empty($params['channel'])) {
            $params['channel_id'] = ee()->db->select('channel_id')->where('channel_name',
                $params['channel'])->get('channels')->row('channel_id');
        }
        $product_channel_fields = ($this->store->config('product_channel_fields')) ? $this->store->config('product_channel_fields') : [];

        $product_channel_fields[$params['channel_id']]['price'] = $field_id;

        $this->cart->set_config('product_channel_fields', $product_channel_fields);
    }

    public function save_customer_info()
    {
        ee()->load->library('locales');
        ee()->load->model(['cartthrob_members_model']);

        if (!isset($_POST['country_code'])) {
            if (ee()->input->post('country') && $country_code = ee()->locales->country_code(ee()->input->post('country'))) {
                $_POST['country_code'] = $country_code;
            }
        }
        ee()->cartthrob->cart->meta('checkout_as_member');

        // there is a member id AND the person using the member id is an admin. If you're not an admin... this is ignored.
        if (ee()->cartthrob->cart->meta('checkout_as_member') && in_array(ee()->session->userdata('role_id'),
            ee()->config->item('cartthrob:admin_checkout_groups'))) {
            $member_id = ee()->cartthrob->cart->meta('checkout_as_member');
        } elseif (ee()->session->userdata('member_id')) {
            $member_id = ee()->session->userdata('member_id');
        }

        $customer_info = $this->cart->customer_info();

        if (is_array($customer_info)) {
            foreach (array_keys($customer_info) as $fieldName) {
                if (ee()->input->post($fieldName) !== false) {
                    $this->cart->set_customer_info($fieldName, ee()->input->post($fieldName, true));

                    if ($this->shouldUseBillingAsShipping() && strpos($fieldName, 'shipping_') !== false) {
                        // we're going to get the data from the billing field
                        $billingField = str_replace('shipping_', '', $fieldName);
                        $this->cart->set_customer_info($fieldName, $this->cart->customer_info($billingField));
                    }
                }
            }
        }

        // moved the custom data setting above the member update to make sure we have fresh custom data for members
        if (($data = ee()->input->post('custom_data', true)) && is_array($data)) {
            foreach ($data as $key => $value) {
                $this->cart->set_custom_data($key, $value);
            }
        }

        if (isset($member_id)) {
            $manually_save_customer_info = false;
            if (ee()->input->post('save_member_data')) {
                $manually_save_customer_info = true;
            }

            ee()->cartthrob_members_model->update($member_id, $this->cart->customer_info(), $manually_save_customer_info);
        }

        ee()->load->library('languages');

        ee()->languages->set_language(ee()->input->post('language', true));

        if (ee()->input->post('shipping_option')) {
            $this->cart->set_shipping_info('shipping_option', ee()->input->post('shipping_option', true));
        }

        /**
         * @property array|bool $data
         */
        $data = ee()->input->post('shipping', true);

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $this->cart->set_shipping_info($key, $value);
            }
        }
    }

    /*
     * Hooks
     *
     * To use the hooks found in the Cartthrob_child objects, create a method
     * by prefixing the class short name and the hook name. For example, to
     * use the Cartthrob_cart class' add_item_end hook, add a method here
     * called cart_add_item_end.
     */

    /**
     * @return mixed
     */
    public function cart_total_start()
    {
        // cartthrob_calculate_total hook
        if (ee()->extensions->active_hook('cartthrob_calculate_total') === true) {
            if (($total = ee()->extensions->call('cartthrob_calculate_total')) !== false) {
                $this->hooks->set_end();

                return $total;
            }
        }
    }

    /**
     * @return mixed
     */
    public function cart_discount_start()
    {
        if (ee()->extensions->active_hook('cartthrob_calculate_discount') === true) {
            if (($discount = ee()->extensions->call('cartthrob_calculate_discount')) !== false) {
                $this->hooks->set_end();

                return $discount;
            }
        }
    }

    /**
     * @param $shipping
     * @return mixed
     */
    public function cart_shipping_end($shipping)
    {
        if (ee()->extensions->active_hook('cartthrob_calculate_shipping') === true) {
            $this->hooks->set_end();

            return ee()->extensions->call('cartthrob_calculate_shipping', $shipping);
        }
    }

    /**
     * @param $tax
     * @return mixed
     */
    public function cart_tax_end($tax)
    {
        if (ee()->extensions->active_hook('cartthrob_calculate_tax') === true) {
            $this->hooks->set_end();

            return ee()->extensions->call('cartthrob_calculate_tax', $tax);
        }
    }

    /**
     * @param $shipping
     * @return mixed
     */
    public function item_shipping_end($shipping)
    {
        if (ee()->extensions->active_hook('cartthrob_calculate_item_shipping') === true) {
            $this->hooks->set_end();

            return ee()->extensions->call('cartthrob_calculate_item_shipping', $shipping);
        }
    }

    /**
     * @param Cartthrob_product $product
     * @param $key
     * @return mixed
     */
    public function product_meta(Cartthrob_product $product, $key)
    {
        ee()->load->model(['cartthrob_field_model', 'product_model']);

        $data = ee()->product_model->get_product($product->product_id());

        if ($key === false) {
            $this->hooks->set_end();

            return $data;
        }

        if (isset($data[$key])) {
            $this->hooks->set_end();

            return $data[$key];
        }

        $field_id = ee()->cartthrob_field_model->get_field_id($key);

        if ($field_id && isset($data['field_id_' . $field_id])) {
            $this->hooks->set_end();

            return $data['field_id_' . $field_id];
        }
    }

    /**
     * @param Cartthrob_product $product
     * @param bool $item
     * @return array|bool|int|mixed
     */
    public function product_price(Cartthrob_product $product, $item = false)
    {
        ee()->load->model(['cartthrob_field_model', 'product_model']);

        $data = ee()->product_model->get_product($product->product_id());

        if ($channel_id = element('channel_id', $data)) {
            $global_price = $this->store->config('product_channel_fields', $channel_id, 'global_price');

            if ($global_price !== false && $global_price !== '') {
                $this->hooks->set_end();

                return $global_price;
            }

            if ($item instanceof Cartthrob_item) {
                $field_id = $this->store->config('product_channel_fields', $channel_id, 'price');

                if ($field_id && $field_type = ee()->cartthrob_field_model->get_field_type($field_id)) {
                    ee()->load->library('api');

                    ee()->legacy_api->instantiate('channel_fields');

                    ee()->api_channel_fields->include_handler($field_type);

                    if (ee()->api_channel_fields->setup_handler($field_type) && ee()->api_channel_fields->check_method_exists('cartthrob_price')) {
                        $field = ee()->api_channel_fields->setup_handler($field_type, true);
                        $field->row = $data;

                        $price = ee()->api_channel_fields->apply('cartthrob_price', [$data['field_id_' . $field_id], $item]);

                        if (is_numeric($price)) {
                            return $price;
                        } else {
                            return 0;
                        }
                    } // matrix always returns 1 if there's content in the matrix field. if the matrix field is set as a price field and there's content in it, it'll always add $1 to the price.
                    elseif ($field_type == 'matrix') {
                        return 0;
                    }
                }
            }
        }
    }

    /**
     * @param Cartthrob_product $product
     * @param $item_options
     * @return bool|int
     */
    public function product_inventory(Cartthrob_product $product, $item_options)
    {
        $this->hooks->set_end();

        if (ee()->extensions->active_hook('cartthrob_calculate_inventory') === true) {
            return ee()->extensions->call('cartthrob_calculate_inventory', $product, $item_options);
        }

        $hash = md5($product->product_id() . serialize($item_options));

        if (false !== ($inventory = $this->cache($hash))) {
            return $inventory;
        }

        $inventory = PHP_INT_MAX;

        ee()->load->model(['cartthrob_field_model', 'product_model']);

        $data = ee()->product_model->get_product($product->product_id());

        $channel_id = element('channel_id', $data);

        if ($channel_id && $field_id = $this->store->config('product_channel_fields', $channel_id, 'inventory')) {
            $field_name = ee()->cartthrob_field_model->get_field_name($field_id);

            $field_type = ee()->cartthrob_field_model->get_field_type($field_id);

            if ($this->isModifier($field_type)) {
                $price_modifiers = ee()->product_model->get_price_modifiers($product->product_id(), $field_id);

                if (isset($item_options[$field_name])) {
                    foreach ($price_modifiers as $row) {
                        if ($item_options[$field_name] == $row['option_value']) {
                            if (array_key_exists('inventory', $row)) {
                                // do not use this. it makes it FALSE when it needs to be 0
                                // $inventory = element('inventory', $row);

                                $inventory = false;
                                if ($row['inventory'] === 0 || $row['inventory'] === '0') {
                                    $inventory = 0;
                                } elseif ($row['inventory'] === false || $row['inventory'] === null || $row['inventory'] === '') {
                                    $inventory = false;
                                } else {
                                    $inventory = $row['inventory'];
                                }
                            }

                            continue;
                        }
                    }
                }
            } else {
                $inventory = element('field_id_' . $field_id, $data);

                if ($inventory === 0 || $inventory === '0') {
                    $inventory = 0;
                } elseif ($inventory === false || $inventory === null || $inventory === '') {
                    $inventory = false;
                }
            }
        }

        if ($inventory === false || $inventory === '') {
            $inventory = PHP_INT_MAX;
        }

        $this->set_cache($hash, $inventory);

        return $inventory;
    }

    /**
     * return the total number of items in the cart that match the item
     *
     * @param Cartthrob_item $item
     *
     * @return int
     */
    public function quantity_in_cart(Cartthrob_item $item)
    {
        ee()->load->model(['cartthrob_field_model', 'product_model']);

        $product_id = $item->product_id();
        $item_options = $item->item_options();
        $sub_items = element('sub_items', $item->toArray());
        $channel_id = $item->meta('channel_id');
        $items = null;

        if ($channel_id && $field_id = $this->store->config('product_channel_fields', $channel_id, 'inventory')) {
            $field_name = ee()->cartthrob_field_model->get_field_name($field_id);

            $field_type = ee()->cartthrob_field_model->get_field_type($field_id);

            if ($this->isModifier($field_type) && isset($item_options[$field_name])) {
                $items = $this->cart->filter_items([
                    'product_id' => $product_id,
                    'item_options' => [$field_name => $item_options[$field_name]],
                ], true);
            }

            if ($field_type === 'cartthrob_package') {
                $items = $this->cart->filter_items(['product_id' => $product_id, 'sub_items' => $sub_items]);
            }
        }

        if (is_null($items)) {
            $items = $this->cart->filter_items(['product_id' => $product_id], true);
        }

        $quantity = 0;

        foreach ($items as $item) {
            $quantity += ($item->is_sub_item()) ? $item->quantity() * $item->parent_item()->quantity() : $item->quantity();
        }

        return $quantity;
    }

    /**
     * @param Cartthrob_product $product
     * @param $quantity
     * @param $args
     */
    public function product_reduce_inventory(Cartthrob_product $product, $quantity, $args)
    {
        // because of the way hooks work,
        // and how we call this in the process_inventory method above
        // item_options are the first arg in args
        $item_options = (isset($args[0])) ? $args[0] : [];

        ee()->load->model('product_model');

        $inventory = ee()->product_model->reduce_inventory($product->product_id(), $quantity, $item_options);

        if ($inventory !== false && $this->store->config('send_inventory_email')) {
            if ($inventory <= $this->store->config('low_stock_level')) {
                $data = ['entry_id' => $product->product_id(), 'inventory' => $inventory];
                ee('cartthrob:NotificationsService')->dispatch(Event::TYPE_LOW_STOCK, $data);
            }
        }

        $this->hooks->set_end();
    }

    /**
     * Custom Methods
     *
     * Create custom methods for the Cartthrob_child objects by prefixing the
     * class short name. For example, to create an Cartthrob_item::entry_id()
     * method, add a method here called item_entry_id().
     *
     * Please use sparingly as __call and call_user_func_array are expensive.
     */

    /**
     * @param Cartthrob_item $item
     * @return int
     */
    public function item_entry_id(Cartthrob_item $item)
    {
        return $item->product_id();
    }

    /**
     * @param Cartthrob_item $item
     * @return int
     */
    public function item_product_entry_id(Cartthrob_item $item)
    {
        return $item->product_id();
    }

    /**
     * @param $location_data
     * @param int $limit
     * @param string $order_by
     * @return mixed
     */
    public function get_tax_rates($location_data, $limit = 100, $order_by = 'id')
    {
        ee()->load->model('tax_model');
        $taxes = ee()->tax_model->get_by_location($location_data, $limit, $order_by);

        return $taxes;
    }

    /**
     * Check if the options to copy billing info into shipping is set
     *
     * @param bool $checkPost Should we check the current post variables or just existing customer info
     * @return bool
     */
    private function shouldUseBillingAsShipping($checkPost = true): bool
    {
        return bool_string($this->cart->customer_info('use_billing_info')) ||
            ($checkPost && bool_string(ee()->input->post('use_billing_info')));
    }

    /**
     * @param string $fieldType
     * @return bool
     */
    private function isModifier(string $fieldType = ''): bool
    {
        return in_array($fieldType, ['cartthrob_price_modifiers', 'matrix', 'grid']) || strncmp($fieldType,
            'cartthrob_price_modifiers', 25) === 0;
    }
}
