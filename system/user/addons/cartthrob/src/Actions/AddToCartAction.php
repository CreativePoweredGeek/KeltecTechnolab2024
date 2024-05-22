<?php

namespace CartThrob\Actions;

use CartThrob\CleansSubscriptionData;
use CartThrob\Math\Number;
use CartThrob\Request\Request;
use Cartthrob_item;
use EE_Session;

class AddToCartAction extends Action
{
    use CleansSubscriptionData;

    public function __construct(EE_Session $session, Request $request)
    {
        parent::__construct($session, $request);

        ee()->load->library(['api', 'cartthrob_file']);
        ee()->load->model(['cartthrob_field_model', 'product_model', 'subscription_model']);
    }

    public function process()
    {
        // cartthrob_add_to_cart_start hook
        if (ee()->extensions->active_hook('cartthrob_add_to_cart_start') === true) {
            ee()->extensions->call('cartthrob_add_to_cart_start');
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        if (!ee()->form_builder->validate()) {
            $this->setGlobalValues();
            ee()->form_builder->set_value([
                'item_options',
                'quantity',
                'title',
            ]);

            return ee()->form_builder->action_complete();
        }

        ee()->cartthrob->save_customer_info();

        $data = [
            'entry_id' => $this->request->input('entry_id'),
            'class' => 'product',
        ];

        $item_options = $this->request->input('item_options');

        if ($item_options && is_array($item_options)) {
            $configuration = [];
            $fields_list = [];

            foreach ($item_options as $key => $value) {
                if (strpos($key, 'configuration:') !== false) {
                    list(, $field, $option_group) = explode(':', $key);
                    $fields_list[] = $field;
                    $configuration[$field][$option_group] = $value;
                    $data['item_options'][$key] = $value;
                }
            }

            if (!empty($configuration)) {
                $fields_list = array_filter($fields_list);

                foreach ($fields_list as $field_name) {
                    if ($entry = ee()->product_model->get_product($data['entry_id'])) {
                        if ($sku = ee()->product_model->get_base_variation($data['entry_id'], $field, $configuration[$field_name])) {
                            $item_options[$field_name] = $sku;
                            $inventory = ee()->product_model->check_inventory($data['entry_id'], $field, $sku);

                            if ($inventory !== false && $inventory <= 0) {
                                ee()->form_builder
                                    ->set_errors([sprintf(lang('configuration_not_in_stock'), $entry['title'])])
                                    ->set_success_callback([ee()->cartthrob, 'action_complete'])
                                    ->action_complete();
                            }
                        }
                    }
                }
            }

            // don't grab numeric item_options, those are for sub_items
            foreach ($item_options as $key => $value) {
                if (strpos($key, ':') === false) {
                    $data['item_options'][$key] = $value;
                }
            }
        }

        // normally all of this data would be handled as part of the subs options, but we
        // have to mess around and get a price in the cart anyway, so we'll go ahead and look into it.
        if ($this->request->has('subscription_plan_id') || $this->request->has('PI')) {
            // look to see if this was overridden by customer, and then look for the parameter
            if (!$plan_id = $this->request->decode('subscription_plan_id')) {
                $plan_id = $this->request->decode('PI');
            }

            $plan_data = ee()->subscription_model->get_plan($plan_id);

            if (isset($plan_data['permissions'])) {
                $perms = @unserialize(base64_decode($plan_data['permissions']));

                if (is_array($perms)) {
                    $data['permissions'] = $plan_data['permissions'] = implode('|', $perms);
                } else {
                    $data['permissions'] = $plan_data['permissions'] = $perms;
                }
            }

            if (!empty($plan_data['trial_price']) || $plan_data['trial_price'] === '0') {
                $data['price'] = $plan_data['trial_price'];
            } elseif (!empty($plan_data['price'])) {
                $data['price'] = $plan_data['price'];
            }

            if (isset($plan_data['name'])) {
                $data['title'] = $plan_data['name'];
            }
        }

        if ($this->request->decode('AUP', false, true) && !is_null($this->request->input('price'))) {
            $data['price'] = abs(Number::sanitize($this->request->input('price')));
        }

        if ($this->request->has('PR')) {
            $price = $this->request->decode('PR');

            if ($price == abs(Number::sanitize($price))) {
                $data['price'] = $price;
            }
        }

        if ($this->request->has('WGT')) {
            $weight = $this->request->decode('WGT');

            if ($weight == abs(Number::sanitize($weight))) {
                $data['weight'] = $weight;
            }
        } elseif ($this->request->decode('AUW', false, true) && !is_null($this->request->input('weight'))) {
            $data['weight'] = $this->request->input('weight');
        }

        if ($this->request->has('SHP')) {
            $shipping = $this->request->decode('SHP');

            if ($shipping == abs(Number::sanitize($shipping))) {
                $data['shipping'] = $shipping;
            }
        } elseif ($this->request->decode('AUS', false, true) && !is_null($this->request->input('shipping'))) {
            $data['shipping'] = $this->request->input('shipping');
        }

        if ($this->request->has('NSH')) {
            $data['no_shipping'] = $this->request->decode('NSH', false, true);
        }

        if ($this->request->has('NTX')) {
            $data['no_tax'] = $this->request->decode('NTX', false, true);
        }

        $data['product_id'] = $data['entry_id'];
        $data['quantity'] = !empty($this->request->input('quantity')) ? $this->request->input('quantity') : '1';

        if ($this->request->has('title')) {
            $data['title'] = $this->request->input('title');
        }

        if (!empty($_FILES['userfile'])) {
            $directory = null;

            if ($this->request->has('UPL')) {
                $directory = $this->request->decode('UPL');
            }

            $file_data = ee()->cartthrob_file->upload($directory);

            if (ee()->cartthrob_file->errors()) {
                ee()->form_builder->set_errors(ee()->cartthrob_file->errors())->action_complete();
            } else {
                $data['item_options']['upload'] = $file_data['file_name'];
                $data['item_options']['upload_directory'] = $file_data['file_path'];
            }
        }

        if (!$isOnTheFly = $this->request->decode('OTF', false, true)) {
            if ($this->request->has('title')) {
                $data['title'] = $this->request->input('title');
            }

            $data['site_id'] = 1;

            if ($entry = ee()->product_model->get_product($data['entry_id'])) {
                if (isset($entry['site_id'])) {
                    $data['site_id'] = $entry['site_id'];
                }

                $field_id = ee()->cartthrob_field_model->channel_has_fieldtype($entry['channel_id'], 'cartthrob_package', true);

                if ($field_id && !empty($entry['field_id_' . $field_id])) {
                    // it's a package
                    $data['class'] = 'package';

                    ee()->legacy_api->instantiate('channel_fields');

                    if (empty(ee()->api_channel_fields->field_types)) {
                        ee()->api_channel_fields->fetch_installed_fieldtypes();
                    }

                    $data['sub_items'] = [];

                    if (ee()->api_channel_fields->setup_handler('cartthrob_package')) {
                        $field_data = ee()->api_channel_fields->apply('pre_process', [$entry['field_id_' . $field_id]]);

                        foreach ($field_data as $row_id => $row) {
                            $item = [
                                'entry_id' => $row['entry_id'],
                                'product_id' => $row['entry_id'],
                                'row_id' => $row_id,
                                'class' => 'product',
                                'site_id' => $data['site_id'],
                                // assuming it has to be from the same site id as the parent based on EE's structure
                            ];

                            $item['item_options'] = (isset($row['option_presets'])) ? $row['option_presets'] : [];

                            $row_item_options = [];

                            if (isset($_POST['item_options'][$row_id])) {
                                $row_item_options = $_POST['item_options'][$row_id];
                            } else {
                                if (isset($_POST['item_options'][$data['entry_id'] . ':' . $row_id . ':'])) {
                                    $row_item_options = $_POST['item_options'][$data['entry_id'] . ':' . $row_id . ':'];
                                } else {
                                    if (isset($_POST['item_options'][':' . $row_id])) {
                                        $row_item_options = $_POST['item_options'][':' . $row_id];
                                    }
                                }
                            }

                            $price_modifiers = ee()->product_model->get_all_price_modifiers($row['entry_id']);

                            foreach ($row_item_options as $key => $value) {
                                // if it's not a price modifier (ie, an on-the-fly item option), add it
                                // if it is a price modifier, check that it's been allowed before adding
                                if (!isset($price_modifiers[$key]) || !empty($row['allow_selection'][$key])) {
                                    $item['item_options'][$key] = ee('Security/XSS')->clean($value);
                                }
                            }

                            $data['sub_items'][$row_id] = $item;
                        }
                    }
                }

                $field_id = ee()->cartthrob_field_model->channel_has_fieldtype($entry['channel_id'], 'cartthrob_subscriptions', true);

                if ($field_id && !empty($entry['field_id_' . $field_id]) && !isset($plan_id)) {
                    // it's a subscription product, let's set the price
                    $item_subscription_options = _unserialize($entry['field_id_' . $field_id], true);
                    if (isset($item_subscription_options['subscription_enabled']) && $item_subscription_options['subscription_enabled'] == true) {
                        if ((isset($item_subscription_options['subscription_trial_occurrences']) && $item_subscription_options['subscription_trial_occurrences'] > 0) && isset($item_subscription_options['subscription_trial_price'])) {
                            $data['price'] = abs(Number::sanitize($item_subscription_options['subscription_trial_price']));
                        } else {
                            $data['price'] = abs(Number::sanitize($item_subscription_options['subscription_price']));
                        }
                    }
                }
            }
        } elseif (isset($data['class'])) {
            unset($data['class']);
        }

        loadCartThrobPath();

        // if a class has been assigned to the item.
        if ($this->request->has('CLS')) {
            $data['class'] = $this->request->decode('CLS');
        }

        $original_last_row_id = (ee()->cartthrob->cart->items()) ? ee()->cartthrob->cart->last_row_id() : -1;

        if (isset($data['quantity']) && $data['quantity'] !== 0) {
            /** @var Cartthrob_item $item */
            if ($item = ee()->cartthrob->cart->add_item($data)) {
                if (isset($configuration)) {
                    $item->set_meta('configuration', $configuration);
                }

                if ($item->product_id() && $field_id = ee()->cartthrob_field_model->channel_has_fieldtype($item->meta('channel_id'), 'cartthrob_subscriptions', true)) {
                    $item_subscription_options = _unserialize($item->meta('field_id_' . $field_id), true);
                }

                if (isset($plan_id)) {
                    if (isset($plan_data)) {
                        // this crazy thing creates a function to add a prefix to each array key.
                        $item_subscription_options = array_combine(
                            array_map(function ($key) { return 'subscription_' . $key; }, array_keys($plan_data)),
                            $plan_data
                        );
                    }

                    $item_subscription_options['subscription_enabled'] = true;

                    $item->set_meta('plan_id', $plan_id);
                }

                // set after plan so that plan permissions can be added.
                if ($this->request->has('PER')) {
                    $item->set_meta('permissions', $this->request->decode('PER'));
                } elseif (isset($item_subscription_options['subscription_permissions'])) {
                    $item->set_meta('permissions', $item_subscription_options['subscription_permissions']);
                }

                if (!is_null($this->request->input('LIC')) && $this->request->decode('LIC', null, true)) {
                    $new_last_row_id = (ee()->cartthrob->cart->items()) ? ee()->cartthrob->cart->last_row_id() : -1;

                    for ($i = $original_last_row_id; $i <= $new_last_row_id; $i++) {
                        /** @var Cartthrob_item $_item */
                        if ($i < 0 || !$_item = ee()->cartthrob->cart->item($i)) {
                            continue;
                        }

                        if (isset($data['class']) && $data['class'] === 'package') {
                            /** @var Cartthrob_item $sub_item */
                            foreach ($_item->sub_items() as $sub_item) {
                                $sub_item->set_meta('license_number', true);
                            }
                        } else {
                            $_item->set_meta('license_number', true);
                        }
                    }
                }

                $sub = $this->clean_sub_data(isset($item_subscription_options) ? $item_subscription_options : null);

                if ($sub !== false) {
                    // adding subscription meta. even if there's no new info, we still want the subscription meta set
                    $item->set_meta('subscription_options', $sub);
                    $item->set_meta('subscription', true);

                    if (!$item->title() && isset($sub['name'])) {
                        $item->set_title($sub['name']);
                    }
                }

                if ($this->request->has('EXP')) {
                    $expires = $this->request->decode('EXP');

                    if ($expires == abs(Number::sanitize($expires))) {
                        $new_last_row_id = (ee()->cartthrob->cart->items()) ? ee()->cartthrob->cart->last_row_id() : -1;

                        for ($i = $original_last_row_id; $i <= $new_last_row_id; $i++) {
                            if ($i < 0 || !$_item = ee()->cartthrob->cart->item($i)) {
                                continue;
                            }

                            if ($data['class'] === 'package') {
                                foreach ($_item->sub_items() as $sub_item) {
                                    $sub_item->set_meta('expires', $expires);
                                }
                            } else {
                                $_item->set_meta('expires', $expires);
                            }
                        }
                    }
                }

                if ($this->request->has('inventory_reduce')) {
                    $item->set_meta('inventory_reduce', $this->request->input('inventory_reduce'));
                }
            }

            if ($this->request->has('MET')) {
                $meta = @unserialize(base64_decode($this->request->decode('MET')));

                if ($meta && is_array($meta)) {
                    // don't grab numeric item_options, those are for sub_items
                    foreach ($meta as $key => $value) {
                        if (strpos($key, ':') === false) {
                            // don't want to override any existing meta that has been set already
                            if (!$item->meta($key)) {
                                $item->set_meta($key, $value);
                            }
                        }
                    }
                }
            }

            // cartthrob_add_to_cart_end hook
            if (ee()->extensions->active_hook('cartthrob_add_to_cart_end') === true) {
                ee()->extensions->call('cartthrob_add_to_cart_end', $item);
                if (ee()->extensions->end_script === true) {
                    return;
                }
            }
        }

        // if they're using inline stuff we wanna clear the added item upon error
        if ($this->request->input('error_handling') === 'inline' && $item) {
            ee()->form_builder->set_error_callback([ee()->cartthrob->cart, 'remove_item', $item->row_id()]);
        }

        ee()->form_builder
            ->set_errors(ee()->cartthrob->errors())
            ->set_success_callback([ee()->cartthrob, 'action_complete'])
            ->action_complete();
    }
}
