<?php

namespace CartThrob\Services\Order;

class OrderService
{
    private $order = null;

    public function __construct()
    {
    }

    /**
     * @param $prop
     * @return mixed
     */
    public function __get($prop)
    {
        if ($prop == 'id') {
            return $this->order->entry_id;
        }

        return $this->order->{$prop} ?? '';
    }

    /**
     * @param $order
     * @return $this
     */
    public function get($order)
    {
        $this->order = ee('Model')
            ->get('ChannelEntry', $order)
            ->with('Channel')
            ->first();

        return $this;
    }

    /**
     * Delete order and purchased items
     * @return $this
     */
    public function delete()
    {
        if ($purchasedItems = $this->purchasedItems()) {
            foreach ($purchasedItems as $purchasedItem) {
                $purchasedItem->delete();
            }
        }

        $this->order->delete();

        return $this;
    }

    /**
     * Return a ChannelEntry Model representing an order's purchased items
     * @return bool
     */
    public function purchasedItems()
    {
        if (!ee()->cartthrob->store->config('purchased_items_channel')) {
            return false;
        }

        if (!ee()->cartthrob->store->config('purchased_items_order_id_field')) {
            return false;
        }

        $entries = ee('Model')
            ->get('ChannelEntry')
            ->fields('entry_id')
            ->filter('field_id_' . ee()->cartthrob->store->config('purchased_items_order_id_field'), $this->order->entry_id)
            ->all();

        if ($entries->count() === 0) {
            return false;
        }

        return ee('Model')
            ->get('ChannelEntry')
            ->filter('entry_id', 'IN', $entries->pluck('entry_id'))
            ->all();
    }

