<?php

namespace CartThrob\Services;

use CartThrob\Traits\OrderTrait;

class OrdersService
{
    use OrderTrait;

    public function createOrder($member_id, array $data)
    {
        ee()->load->model('cartthrob_members_model');
        ee()->load->model('cartthrob_field_model');
        ee()->load->model('cartthrob_entries_model');
        ee()->load->model('order_model');

        ee()->load->helper('url');
        ee()->load->helper('text');
        ee()->load->helper('array');

        $orderNumber = $this->generateOrderNumber();

        $entry_data = [
            'title' => 'order',
            'url_title' => uniqid('order_', true),
            'author_id' => $member_id,
            'channel_id' => ee()->cartthrob->store->config('orders_channel'),
            'status' => (ee()->cartthrob->store->config('orders_default_status') ? ee()->cartthrob->store->config('orders_default_status') : 'open'),
        ];

        $data = array_merge($data, $entry_data);

        if (ee()->cartthrob->store->config('orders_subtotal_field') && isset($data['subtotal'])) {
            $data['field_id_' . ee()->cartthrob->store->config('orders_subtotal_field')] = $data['subtotal'];
        }
        if (ee()->cartthrob->store->config('orders_subtotal_plus_tax_field') && isset($data['subtotal_plus_tax'])) {
            $data['field_id_' . ee()->cartthrob->store->config('orders_subtotal_plus_tax_field')] = $data['subtotal_plus_tax'];
        }
        if (ee()->cartthrob->store->config('orders_tax_field') && isset($data['tax'])) {
            $data['field_id_' . ee()->cartthrob->store->config('orders_tax_field')] = $data['tax'];
        }
        if (ee()->cartthrob->store->config('orders_shipping_field') && isset($data['shipping'])) {
            $data['field_id_' . ee()->cartthrob->store->config('orders_shipping_field')] = $data['shipping'];
        }
        if (ee()->cartthrob->store->config('orders_shipping_plus_tax_field') && isset($data['shipping_plus_tax'])) {
            $data['field_id_' . ee()->cartthrob->store->config('orders_shipping_plus_tax_field')] = $data['shipping_plus_tax'];
        }
        if (ee()->cartthrob->store->config('orders_total_field') && isset($data['total'])) {
            $data['field_id_' . ee()->cartthrob->store->config('orders_total_field')] = $data['total'];
        }
        if (ee()->cartthrob->store->config('orders_discount_field') && isset($data['discount'])) {
            $data['field_id_' . ee()->cartthrob->store->config('orders_discount_field')] = $data['discount'];
        }

        foreach (ee()->cartthrob_field_model->get_fields_by_channel(ee()->cartthrob->store->config('orders_channel')) as $field) {
            if (isset($data[$field['field_name']])) {
                $data['field_id_' . $field['field_id']] = $data[$field['field_name']];
            }
        }

        if (!$orderNumber) {
            // create the entry and update the title and url title.
            $orderNumber = ee()->cartthrob_entries_model->create_entry($data);

            $data['title'] = $this->generateOrderTitle($orderNumber);
            $data['url_title'] = ee()->cartthrob->store->config('orders_url_title_prefix') . $orderNumber . ee()->cartthrob->store->config('orders_url_title_suffix');

            // update now.
            ee()->cartthrob_entries_model->update_entry($orderNumber, $data);
        } else {
            $data['title'] = ee()->cartthrob->store->config('orders_title_prefix') . $orderNumber . ee()->cartthrob->store->config('orders_title_suffix');
            $data['url_title'] = ee()->cartthrob->store->config('orders_url_title_prefix') . $orderNumber . ee()->cartthrob->store->config('orders_url_title_suffix');

            // otherwise replace the sequential id with an entry id for use elsewhere.
            $orderNumber = @ee()->cartthrob_entries_model->create_entry($data);
        }

        // Add items to the order items
        if (ee()->cartthrob->store->config('orders_items_field') && element('items', $data)) {
            $field_type = ee()->cartthrob_field_model->get_field_type(ee()->cartthrob->store->config('orders_items_field'));

            if ($field_type === 'cartthrob_order_items') {
                $items = [];

                foreach (element('items', $data) as $key => $item) {
                    $items[] = $item;
                }

                if (!empty($items)) {
                    // add items to the database
                    ee()->order_model->updateOrderItems($orderNumber, $items);
                    // stick a 1 in the channel entry data
                    ee()->cartthrob_entries_model->update($orderNumber, ['field_id_' . ee()->cartthrob->store->config('orders_items_field') => 1]);
                }
            }
        }

        return $orderNumber;
    }
}
