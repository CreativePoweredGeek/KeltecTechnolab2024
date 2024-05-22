<?php

use CartThrob\Dependency\Illuminate\Support\Str;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @property CI_Controller $EE
 * @property Cartthrob_core_ee $cartthrob;
 * @property Cartthrob_cart $cart
 * @property Cartthrob_store $store
 */
class Cartthrob
{
    public $cartthrob;
    public $store;
    public $cart;

    public function __construct()
    {
        ee()->cartthrob_loader->setup($this);

        /* @todo This list could likely be paired down and added to the Tag/Action classes that need them */
        ee()->lang->loadfile('cartthrob');
        ee()->load->add_package_path(PATH_THIRD . 'cartthrob');
        ee()->load->helper(['security', 'countries', 'data_formatting', 'credit_card', 'form', 'template']);
        ee()->load->library(['template_helper']);
        ee()->load->model('product_model');

        ee()->product_model->load_products(ee()->cartthrob->cart->product_ids());
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     * @throws Exception
     */
    public function __call($method, $args)
    {
        ee()->load->library('cartthrob_addons');

        if (substr($method, -6) == 'action') {
            if (!ee()->input->get_post('ACT')) {
                show_404();
            }

            $className = 'CartThrob\\Actions\\' . Str::studly($method);
        } else {
            $className = 'CartThrob\\Tags\\' . Str::studly($method) . 'Tag';
        }

        if (class_exists($className)) {
            return cartthrob($className)->process();
        } elseif (ee()->cartthrob_addons->method_exists($method)) {
            return ee()->cartthrob_addons->call($method);
        } else {
            return ee()->cartthrob_addons->call($method);
            throw new Exception("Call to undefined method Cartthrob::$method()");
        }
    }

    /**
     * @return bool
     * @TODO make this field figure out the field type, and make that field type handle the output.
     */
    public function field()
    {
        $entry_id = ee()->TMPL->fetch_param('entry_id');
        $field = ee()->TMPL->fetch_param('field');

        ee()->load->model('cartthrob_entries_model');

        $entry = ee()->cartthrob_entries_model->entry(ee()->TMPL->fetch_param('entry_id'));

        ee()->load->helper('array');

        return element(ee()->TMPL->fetch_param('field'), $entry);
    }

    /**
     * @return mixed
     */
    public function purchased_entry_ids()
    {
        $data = [];

        ee()->load->model('purchased_items_model');

        $purchased = ee()->purchased_items_model->purchased_entry_ids();

        foreach ($purchased as $entry_id) {
            $data[] = ['entry_id' => $entry_id];
        }

        return ee()->template_helper->parse_variables($data);
    }

    /**
     * most_purchased
     *
     * Tag pair will print out the entry IDs of items purchased in descending order.
     * @param $TMPL limit
     * @return string
     **/
    public function most_purchased()
    {
        $data = [];

        ee()->load->model('order_management_model');
        $sort = ee()->TMPL->fetch_param('sort') ? ee()->TMPL->fetch_param('sort') : 'DESC';
        $limit = ee()->TMPL->fetch_param('limit');

        $purchased = ee()->order_management_model->get_purchased_products([], 'total_sales', $sort, $limit);

        foreach ($purchased as $row) {
            $data[] = [
                'entry_id' => $row['entry_id'],
            ];
        }

        return ee()->template_helper->parse_variables($data);
    }

    /**
     * Return the tax rate
     *
     * @return mixed
     */
    public function cart_tax_rate()
    {
        return ee()->cartthrob->store->tax_rate();
    }

    /**
     * @return mixed
     */
    public function check_cc_number_errors()
    {
        $data = [
            'errors' => '',
            'valid' => true,
        ];

        if (!ee()->TMPL->fetch_param('credit_card_number')) {
            $data['errors'] = ee()->lang->line('validate_cc_number_missing'); // return lang missing number.
        }

        $response = validate_credit_card(ee()->TMPL->fetch_param('credit_card_number'), ee()->TMPL->fetch_param('card_type'));

        if (!$response['valid']) {
            $data['errors'] = $response['error_code'];
            $data['valid'] = false;

            switch ($response['error_code']) {
                case '1':
                    $data['errors'] = ee()->lang->line('validate_cc_card_type_unknown');
                    break;
                case '2':
                    $data['errors'] = ee()->lang->line('validate_cc_card_type_mismatch');
                    break;
                case '3':
                    $data['errors'] = ee()->lang->line('validate_cc_invalid_card_number');
                    break;
                case '4':
                    $data['errors'] = ee()->lang->line('validate_cc_incorrect_card_length');
                    break;
                default:
                    $data['errors'] = ee()->lang->line('validate_cc_card_type_unknown');
            }
        }

        return ee()->template_helper->parse_variables_row($data);
    }

    /**
     * For use in a conditional, returns whether or not customer_info has been saved
     *
     * @return string
     */
    public function is_saved()
    {
        foreach (ee()->cartthrob->cart->customer_info() as $key => $value) {
            if (!empty($value)) {
                return '1';
            }
        }

        return 0;
    }

    /**
     * @return mixed
     */
    public function member_downloads()
    {
        if (!ee()->session->userdata('member_id')) {
            return ee()->TMPL->no_results();
        }

        ee()->load->model('cartthrob_entries_model');

        return ee()->cartthrob_entries_model->channel_entries([
            'dynamic' => 'no',
            'author_id' => ee()->session->userdata('member_id'),
            'channel_id' => ee()->cartthrob->store->config('purchased_items_channel'),
        ]);
    }

    /**
     * @return mixed
     */
    public function package()
    {
        if (ee()->TMPL->fetch_param('row_id', '') !== '') {
            $item = ee()->cartthrob->cart->item(ee()->TMPL->fetch_param('row_id'));
        }

        $data = [];

        if (empty($item)) {
            if (ee()->TMPL->fetch_param('entry_id', '') !== '') {
                $product = ee()->product_model->get_product(ee()->TMPL->fetch_param('entry_id'));

                ee()->load->library('api');

                ee()->legacy_api->instantiate('channel_fields');

                if ($product && ee()->api_channel_fields->setup_handler('cartthrob_package')) {
                    if (ee()->TMPL->fetch_param('variable_prefix')) {
                        ee()->api_channel_fields->field_types['cartthrob_package']->variable_prefix = ee()->TMPL->fetch_param('variable_prefix');
                    }

                    $field_id = ee()->cartthrob_field_model->channel_has_fieldtype($product['channel_id'],
                        'cartthrob_package', true);

                    if ($field_id && isset($product['field_id_' . $field_id])) {
                        $data = ee()->api_channel_fields->apply('pre_process',
                            [$product['field_id_' . $field_id]]);

                        return ee()->api_channel_fields->apply('replace_tag',
                            [$data, ee()->TMPL->tagparams, ee()->TMPL->tagdata]);
                    }
                }
            }
        } elseif ($item->sub_items()) {
            // @todo This needs to be migrated to HasVariables trait if chosen to remain
            $data = ee()->cartthrob_variables->sub_item_vars($item);
        }

        if (count($data) === 0) {
            return ee()->TMPL->no_results();
        }

        loadCartThrobPath();

        return ee()->template_helper->parse_variables($data);
    }

    public function save_customer_info()
    {
        ee()->load->library('form_builder');

        $_POST = array_merge($_POST, ee()->TMPL->tagparams);

        $customer_fields = array_keys(ee()->cartthrob->cart->customer_info());

        $required = ee()->TMPL->fetch_param('required');

        $save_shipping = bool_string(ee()->TMPL->fetch_param('save_shipping'), true);

        if ($required == 'all') {
            $required = $customer_fields;

            if ($save_shipping) {
                $required[] = 'shipping_option';
            }
        } elseif (preg_match('/^not\s/', $required)) {
            $not_required = explode('|', substr($required, 4));

            $required = $customer_fields;

            if ($save_shipping) {
                $required[] = 'shipping_option';
            }

            foreach ($required as $key => $value) {
                if (in_array($value, $not_required)) {
                    unset($required[$key]);
                }
            }
        } elseif ($required) {
            $required = explode('|', $required);
        }

        if (!$required) {
            $required = [];
        }

        if (ee()->form_builder
            ->set_require_rules(false)
            ->set_require_errors(false)
            ->set_require_form_hash(false)
            ->set_required($required)->validate($required)) {
            ee()->cartthrob->save_customer_info();
        }

        ee()->template_helper->tag_redirect(ee()->TMPL->fetch_param('return'));
    }

    /**
     * Saves chosen shipping option to SESSION
     *
     * @return string
     */
    public function save_shipping_option()
    {
        $shipping_option = set(ee()->TMPL->fetch_param('shipping_option'), ee()->input->post('shipping_option', true));

        ee()->cartthrob->cart->set_shipping_info('shipping_option', $shipping_option);

        ee()->cartthrob->cart->save();

        ee()->template_helper->tag_redirect(ee()->TMPL->fetch_param('return'));
    }

    /**
     * update_live_rates_action
     * Gets a quoted shipping value from the default shipping method, and applies that value as the shipping value
     *
     * @param ee()->TMPL->shipping_plugin
     * @param ee()->TMPL->validate (checks required fields)
     * @return string
     **/
    public function update_live_rates_action()
    {
        // save_shipping (if set in post...will automatically save the cheapest option)

        if (!ee()->input->get_post('ACT')) {
            return;
        }

        if (ee()->extensions->active_hook('cartthrob_update_live_rates_start') === true) {
            ee()->extensions->call('cartthrob_update_live_rates_start');
            if (ee()->extensions->end_script === true) {
                return;
            }
        }
        ee()->cartthrob->save_customer_info();
        ee()->cartthrob->cart->save();

        ee()->load->library('form_validation');
        ee()->load->library('form_builder');
        ee()->load->library('api/api_cartthrob_shipping_plugins');
        ee()->load->library('languages');

        if (ee()->cartthrob->cart->count() <= 0) {
            return ee()->form_builder
                ->add_error(ee()->lang->line('empty_cart'))
                ->action_complete();
        }

        if (ee()->cartthrob->cart->shippable_subtotal() <= 0) {
            ee()->form_builder
                ->set_errors(ee()->cartthrob->errors())
                ->set_success_callback([ee()->cartthrob, 'action_complete'])
                ->action_complete();
        }

        ee()->languages->set_language(ee()->input->post('language', true));

        $not_required = [];

        $required = [];

        if (ee()->input->post('REQ')) {
            $required_string = ee('Security/XSS')->clean(ee('Encrypt')->decode(ee()->input->post('REQ')));

            if (preg_match('/^not (.*)/', $required_string, $matches)) {
                $not_required = explode('|', $matches[1]);
                $required_string = '';
            }

            if ($required_string) {
                $required = explode('|', $required_string);
            }

            unset($required_string);
        }

        if (ee()->input->post('shipping_plugin')) {
            $selected_plugin = ee('Security/XSS')->clean(ee()->input->post('shipping_plugin'));
            ee()->api_cartthrob_shipping_plugins->set_plugin($selected_plugin);
            if (bool_string(ee('Security/XSS')->clean(ee()->input->post('activate_plugin')), true)) {
                ee()->cartthrob->cart->set_config('shipping_plugin', $selected_plugin);
            }
        }

        $shipping_name = ee()->api_cartthrob_shipping_plugins->title();

        $required = array_unique(array_merge($required, ee()->api_cartthrob_shipping_plugins->required_fields()));
        foreach ($not_required as $key) {
            unset($required[array_search($key, $required)]);
        }
        if (!ee()->form_builder->set_required($required)->validate()) {
            return ee()->form_builder->action_complete();
        }

        $product_id = ee()->input->post('shipping_option') ? ee()->input->post('shipping_option') : 'ALL';

        $shippingInfo = [
            'error_message' => null,
            'option_value' => [],
            'option_name' => [],
            'price' => [],
        ];

        $shippingInfo = array_merge($shippingInfo, ee()->api_cartthrob_shipping_plugins->get_live_rates($product_id));

        ee()->load->library('cartthrob_shipping_plugins');

        // OUTPUTS ERROR IN STANDARD EE WAY
        if (!$shippingInfo || (empty($shippingInfo['error_message']) && empty($shippingInfo['option_value']))) {
            return ee()->form_builder
                ->add_error(ee()->lang->line('no_shipping_returned'))
                ->action_complete();
        }
        if (!empty($shippingInfo['error_message'])) {
            return ee()->form_builder
                ->add_error($shippingInfo['error_message'])
                ->action_complete();
        } else {
            // SAVE THE CHEAPEST OPTION AS SELECTED
            if (bool_string(ee()->input->post('save_shipping'), true)) {
                if (!in_array($this->selected_shipping_option(), $shippingInfo['option_value'])) {
                    $lowest_amount_key = array_pop(array_keys($shippingInfo['price'], min($shippingInfo['price'])));
                    if (!empty($shippingInfo['option_value'][$lowest_amount_key])) {
                        ee()->cartthrob->cart->set_shipping($shippingInfo['price'][$lowest_amount_key]);
                        ee()->cartthrob->cart->set_shipping_info('shipping_option',
                            $shippingInfo['option_value'][$lowest_amount_key]);
                        ee()->cartthrob->cart->save();
                    }
                }
            }
        }

        ee()->form_builder->set_errors(ee()->cartthrob->errors())
            ->set_success_callback([ee()->cartthrob, 'action_complete'])
            ->action_complete();
    }
}
