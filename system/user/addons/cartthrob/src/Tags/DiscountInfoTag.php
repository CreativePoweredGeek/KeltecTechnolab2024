<?php

namespace CartThrob\Tags;

use EE_Session;

class DiscountInfoTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->model('discount_model');
        ee()->load->library('number');
    }

    public function process()
    {
        if (!$discounts = ee()->discount_model->get_valid_discounts()) {
            return ee()->TMPL->no_results();
        }

        $variables = [];

        foreach ($discounts as $discount) {
            $row = [];

            foreach ($discount as $key => $value) {
                if (strpos($key, 'discount_') !== 0) {
                    $key = 'discount_' . $key;
                }

                $row[$key] = $value;
            }

            $discount_price = ee()->cartthrob->cart->discount(true, $discount['entry_id']);

            $row['discount_amount'] = ee()->number->format($discount_price);
            $row = array_merge(ee()->cartthrob_entries_model->entry_vars($row['discount_entry_id']), $row);

            $variables[] = $row;
        }

        return $this->parseVariables($variables);
    }
}
