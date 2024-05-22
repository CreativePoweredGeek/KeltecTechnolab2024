<?php

namespace CartThrob\Tags;

use EE_Session;

class CouponInfoTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('number');
        ee()->load->model('coupon_code_model');
    }

    public function process()
    {
        if (!$coupon_codes = ee()->cartthrob->cart->coupon_codes()) {
            return ee()->TMPL->no_results();
        }

        $variables = [];

        foreach ($coupon_codes as $coupon_code) {
            $row = array_key_prefix(ee()->coupon_code_model->get($coupon_code), 'coupon_');
            $row['coupon_code'] = $coupon_code;

            $entry_id = $row['coupon_metadata']['entry_id'];
            $discount_price = ee()->cartthrob->cart->discount(true, $entry_id, $coupon_code);

            $row['discount_amount'] = $row['coupon_amount'] = $row['voucher_amount'] = ee()->number->format($discount_price);

            unset($row['coupon_metadata']);

            $variables[] = array_merge(ee()->cartthrob_entries_model->entry_vars($entry_id), $row);
        }

        return $this->parseVariables($variables);
    }
}
