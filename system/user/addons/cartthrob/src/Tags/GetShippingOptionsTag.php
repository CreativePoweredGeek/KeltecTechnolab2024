<?php

namespace CartThrob\Tags;

use CartThrob\GeneratesFormElementAttributes;
use EE_Session;

class GetShippingOptionsTag extends Tag
{
    use GeneratesFormElementAttributes;

    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library(['api/api_cartthrob_shipping_plugins', 'number']);
        ee()->load->helper('form');
    }

    /**
     * Returns the options from the selected shipping plugin
     */
    public function process()
    {
        if ($this->hasParam('shipping_plugin')) {
            ee()->api_cartthrob_shipping_plugins->set_plugin($this->param('shipping_plugin'));
        }

        $options = ee()->api_cartthrob_shipping_plugins->shipping_options();
        $tagData = trim($this->tagdata());

        if (ee()->cartthrob->cart->custom_data('shipping_error')) {
            $option['price'] = '';
            $option['option_value'] = '';
            $option['option_name'] = '';
            $option['checked'] = '';
            $option['selected'] = '';
            $option['count'] = 0;
            $option['first_row'] = false;
            $option['last_row'] = false;
            $option['total_results'] = 0;
            $options['error_message'] = ee()->cartthrob->cart->custom_data('shipping_error');

            return $this->parseVariablesRow($options);
        }

        if (empty($options) && empty($tagData)) {
            return null;
        }

        $selected = ee()->cartthrob->cart->shipping_info('shipping_option') ?
            ee()->cartthrob->cart->shipping_info('shipping_option') :
            ee()->api_cartthrob_shipping_plugins->default_shipping_option();

        if (empty($tagData)) {
            $attrs = $this->generateFormAttrs(
                $this->param('id', null),
                $this->param('class', null),
                $this->param('onchange', null),
                $this->param('extra', null)
            );

            $selectOptions = [];

            foreach ($options as $row) {
                if ($this->param('hide_price')) {
                    $selectOptions[$row['rate_short_name']] = $row['rate_title'];
                } else {
                    $selectOptions[$row['rate_short_name']] = $row['rate_title'] . ' - ' . $row['price'];
                }
            }

            if (!empty($selectOptions)) {
                return form_dropdown('shipping_option', $selectOptions, $selected, $attrs);
            }

            return null;
        }

        $newOptions = [];

        foreach ($options as $key => $option) {
            if (empty($option['rate_short_name']) || empty($option['rate_title'])) {
                continue;
            }

            if (isset($count)) {
                $count++;
            } else {
                $count = 1;
            }

            $option['price'] = ee()->number->format($option['price']);
            $option['option_value'] = $option['rate_short_name'];
            $option['option_name'] = $option['rate_title'];
            $option['checked'] = ($option['rate_short_name'] == $selected) ? ' checked="checked"' : '';
            $option['selected'] = ($option['rate_short_name'] == $selected) ? ' selected="selected"' : '';
            $option['count'] = $count;
            $option['first_row'] = ($count === 1) ? true : false;
            $option['last_row'] = ($count === count($options)) ? true : false;
            $option['total_results'] = count($options);
            $option['error_message'] = null;

            if (ee()->cartthrob->cart->custom_data('shipping_error')) {
                $option['error_message'] = ee()->cartthrob->cart->custom_data('shipping_error');
            }

            $newOptions[] = $option;
        }

        return $this->parseVariables($newOptions);
    }
}
