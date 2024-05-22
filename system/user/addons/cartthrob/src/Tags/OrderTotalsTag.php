<?php

namespace CartThrob\Tags;

use CartThrob\Math\Number;
use EE_Session;

class OrderTotalsTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('number');
        ee()->load->model('cartthrob_entries_model');
    }

    public function process()
    {
        $data = [
            'total' => 0,
            'subtotal' => 0,
            'tax' => 0,
            'shipping' => 0,
            'discount' => 0,
            'count' => 0,
        ];

        if (ee()->cartthrob->store->config('orders_channel')) {
            if ($query = ee()->cartthrob_entries_model->channel_entries(['channel_id' => ee()->cartthrob->store->config('orders_channel')], true)) {
                $data['count'] = $query->num_rows();

                foreach ($query->result_array() as $row) {
                    if (ee()->cartthrob->store->config('orders_total_field') && isset($row['field_id_' . ee()->cartthrob->store->config('orders_total_field')])) {
                        $data['total'] += abs(Number::sanitize($row['field_id_' . ee()->cartthrob->store->config('orders_total_field')]));
                    }

                    if (ee()->cartthrob->store->config('orders_subtotal_field') && isset($row['field_id_' . ee()->cartthrob->store->config('orders_subtotal_field')])) {
                        $data['subtotal'] += abs(Number::sanitize($row['field_id_' . ee()->cartthrob->store->config('orders_subtotal_field')]));
                    }

                    if (ee()->cartthrob->store->config('orders_tax_field') && isset($row['field_id_' . ee()->cartthrob->store->config('orders_tax_field')])) {
                        $data['tax'] += abs(Number::sanitize($row['field_id_' . ee()->cartthrob->store->config('orders_tax_field')]));
                    }

                    if (ee()->cartthrob->store->config('orders_shipping_field') && isset($row['field_id_' . ee()->cartthrob->store->config('orders_shipping_field')])) {
                        $data['shipping'] += abs(Number::sanitize($row['field_id_' . ee()->cartthrob->store->config('orders_shipping_field')]));
                    }

                    if (ee()->cartthrob->store->config('orders_discount_field') && isset($row['field_id_' . ee()->cartthrob->store->config('orders_discount_field')])) {
                        $data['discount'] += abs(Number::sanitize($row['field_id_' . ee()->cartthrob->store->config('orders_discount_field')]));
                    }
                }
            }
        }

        foreach ($data as $key => $value) {
            if ($key === 'count') {
                continue;
            }

            $data[$key] = ee()->number->format($value);
        }

        if (!$this->tagdata()) {
            return $data['total'];
        }

        return $this->parseVariablesRow($data);
    }
}
