<?php

namespace CartThrob\Actions;

use CartThrob\Request\Request;
use Cartthrob_item;
use EE_Session;

class MultiAddToCartAction extends Action
{
    public function __construct(EE_Session $session, Request $request)
    {
        parent::__construct($session, $request);

        ee()->load->library('api');
        ee()->load->model(['product_model', 'cartthrob_field_model']);
    }

    /**
     * NOTE: multi add to cart does not work with configured items
     */
    public function process()
    {
        if (ee()->extensions->active_hook('cartthrob_multi_add_to_cart_start') === true) {
            ee()->extensions->call('cartthrob_multi_add_to_cart_start');
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        if (!ee()->form_builder->validate()) {
            return ee()->form_builder->action_complete();
        }

        ee()->cartthrob->save_customer_info();

        $entry_ids = $this->request->input('entry_id');
        $items = [];

        if (is_array($entry_ids)) {
            $on_the_fly = $this->request->decode('OTF', false, true);
            $allow_user_price = $this->request->decode('AUP', false, true);
            $allow_user_shipping = $this->request->decode('AUS', false, true);
            $allow_user_weight = $this->request->decode('AUW', false, true);
            $class = null;

            // if a class has been assigned to the item.
            if ($this->request->has('CLS')) {
                $class = $this->request->decode('CLS');
            }

            foreach ($entry_ids as $row_count => $entry_id) {
                $quantity = ee('Security/XSS')->clean(array_value($_POST, 'quantity', $row_count));

                if (!is_numeric($quantity) || $quantity <= 0) {
                    continue;
                }

                $data = [
                    'entry_id' => ee('Security/XSS')->clean(array_value($_POST, 'entry_id', $row_count)),
                    'quantity' => $quantity,
                ];

                if ($this->request->has('NSH')) {
                    $data['no_shipping'] = $this->request->decode('NSH');
                } elseif ($value = array_value($_POST, 'pNSH', $row_count)) {
                    $data['no_shipping'] = ee('cartthrob:EncryptionService')->decode($value);
                }

                if ($this->request->has('NTX')) {
                    $data['no_tax'] = $this->request->decode('NTX');
                } elseif ($value = array_value($_POST, 'pNTX', $row_count)) {
                    $data['no_tax'] = ee('cartthrob:EncryptionService')->decode($value);
                }

                if (($allow_user_price || $on_the_fly) && ($value = array_value($_POST, 'price', $row_count)) !== false) {
                    $data['price'] = ee('Security/XSS')->clean($value);
                }

                if (($allow_user_weight || $on_the_fly) && ($value = array_value($_POST, 'weight', $row_count)) !== false) {
                    $data['weight'] = ee('Security/XSS')->clean($value);
                }

                if (($allow_user_shipping || $on_the_fly) && ($value = array_value($_POST, 'shipping', $row_count)) !== false) {
                    $data['shipping'] = ee('Security/XSS')->clean($value);
                }

                if ($value = array_value($_POST, 'title', $row_count)) {
                    $data['title'] = ee('Security/XSS')->clean($value);
                }

                if (!$on_the_fly) {
                    $data['class'] = 'product';
                }

                if ($class) {
                    $data['class'] = $class;
                }

                $data['site_id'] = 1;
                $item_options = [];

                if ($value = array_value($_POST, 'item_options', $row_count)) {
                    $item_options = ee('Security/XSS')->clean($value);
                }

                // don't grab numeric item_options, those are for sub_items
                foreach ($item_options as $key => $value) {
                    if (strpos($key, ':') === false) {
                        $data['item_options'][$key] = $value;
                    }
                }

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

                                $item['item_options'] = $row['option_presets'] ?? [];
                                $row_item_options = [];

                                if (isset($_POST['item_options'][$row_count])) {
                                    $row_item_options = $_POST['item_options'][$row_count];
                                } else {
                                    if (isset($_POST['item_options'][$data['entry_id'] . ':' . $row_id . ':' . $row_count])) {
                                        $row_item_options = $_POST['item_options'][$data['entry_id'] . ':' . $row_id . ':' . $row_count];
                                    } else {
                                        if (isset($_POST['item_options'][':' . $row_id . ':' . $row_count])) {
                                            $row_item_options = $_POST['item_options'][':' . $row_id . ':' . $row_count];
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
                }

                $data['product_id'] = $data['entry_id'];

                /** @var Cartthrob_item $item */
                $item = ee()->cartthrob->cart->add_item($data);

                if ($this->request->has('PER')) {
                    $item->set_meta('permissions', $this->request->decode('PER'));
                }

                if ($item && $value = array_value($_POST, 'license_number', $row_count)) {
                    $item->set_meta('license_number', true);
                }
            }

            $items[$entry_id] = $item;
        }

        ee()->cartthrob->cart->check_inventory();

        if (ee()->extensions->active_hook('cartthrob_multi_add_to_cart_end') === true) {
            ee()->extensions->call('cartthrob_multi_add_to_cart_end', $entry_ids, $items);
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        ee()->form_builder
            ->set_errors(ee()->cartthrob->errors())
            ->set_success_callback([ee()->cartthrob, 'action_complete'])
            ->action_complete();
    }
}
