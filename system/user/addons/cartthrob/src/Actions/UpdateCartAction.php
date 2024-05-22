<?php

namespace CartThrob\Actions;

use CartThrob\CleansSubscriptionData;
use CartThrob\Dependency\Illuminate\Support\Arr;
use CartThrob\Request\Request;
use Cartthrob_item;
use EE_Session;

class UpdateCartAction extends Action
{
    use CleansSubscriptionData;

    public function __construct(EE_Session $session, Request $request)
    {
        parent::__construct($session, $request);

        ee()->load->library(['api']);
        ee()->load->model(['cartthrob_field_model', 'product_model', 'subscription_model']);
        ee()->load->helper('array');
    }

    /**
     * Handles submissions from the update_cart_form redirects on completion
     */
    public function process()
    {
        if (ee()->extensions->active_hook('cartthrob_update_cart_start') === true) {
            ee()->extensions->call('cartthrob_update_cart_start');
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        if (!ee()->form_builder->validate()) {
            $this->setGlobalValues();
            ee()->form_builder->set_value(['clear_cart']);

            return ee()->form_builder->action_complete();
        }

        ee()->cartthrob->save_customer_info();

        if ($this->request->has('clear_cart')) {
            ee()->cartthrob->cart
                ->clear_items()
                ->save();
        } else {
            $this->updateCartItems();
        }

        if (ee()->extensions->active_hook('cartthrob_update_cart_end') === true) {
            ee()->extensions->call('cartthrob_update_cart_end');
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        if ($this->request->input('coupon_code', '') != '') {
            ee()->cartthrob->cart->add_coupon_code($this->request->input('coupon_code'));
        }

        ee()->cartthrob->cart->check_inventory();

        ee()->form_builder
            ->set_errors(ee()->cartthrob->errors())
            ->set_success_callback([ee()->cartthrob, 'action_complete'])
            ->action_complete();
    }

    private function updateCartItems(): void
    {
        /** @var Cartthrob_item $item */
        foreach (ee()->cartthrob->cart->items() as $row_id => $item) {
            if (Arr::has($_POST, "delete.{$row_id}")) {
                $_POST['quantity'][$row_id] = 0;
            }

            $data = [];
            $defaultItemKeys = $item->default_keys();

            foreach ($_POST as $key => $value) {
                if ($item->sub_items()) {
                    $value = $this->processSubItems($item, $value, $row_id, $key);
                }

                if ($this->rowValueUpdatesDefaultItemKey($value, $row_id, $key, $defaultItemKeys)) {
                    $item_options = [];

                    if ($key == 'item_options') {
                        $configuration = [];
                        $configuration_meta = null;
                        $set_configuration_data = [];
                        $fields_list = [];
                        $arr = $value[$row_id];

                        foreach ($arr as $k => $v) {
                            if (strpos($k, 'configuration:') !== false) {
                                list(, $field, $option_group) = explode(':', $k);
                                $fields_list[] = $field;
                                $configuration[$field][$option_group] = $v;
                                unset($value[$row_id][$k]);
                            }
                        }

                        if (!empty($configuration)) {
                            $fields_list = array_filter($fields_list);

                            foreach ($fields_list as $field_name) {
                                $entry = ee()->product_model->get_product($item->product_id());
                                $sku = ee()->product_model->get_base_variation($item->product_id(), $field, $configuration[$field_name]);

                                if (!$entry || !$sku) {
                                    continue;
                                }

                                $item_options[$field_name] = $sku;
                                $quantity = (isset($_POST['quantity'][$row_id]) ? $_POST['quantity'][$row_id] : 1);
                                $inventory = ee()->product_model->check_inventory($item->product_id(), $quantity, [$field_name => $sku]);

                                if ($inventory !== false && $inventory <= 0) {
                                    $title = $this->request->input('title');

                                    if (!$title) {
                                        $title = lang('item_title_placeholder');
                                    }

                                    ee()->form_builder
                                        ->set_errors([sprintf(lang('configuration_not_in_stock'), $title)])
                                        ->set_success_callback([ee()->cartthrob, 'action_complete'])
                                        ->action_complete();
                                }
                            }
                        }
                    }

                    $data[$key] = ee('Security/XSS')->clean($value[$row_id]);

                    if (isset($item_options) && !empty($item_options) && is_array($data[$key])) {
                        $data[$key] = array_merge($data[$key], $item_options);

                        $configuration_meta = $item->meta('configuration');
                        if ($configuration_meta && isset($configuration)) {
                            $set_configuration_data = [];
                            foreach ($configuration_meta as $b => $c) {
                                if (array_key_exists($b, $configuration)) {
                                    $set_configuration_data[$b] = array_merge($c, $configuration[$b]);
                                }
                            }
                        }
                        if (isset($set_configuration_data) && is_array($set_configuration_data)) {
                            $item->set_meta('configuration', $set_configuration_data);
                        }
                    }
                }

                if (isset($value[$row_id]) && $key === 'subscription') {
                    $item->set_meta('subscription', bool_string($value[$row_id]));
                }
            }

            $sub = $this->clean_sub_data((array)$item->meta('subscription_options'), $update = true);
            if ($sub !== false) {
                // adding subscription meta. even if there's no new info, we still want the subscription meta set
                $item->set_meta('subscription_options', $sub);
            }

            if ($data) {
                $item->update($data);
            }
        }
    }

    /**
     * @param Cartthrob_item $item
     * @param $value
     * @param int $rowId
     * @param int $key
     * @return mixed
     */
    private function processSubItems(Cartthrob_item $item, $value, int $rowId, int $key)
    {
        foreach ($item->sub_items() as $subItem) {
            if (isset($value[$rowId . ':' . $subItem->row_id()]) && in_array($key, $subItem->default_keys())) {
                $_value = $value[$rowId . ':' . $subItem->row_id()];

                ee()->legacy_api->instantiate('channel_fields');

                if (empty(ee()->api_channel_fields->field_types)) {
                    ee()->api_channel_fields->fetch_installed_fieldtypes();
                }

                if ($key === 'item_options' && ee()->api_channel_fields->setup_handler('cartthrob_package')) {
                    $fieldId = ee()->cartthrob_field_model->channel_has_fieldtype($item->meta('channel_id'), 'cartthrob_package', true);
                    $fieldData = ee()->api_channel_fields->apply('pre_process', [$item->meta('field_id_' . $fieldId)]);

                    loadCartThrobPath();

                    foreach ($fieldData as $row) {
                        if (isset($row['allow_selection'])) {
                            foreach ($row['allow_selection'] as $zkey => $allowed) {
                                if (!$allowed && isset($_value[$zkey])) {
                                    unset($_value[$zkey]);
                                }
                            }
                        }
                    }
                }

                $subItem->update([$key => ee('Security/XSS')->clean($_value)]);
            }
        }

        return $value;
    }

    /**
     * @param $value
     * @param int $rowId
     * @param mixed $key
     * @param array $defaultItemKeys
     * @return bool
     */
    private function rowValueUpdatesDefaultItemKey($value, int $rowId, $key, array $defaultItemKeys): bool
    {
        return is_array($value) && isset($value[$rowId]) && in_array($key, $defaultItemKeys);
    }
}
