<?php

namespace CartThrob\Tags;

use Cartthrob_item;
use EE_Session;
use ExpressionEngine\Library\Security\XSS;

class AddToCartTag extends Tag
{
    /** @var XSS */
    private $xss;

    public function __construct(EE_Session $session, XSS $xss)
    {
        parent::__construct($session);

        $this->xss = $xss;

        ee()->load->library('api');
    }

    public function process()
    {
        if (ee()->extensions->active_hook('cartthrob_add_to_cart_start') === true) {
            ee()->extensions->call('cartthrob_add_to_cart_start');
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        $data = [
            'entry_id' => $this->param('entry_id'),
            'quantity' => $this->param('quantity') !== false ? $this->param('quantity') : 1,
            'class' => 'product',
        ];

        foreach ($this->params() as $key => $value) {
            if (preg_match('/^item_options?:(.*)$/', $key, $match)) {
                if (!isset($data['item_options'])) {
                    $data['item_options'] = [];
                }

                $data['item_options'][$match[1]] = $value;
            }
        }

        if ($this->param('shipping_exempt')) {
            $data['no_shipping'] = true;
        }

        if ($this->param('no_shipping')) {
            $data['no_shipping'] = true;
        }

        if ($this->param('tax_exempt')) {
            $data['no_tax'] = true;
        }

        if ($this->param('no_tax')) {
            $data['no_tax'] = true;
        }

        $data['product_id'] = $data['entry_id'];

        if (!$data['entry_id']) {
            ee()->cartthrob->set_error(lang('add_to_cart_no_entry_id'));
        }

        if (!ee()->cartthrob->errors()) {
            $entry = ee()->product_model->get_product($data['entry_id']);

            // it's a package
            if ($entry && $field_id = ee()->cartthrob_field_model->channel_has_fieldtype($entry['channel_id'], 'cartthrob_package', true)) {
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
                        ];

                        $item['item_options'] = (isset($row['option_presets'])) ? $row['option_presets'] : [];

                        $row_item_options = [];

                        if (isset($_POST['item_options'][$row_id])) {
                            $row_item_options = $_POST['item_options'][$row_id];
                        } elseif (isset($_POST['item_options'][':' . $row_id])) {
                            $row_item_options = $_POST['item_options'][':' . $row_id];
                        }

                        $price_modifiers = ee()->product_model->get_all_price_modifiers($row['entry_id']);

                        foreach ($row_item_options as $key => $value) {
                            // if it's not a price modifier (ie, an on-the-fly item option), add it
                            // if it is a price modifier, check that it's been allowed before adding
                            if (!isset($price_modifiers[$key]) || !empty($row['allow_selection'][$key])) {
                                $item['item_options'][$key] = $this->xss->clean($value);
                            }
                        }

                        $data['sub_items'][$row_id] = $item;
                    }
                }
            } elseif ($entry) {
                // it's a product... don't need to do anything extra
                // but we need to check for it... else the class gets killed and we dont' want that.
            } elseif (isset($data['class'])) {
                // it's a dynamic product. kill the class
                unset($data['class']);
            }

            /** @var CartThrob_item $item */
            $item = ee()->cartthrob->cart->add_item($data);

            if ($item && $this->param('permissions')) {
                $item->set_meta('permissions', $this->param('permissions'));
            }

            if ($item && $this->param('license_number')) {
                $item->set_meta('license_number', true);
            }

            // cartthrob_add_to_cart_end hook
            if (ee()->extensions->active_hook('cartthrob_add_to_cart_end') === true) {
                ee()->extensions->call('cartthrob_add_to_cart_end', $item);
                if (ee()->extensions->end_script === true) {
                    return;
                }
            }
        }

        $show_errors = $this->param('show_errors', true);

        $this->setFlashdata([
            'success' => !(bool)ee()->cartthrob->errors(),
            'errors' => ee()->cartthrob->errors(),
            'csrf_token' => ee()->functions->add_form_security_hash('{csrf_token}'),
        ]);

        if ($show_errors && ee()->cartthrob->errors() && !AJAX_REQUEST) {
            return show_error(ee()->cartthrob->errors());
        }

        ee()->cartthrob->cart->save();

        ee()->template_helper->tag_redirect($this->param('return'));
    }
}
