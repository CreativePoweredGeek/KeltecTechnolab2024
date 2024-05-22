<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @property CI_Controller $EE
 */
class Cartthrob_shipping_plugins
{
    /**
     * Cartthrob_shipping_plugins constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        ee()->load->library('cartthrob_payments');
    }

    /**
     * @param $url
     * @param bool $data
     * @param bool $header
     * @param string $mode
     * @param bool $suppress_errors
     * @param null $options
     * @return mixed
     */
    public function curl_transaction($url, $data = false, $header = false, $mode = 'POST', $suppress_errors = false, $options = null)
    {
        return ee()->cartthrob_payments->curlTransaction($url, $data, $header, $mode, $suppress_errors, $options);
    }

    /**
     * @param array $option_values
     * @param array $option_names
     * @param array $option_prices
     * @param null $errors
     * @param null $selected_option
     * @return string|null
     */
    public function live_rates_options($option_values = [], $option_names = [], $option_prices = [], $errors = null, $selected_option = null)
    {
        $output = null;

        if (!isset(ee()->TMPL)) {
            ee()->load->library('template', null, 'TMPL');
        }

        if (!ee()->TMPL->tagdata) {
            $id = (ee()->TMPL->fetch_param('id')) ? 'id="' . ee()->TMPL->fetch_param('id') . '"' : '';
            $class = (ee()->TMPL->fetch_param('class')) ? 'class="' . ee()->TMPL->fetch_param('class') . '"' : '';
            $onchange = (ee()->TMPL->fetch_param('onchange')) ? 'onchange="' . ee()->TMPL->fetch_param('onchange') . '"' : '';
            $extra = (ee()->TMPL->fetch_param('extra')) ? ee()->TMPL->fetch_param('extra') : '';

            $output .= '<select name="shipping[product]" ' . $id . ' ' . $class . ' ' . $onchange . ' ' . $extra . ">\n";

            foreach ($option_values as $key => $value) {
                // make sure a price is set
                if (!empty($option_prices[$key])) {
                    $output .= "\t";
                    $output .= '<option value="' . $key . '"' . (($selected_option == $key) ? 'selected="selected"' : '') . '>' . $option_names[$key] . '</option>';
                    $output .= "\n";
                }
            }
            $output .= "</select>\n";
        } else {
            $count = 0;
            foreach ($option_values as $key => $value) {
                $variables['selected'] = ($key == $selected_option) ? ' selected="selected"' : '';
                $variables['checked'] = ($key == $selected_option) ? ' checked="checked"' : '';
                $variables['option_value'] = $key;
                $variables['option_name'] = $option_names[$key];
                $variables['price'] = $option_prices[$key];

                $cond['first_item'] = ($count == 0 ? true : false);
                $cond['selected'] = (bool)$selected;
                $cond['checked'] = (bool)$checked;
                $cond['price'] = (bool)$price;
                $cond['rate_title'] = (bool)$rate_title;
                $cond['rate_short_name'] = (bool)$key;
                $cond['last_item'] = ($count == count($shipping_options)) ? true : false;

                $tagdata .= $this->parse_variables($variables);
                $tagdata .= ee()->functions->prep_conditionals($tagdata, $cond);
                $count++;
            }

            $output .= $tagdata;
        }

        return $output;
    }

    /**
     * @param $location
     * @param bool $default
     * @return bool
     */
    public function customer_location_defaults($location, $default = false)
    {
        if (ee()->cartthrob->cart->customer_info('shipping_' . $location)) {
            return ee()->cartthrob->cart->customer_info('shipping_' . $location);
        } elseif (ee()->cartthrob->cart->customer_info($location)) {
            return ee()->cartthrob->cart->customer_info($location);
        } elseif (ee()->cartthrob->cart->custom_data($location)) { // looking through custom data for this information.
            return ee()->cartthrob->cart->custom_data($location);
        } elseif ($default !== false) {
            return $default;
        }

        return ee()->cartthrob->store->config('default_location', $location);
    }
}