    /**
     * @param bool $where
     * @param bool $status
     * @param bool $just_total
     * @return array|float|int
     */
    public function totalsByDay($where = false, $status = false, $just_total = false)
    {
        $defaults = [
            'total' => 0,
            'subtotal' => 0,
            'tax' => 0,
            'shipping' => 0,
            'discount' => 0,
            'orders' => 0,
            'count' => 0,
        ];

        if (!ee()->cartthrob->store->config('orders_total_field') || !ee()->cartthrob->store->config('orders_channel')) {
            return ($just_total) ? 0 : $defaults;
        }

        $fields = [];

        foreach ([
                     'orders_total_field' => 'total',
                     'orders_subtotal_field' => 'subtotal',
                     'orders_subtotal_plus_tax_field' => 'subtotal_plus_tax',
                     'orders_tax_field' => 'tax',
                     'orders_shipping_field' => 'shipping',
                     'orders_shipping_plus_tax_field' => 'shipping_plus_tax',
                     'orders_discount_field' => 'discount',
                 ] as $field => $name) {
            if ($id = ee()->cartthrob->store->config($field)) {
                $fields[$id] = $name;
            }
        }

        ee()->db->select('day');
        ee()->db->select('COUNT(' . ee()->db->dbprefix('channel_titles') . '.entry_id) as count');

        foreach ($fields as $field => $name) {
            ee()->db->select_sum('field_id_' . $field, $name);
        }

        ee()->db->from('channel_titles');
        ee()->db->join('channel_data', 'channel_data.entry_id = channel_titles.entry_id');

        foreach ($fields as $id => $name) {
            if (!ee()->db->field_exists('field_id_' . $id, 'channel_data')) {
                ee()->db->join('channel_data_field_' . $id, 'channel_data_field_' . $id . '.entry_id = channel_titles.entry_id');
            }
        }

        if (is_array($where)) {
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    ee()->db->where_in($key, $value);
                } elseif (isset($where['entry_start_date'])) {
                    ee()->db->where('entry_date', '>=', $where['entry_start_date']);
                    ee()->db->where('entry_date', '<', $where['entry_end_date']);
                } else {
                    if ($value == 'IS NOT NULL') {
                        ee()->db->where($key . " <> ''", null, false);
                        ee()->db->where($key . ' IS NOT NULL', null, false);
                    } else {
                        ee()->db->where($key, $value);
                    }
                }
            }
        }

        ee()->db->where('channel_titles.channel_id', ee()->cartthrob->store->config('orders_channel'));

        if ($status) {
            ee()->db->where_not_in('status', $status);
        }

        ee()->db->group_by(['day']);
        ee()->db->order_by('day asc');

        $results = ee()->db->get();

        if ($results->num_rows == 0) {
            return $defaults;
        }

        $results = $results->result_array();

        return (count($results) === 1) ? array_shift($results) : $results;
    }

    /**
     * @param bool $where
     * @param bool $status
     * @param bool $just_total
     * @return array|float|int
     */
    public function totalsByMonth($where = false, $status = false, $just_total = false)
    {
        $defaults = [
            'total' => 0,
            'subtotal' => 0,
            'tax' => 0,
            'shipping' => 0,
            'discount' => 0,
            'orders' => 0,
            'count' => 0,
        ];

        if (!ee()->cartthrob->store->config('orders_total_field') || !ee()->cartthrob->store->config('orders_channel')) {
            return ($just_total) ? 0 : $defaults;
        }

        $fields = [];

        foreach ([
                     'orders_total_field' => 'total',
                     'orders_subtotal_field' => 'subtotal',
                     'orders_subtotal_plus_tax_field' => 'subtotal_plus_tax',
                     'orders_tax_field' => 'tax',
                     'orders_shipping_field' => 'shipping',
                     'orders_shipping_plus_tax_field' => 'shipping_plus_tax',
                     'orders_discount_field' => 'discount',
                 ] as $field => $name) {
            if ($id = ee()->cartthrob->store->config($field)) {
                $fields[$id] = $name;
            }
        }

        ee()->db->select('day');
        ee()->db->select('COUNT(' . ee()->db->dbprefix('channel_titles') . '.entry_id) as count');

        foreach ($fields as $field => $name) {
            ee()->db->select_sum('field_id_' . $field, $name);
        }

        ee()->db->from('channel_titles');
        ee()->db->join('channel_data', 'channel_data.entry_id = channel_titles.entry_id');

        foreach ($fields as $id => $name) {
            if (!ee()->db->field_exists('field_id_' . $id, 'channel_data')) {
                ee()->db->join('channel_data_field_' . $id, 'channel_data_field_' . $id . '.entry_id = channel_titles.entry_id');
            }
        }

        if (is_array($where)) {
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    ee()->db->where_in($key, $value);
                } elseif (isset($where['entry_start_date'])) {
                    ee()->db->where('entry_date', '>=', $where['entry_start_date']);
                    ee()->db->where('entry_date', '<', $where['entry_end_date']);
                } else {
                    if ($value == 'IS NOT NULL') {
                        ee()->db->where($key . " <> ''", null, false);
                        ee()->db->where($key . ' IS NOT NULL', null, false);
                    } else {
                        ee()->db->where($key, $value);
                    }
                }
            }
        }

        ee()->db->where('channel_titles.channel_id', ee()->cartthrob->store->config('orders_channel'));

        if ($status) {
            ee()->db->where_not_in('status', $status);
        }

        ee()->db->group_by(['day']);
        ee()->db->order_by('day asc');

        $results = ee()->db->get();

        if ($results->num_rows == 0) {
            return $defaults;
        }

        $results = $results->result_array();

        return (count($results) === 1) ? array_shift($results) : $results;
    }

    /**
     * @param bool $where
     * @param bool $status
     * @param bool $just_total
     * @return array|float|int
     */
    public function totals($where = [], $status = false, $just_total = false)
    {
        $defaults = [
            'total' => 0,
            'subtotal' => 0,
            'tax' => 0,
            'shipping' => 0,
            'discount' => 0,
            'orders' => 0,
            'count' => 0,
        ];

        if (!ee()->cartthrob->store->config('orders_total_field') || !ee()->cartthrob->store->config('orders_channel')) {
            return ($just_total) ? 0 : $defaults;
        }

        $fields = [];

        foreach ([
                     'orders_total_field' => 'total',
                     'orders_subtotal_field' => 'subtotal',
                     'orders_subtotal_plus_tax_field' => 'subtotal_plus_tax',
                     'orders_tax_field' => 'tax',
                     'orders_shipping_field' => 'shipping',
                     'orders_shipping_plus_tax_field' => 'shipping_plus_tax',
                     'orders_discount_field' => 'discount',
                 ] as $field => $name) {
            if ($id = ee()->cartthrob->store->config($field)) {
                $fields[$id] = $name;
            }
        }

        ee()->db->select('month, year');
        ee()->db->select('COUNT(' . ee()->db->dbprefix('channel_titles') . '.entry_id) as count');

        foreach ($fields as $field => $name) {
            ee()->db->select_sum('field_id_' . $field, $name);
        }

        ee()->db->from('channel_titles');
        ee()->db->join('channel_data', 'channel_data.entry_id = channel_titles.entry_id');

        foreach ($fields as $id => $name) {
            if (!ee()->db->field_exists('field_id_' . $id, 'channel_data')) {
                ee()->db->join('channel_data_field_' . $id, 'channel_data_field_' . $id . '.entry_id = channel_titles.entry_id');
            }
        }

        if (!isset($where['entry_start_date'])) {
            $where['entry_start_date'] = 0;
        }
        if (!isset($where['entry_end_date'])) {
            $where['entry_end_date'] = strtotime('now');
        }

        if (isset($where['entry_start_date']) && isset($where['entry_end_date'])) {
            ee()->db->where('entry_date >=', $where['entry_start_date'] < $where['entry_end_date'] ? $where['entry_start_date'] : $where['entry_end_date']);
            ee()->db->where('entry_date <=', $where['entry_end_date'] > $where['entry_start_date'] ? $where['entry_end_date'] : $where['entry_start_date']);

            unset($where['entry_start_date']);
            unset($where['entry_end_date']);
        }

        if (!empty($where)) {
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    ee()->db->where_in($key, $value);
                } else {
                    if ($value == 'IS NOT NULL') {
                        ee()->db->where($key . " <> ''", null, false);
                        ee()->db->where($key . ' IS NOT NULL', null, false);
                    } else {
                        ee()->db->where($key, $value);
                    }
                }
            }
        }

        ee()->db->where('channel_titles.channel_id', ee()->cartthrob->store->config('orders_channel'));

        if ($status) {
            ee()->db->where_not_in('status', $status);
        }

        ee()->db->group_by(['month', 'year']);
        ee()->db->order_by('year asc, month asc');

        $results = ee()->db->get();

        if ($results->num_rows() === 0) {
            return [];
        }

        $data = $results->result_array();

        $data = ($results->num_rows() === 1) ? array_shift($data) : $data;

        return $data;
    }
}
