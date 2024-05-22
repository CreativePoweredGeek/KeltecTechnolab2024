<?php

namespace CartThrob\Tags;

class ViewSettingTag extends Tag
{
    public function process()
    {
        foreach ($this->params() as $key => $value) {
            switch ($key) {
                case !$key:
                case !bool_string($value):
                    break;
                case 'prefix':
                case 'number_prefix':
                    return ee()->cartthrob->store->config('number_format_defaults_prefix');
                case 'country':
                    return ee()->cartthrob->store->config('default_location', 'country_code');
                case 'country_code':
                case 'state':
                case 'region':
                case 'zip':
                    return ee()->cartthrob->store->config('default_location', $key);
                case 'member_id':
                    return ee()->cartthrob->store->config('default_member_id');
                case 'thousands_sep':
                case 'thousands_separator':
                    return ee()->cartthrob->store->config('number_format_defaults_thousands_sep');
                case 'prefix_position':
                    return ee()->cartthrob->store->config('number_format_defaults_prefix_position');
                case 'decimal':
                case 'decimal_point':
                    return ee()->cartthrob->store->config('number_format_defaults_dec_point');
                case 'decimal_precision':
                    return ee()->cartthrob->store->config('number_format_defaults_decimals');
                case 'currency_code':
                    return ee()->cartthrob->store->config('number_format_defaults_currency_code');
                case 'shipping_option':
                case 'selected_shipping_option':
                    return ee()->cartthrob->cart->shipping_info('shipping_option');
                default:
                    return ee()->cartthrob->store->config($key);
            }
        }

        return '';
    }
}
