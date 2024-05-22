<?php

use ExpressionEngine\Service\Model\Query\Builder;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Order_management_model extends CI_Model
{
    private $cartthrob;

    /**
     * Order_management_model constructor.
     */
    public function __construct()
    {
        $this->load->model('cartthrob_entries_model');

        $this->cartthrob = ee()->cartthrob;
    }

    /**
     * @param $entry_id
     * @return array
     */
    public function get_related_orders_by_item($entry_id)
    {
        $query = $this->db->select('order_id')
            ->from('cartthrob_order_items')
            ->where('entry_id', $entry_id)
            ->get();

        return $query->num_rows() > 0 ? $query->result_array() : [];
    }

    /**
     * @param bool $memberId
     * @return bool
     */
    public function is_member($memberId = false)
    {
        ee()->load->model('cartthrob_members_model');
        $oldestSuperadmin = ee()->cartthrob_members_model->getOldestSuperAdmin();

        return $memberId && $memberId != $this->cartthrob->store->config('default_member_id') && $memberId != $oldestSuperadmin;
    }

    /**
     * @param array $where
     * @param string $order_by
     * @param string $sort
     * @param null $limit
     * @param null $offset
     * @param array $like
     * @param null $status
     * @return array
     */
    public function get_purchased_products($where = [], $order_by = 'total_sales', $sort = 'DESC', $limit = null, $offset = null, $like = [], $status = null)
    {
        if ($limit) {
            $this->db->limit($limit, $offset);
        }

        $this->db
            ->select_sum($this->db->dbprefix . 'cartthrob_order_items.price * ' . $this->db->dbprefix . 'cartthrob_order_items.quantity', 'total_sales')
            ->select_sum($this->db->dbprefix . 'cartthrob_order_items.quantity', 'total_quantity')
            ->select('cartthrob_order_items.*')
            ->from('cartthrob_order_items')
            ->from('channel_titles AS ct')
            ->where('ct.entry_id', $this->db->dbprefix . 'cartthrob_order_items.entry_id', false);

        if (!$status) {
            $status = $this->config->item('cartthrob:orders_default_status') ?? 'open';
        } elseif (strtolower($status) == 'any') {
            $status = null;
        }

        // now we need to ONLY get the completed items... again from channel_titles, which is why we aliased it previously a few lines above.
        $this->db->join('channel_titles', $this->db->dbprefix . 'channel_titles.entry_id = ' . $this->db->dbprefix . 'cartthrob_order_items.order_id');

        if ($status) {
            $this->db->where($this->db->dbprefix . 'channel_titles.status', $status);
        }

        if (!empty($where)) {
            $this->db->where($where);
        } elseif (!empty($like)) {
            $this->db->like($like);
        }

        if ($order_by) {
            $this->db->order_by($order_by, $sort);
        }

        $this->db
            ->group_by('cartthrob_order_items.entry_id')
            ->group_by('cartthrob_order_items.row_id')
            ->group_by('cartthrob_order_items.price');

        $query = $this->db->get();

        return $query->num_rows() > 0 ? $query->result_array() : [];
    }

    /**
     * @param $order_id
     * @return array|bool
     */
    public function get_purchased_items_by_order($order_id)
    {
        if (!$this->cartthrob->store->config('purchased_items_channel')) {
            return false;
        }

        if (ee('Model')->get('ChannelField', $this->config->item('cartthrob:purchased_items_order_id_field'))->count()) {
            $entries = ee('Model')
                ->get('ChannelEntry')
                ->fields('entry_id')
                ->filter('field_id_' . $this->cartthrob->store->config('purchased_items_order_id_field'), $order_id)
                ->all();

            $entryIds = [];

            foreach ($entries as $row) {
                $entryIds[] = $row->entry_id;
            }

            return $entryIds;
        }

        return false;
    }

    /**
     * @return int
     */
    public function getCustomerCount(): int
    {
        if (!$this->cartthrob->store->config('orders_channel') || !$this->cartthrob->store->config('orders_customer_email')) {
            return 0;
        }

        if ($this->db->field_exists('field_id_' . $this->cartthrob->store->config('orders_customer_email'),
            'channel_data')) {
            // this returns potentially more customers than are output, because some of the author ids might also contain different email addresses. get_customers is more accurate
            $this->db->select('COUNT(DISTINCT(field_id_' . $this->cartthrob->store->config('orders_customer_email') . ')) AS member_count',
                true);
            $this->db->from('channel_data');
            $this->db->join('channel_titles', 'channel_titles.entry_id = channel_data.entry_id');
            $this->db->where('channel_titles.channel_id', $this->cartthrob->store->config('orders_channel'));

            $data = $this->db->get()->row_array();

            return (int)$data['member_count'];
        }

        // this returns potentially more customers than are output, because some of the author ids might also contain different email addresses. get_customers is more accurate
        $this->db->select('COUNT(DISTINCT(field_id_' . $this->cartthrob->store->config('orders_customer_email') . ')) AS member_count',
            true);
        $this->db->from('channel_data_field_' . $this->cartthrob->store->config('orders_customer_email'));
        $this->db->join('channel_titles',
            'channel_titles.entry_id = channel_data_field_' . $this->cartthrob->store->config('orders_customer_email') . '.entry_id');
        $this->db->where('channel_titles.channel_id', $this->cartthrob->store->config('orders_channel'));

        $data = $this->db->get()->row_array();

        return (int)$data['member_count'];
    }

    /**
     * @param array $where
     * @param string $orderBy
     * @param string $direction
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function get_customers_reports($where = [], $orderBy = 'entry_date', $direction = 'DESC', $limit = null, $offset = null): array
    {
        $defaults = [
            'total' => 0,
            'subtotal' => 0,
            'tax' => 0,
            'shipping' => 0,
            'discount' => 0,
            'orders' => 0,
        ];
        $data = [];

        if (!$this->config->item('cartthrob:orders_total_field') || !$this->config->item('cartthrob:orders_channel')) {
            return $defaults;
        }

        $query = ee('Model')
            ->get('ChannelEntry')
            ->filter('channel_id', $this->cartthrob->store->config('orders_channel'))
            ->with('Channel');

        if ($where) {
            $whereIn = [];

            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    $whereIn[$key] = $value;
                    unset($where[$key]);
                } else {
                    $query->filter($key, '==', $value);
                }
            }

            foreach ($whereIn as $key => $value) {
                $query->filter($key, 'IN', $value);
            }
        }

        $this->applySortOrder($query, $orderBy, $direction);
        $this->applyLimitAndOffset($query, $limit, $offset);

        $entries = $query->all();
        $entryData = $entries->getValues();

        foreach ($entryData as $key => $value) {
            $data[] = $value;
        }

        return $data;
    }

    /**
     * @param array $where
     * @param string $order_by
     * @param string $sort
     * @param null $limit
     * @param null $offset
     * @return array
     */
    public function get_customers($where = [], $order_by = 'entry_date', $sort = 'DESC', $limit = null, $offset = null)
    {
        $prefix = $this->db->dbprefix;

        if ($this->db->field_exists('field_id_' . $this->cartthrob->store->config('orders_total_field'),
            'channel_data')) {
            $this->db->select('COUNT(' . $this->db->dbprefix . 'channel_data.entry_id) AS order_count');
            $this->db->select_sum('channel_data.field_id_' . $this->cartthrob->store->config('orders_total_field'),
                'order_total');
            $this->db->select('channel_data.*', false);
            $this->db->select('channel_titles.author_id', 'author_id');
            $this->db->select_min('channel_titles.entry_date', 'order_first');
            $this->db->select_max('channel_titles.entry_date', 'order_last');
            $this->db->where('channel_titles.channel_id', $this->cartthrob->store->config('orders_channel'));

            if ($where) {
                $this->db->where($where);
            }

            if ($order_by) {
                if (is_array($order_by)) {
                    foreach ($order_by as $key => $order_value) {
                        $sort_item = 'asc';
                        if (!empty($sort[$key])) {
                            $sort_item = $sort[$key];
                        }
                        $this->db->order_by($order_value, $sort_item);
                    }
                } else {
                    $this->db->order_by($order_by, $sort);
                }
            }

            if ($limit) {
                $this->db->limit($limit, $offset);
            }

            $this->db->where($this->db->dbprefix . 'channel_data.entry_id', $this->db->dbprefix . 'channel_titles.entry_id', false);

            if ($this->db->field_exists('field_id_' . $this->cartthrob->store->config('orders_customer_email'), 'channel_data')) {
                $this->db->where($this->db->dbprefix . 'channel_data.field_id_' . $this->cartthrob->store->config('orders_customer_email') . ' !=', '');
            }

            $this->db->from('channel_data');
            $this->db->from('channel_titles');

            // group by email address
            $this->db->group_by('channel_data.field_id_' . $this->cartthrob->store->config('orders_customer_email'),
                'author_id');

            $query = $this->db->get();

            if ($query->result() && $query->num_rows() > 0) {
                return $query->result_array();
            }
        } else {
            $this->db->select('COUNT(' . $this->db->dbprefix . 'channel_data.entry_id) AS order_count');

            $this->db->select_sum('channel_data_field_' . $this->cartthrob->store->config('orders_total_field') . '.field_id_' .
                $this->cartthrob->store->config('orders_total_field'), 'order_total');

            $this->db->select('channel_data.*', false);

            foreach ([
                        'orders_billing_first_name',
                        'orders_billing_last_name',
                        'orders_customer_email',
                        'orders_customer_phone',
                     ] as $field) {
                if ($fieldId = $this->cartthrob->store->config($field)) {
                    $this->db->select("channel_data_field_{$fieldId}.field_id_{$fieldId}", false);
                }
            }

            $this->db->select('channel_titles.author_id', 'author_id');
            $this->db->select_min('channel_titles.entry_date', 'order_first');
            $this->db->select_max('channel_titles.entry_date', 'order_last');
            $this->db->where('channel_titles.channel_id', $this->cartthrob->store->config('orders_channel'));

            if ($where) {
                $this->db->where($where);
            }

            if ($order_by) {
                if (is_array($order_by)) {
                    foreach ($order_by as $key => $order_value) {
                        $sort_item = 'asc';
                        if (!empty($sort[$key])) {
                            $sort_item = $sort[$key];
                        }
                        $this->db->order_by($order_value, $sort_item);
                    }
                } else {
                    $this->db->order_by($order_by, $sort);
                }
            }

            if ($limit) {
                $this->db->limit($limit, $offset);
            }

            $this->db->where($this->db->dbprefix . 'channel_data.entry_id', $this->db->dbprefix . 'channel_titles.entry_id', false);
            $this->db->where($this->db->dbprefix . 'channel_data_field_' . $this->cartthrob->store->config('orders_customer_email') . '.field_id_' . $this->cartthrob->store->config('orders_customer_email') . ' !=', '');
            $this->db->from('channel_data');
            $this->db->from('channel_titles');

            foreach ([
                        'orders_customer_email',
                        'orders_total_field',
                        'orders_billing_first_name',
                        'orders_billing_last_name',
                        'orders_customer_phone',
                     ] as $field) {
                if ($fieldId = $this->cartthrob->store->config($field)) {
                    $this->db->join("channel_data_field_{$fieldId}", "{$prefix}channel_data_field_{$fieldId}.entry_id={$prefix}channel_data.entry_id");
                }
            }

            $this->db
                ->group_by('exp_channel_data.entry_id')
                ->group_by('channel_data_field_' . $this->cartthrob->store->config('orders_customer_email') . '.field_id_' . $this->cartthrob->store->config('orders_customer_email'), 'author_id')
                ->group_by('channel_data_field_' . $this->cartthrob->store->config('orders_customer_phone') . '.field_id_' . $this->cartthrob->store->config('orders_customer_phone'));

            $query = $this->db->get();

            if ($query->result() && $query->num_rows() > 0) {
                return $query->result_array();
            }
        }

        return [];
    }

    /**
     * @param array $where
     * @param string $orderBy
     * @param string $direction
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function getOrders($where = [], $orderBy = 'entry_date', $direction = 'DESC', $limit = null, $offset = null)
    {
        $defaults = [
            'total' => 0,
            'subtotal' => 0,
            'tax' => 0,
            'shipping' => 0,
            'discount' => 0,
            'orders' => 0,
        ];
        $data = [
            'order_total' => 0,
            'order_count' => 0,
        ];
        $ordersTotalField = 'field_id_' . $this->config->item('cartthrob:orders_total_field');

        if (!$this->config->item('cartthrob:orders_total_field') || !$this->config->item('cartthrob:orders_channel')) {
            return $defaults;
        }

        $query = ee('Model')
            ->get('ChannelEntry')
            ->filter('channel_id', $this->cartthrob->store->config('orders_channel'))
            ->with('Channel');

        $this->applyWhere($query, $where);
        $this->applySortOrder($query, $orderBy, $direction);
        $this->applyLimitAndOffset($query, $limit, $offset);

        $entries = $query->all();

        if (count($entries) <= 0) {
            return $data;
        }

        foreach ($entries as $entry) {
            $data['order_total'] += (float)$entry->{$ordersTotalField};
        }

        $data['order_count'] = $this->getOrderCount($where);

        return array_map(function ($value) use ($data) {
            return array_merge($value, $data);
        }, $entries->getValues());
    }

    /**
     * @param $query
     * @param array|string $orderBy
     * @param array|string $direction
     */
    private function applySortOrder(Builder $query, $orderBy, $direction): void
    {
        if (!$orderBy) {
            return;
        }

        if (is_array($orderBy)) {
            foreach ($orderBy as $sortKey => $sortValue) {
                $query->order(
                    $sortValue,
                    !empty($direction[$sortKey]) ? $direction[$sortKey] : 'ASC'
                );
            }
        } else {
            $query->order($orderBy, $direction);
        }
    }

    /**
     * @param $query
     * @param array $where
     */
    private function applyWhere(Builder $query, array $where): void
    {
        if (!$where || count($where) <= 0) {
            return;
        }

        $whereIn = [];

        foreach ($where as $key => $value) {
            if (is_array($value)) {
                $whereIn[$key] = $value;
                unset($where[$key]);
            } elseif ($key == 'entry_start_date') {
                $query->filter('entry_date', '>=', $value);
                unset($where[$key]);
            } elseif ($key == 'entry_end_date') {
                $query->filter('entry_date', '<=', $value);
                unset($where[$key]);
            } else {
                $query->filter($key, '==', $value);
            }
        }

        foreach ($whereIn as $key => $value) {
            $query->filter($key, 'IN', $value);
        }
    }

    /**
     * @param Builder $query
     * @param int $limit
     * @param int $offset
     */
    private function applyLimitAndOffset(Builder $query, $limit = 0, $offset = 0): void
    {
        if (!$limit && !$offset) {
            return;
        }

        $query
            ->offset($offset)
            ->limit($limit);
    }

    /**
     * @param $where
     * @return int
     */
    private function getOrderCount(array $where): int
    {
        $this->db
            ->from('channel_titles')
            ->where('channel_id', $this->config->item('cartthrob:orders_channel'))
            ->select('COUNT(*) AS count');

        if (isset($where['entry_start_date'])) {
            $this->db
                ->where('entry_date >=', $where['entry_start_date'])
                ->where('entry_date <', $where['entry_end_date']);
        }

        return $this->db->get()->row()->count;
    }
}
