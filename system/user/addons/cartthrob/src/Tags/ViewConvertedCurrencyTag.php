<?php

namespace CartThrob\Tags;

use CartThrob\Math\Number;
use EE_Session;

class ViewConvertedCurrencyTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library(['curl', 'number']);
    }

    public function process()
    {
        // Check to see if this value is being passed in or not.
        $number = $this->param('price');

        if ($number === false) {
            return '';
        }

        $number = abs(Number::sanitize($number));

        // -------------------------------------------
        // 'cartthrob_view_converted_currency' hook.
        //
        if (ee()->extensions->active_hook('cartthrob_view_converted_currency') === true) {
            return ee()->extensions->call('cartthrob_view_converted_currency', $number);
        }

        // set defaults
        $prefix = '';
        $currency = strtolower($this->param('currency_code', ee()->cartthrob->store->config('number_format_default_currency_code')));
        $new_prefix = $this->param('use_prefix');
        $new_currency = strtolower($this->param('new_currency_code', ee()->cartthrob->store->config('number_format_default_currency_code')));

        if ($new_prefix) {
            switch ($new_currency) {
                case 'eur':
                case 'lvl':
                    $prefix = '&#8364;';
                    break;
                case 'gbp':
                    $prefix = '&#163;';
                    break;
                case 'brl':
                    $prefix = 'R$';
                    break;
                case 'chf':
                    $prefix = 'CHF';
                    break;
                case 'cny':
                case 'jpy':
                    $prefix = '&#165;';
                    break;
                case 'dkk':
                case 'eek':
                case 'nok':
                case 'ron':
                case 'sek':
                    $prefix = 'kr';
                    break;
                case 'inr':
                    $prefix = '&#8360;';
                    break;
                case 'krw':
                    $prefix = '&#8361;';
                    break;
                case 'myr':
                    $prefix = 'RM';
                    break;
                case 'thb':
                    $prefix = '&#3647;';
                    break;
                case 'zar':
                    $prefix = 'R';
                    break;
                case 'bgn':
                    $prefix = '&#1083;&#1074;';
                    break;
                case 'czk':
                    $prefix = '&#75;&#269;';
                    break;
                case 'huf':
                    $prefix = 'Ft';
                    break;
                case 'ltl':
                    $prefix = 'Lt';
                    break;
                case 'pln':
                    $prefix = 'z&#322;';
                    break;
                case 'hrk':
                    $prefix = 'kn';
                    break;
                case 'rub':
                    $prefix = '&#1088;&#1091;&#1073;';
                    break;
                case 'try':
                    $prefix = 'TL';
                    break;
                case 'php':
                    $prefix = 'Php';
                    break;
                case 'ars':
                case 'aud':
                case 'cad':
                case 'cop':
                case 'hkd':
                case 'mxn':
                case 'nzd':
                case 'sgd':
                case 'usd':
                default:
                    $prefix = '$';
            }
        }

        ee()->number->set_prefix($prefix);

        $api_key = $this->hasParam('api_key') ? '?key=' . $this->param('api_key') : '';

        if ($json = ee()->curl->simple_get('http://xurrency.com/api/' . $currency . '/' . $new_currency . '/' . $number . $api_key)) {
            $obj = json_decode($json);

            if (is_object($obj) && isset($obj->{'result'}) && isset($obj->{'status'}) && $obj->{'status'} == 'ok' && isset($obj->{'result'}->{'value'})) {
                return ee()->number->format($obj->{'result'}->{'value'});
            }
        }

        return ee()->number->format($number);
    }
}
