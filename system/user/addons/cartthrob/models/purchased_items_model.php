<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Purchased_items_model extends CI_Model
{
    /**
     * Purchased_items_model constructor.
     */
    public function __construct()
    {
        $this->load->model('cartthrob_settings_model');
        $this->load->model('cartthrob_entries_model');
    }

    /**
     * @param $order_id
     * @return array
     */
    public function get_purchased_items($order_id)
    {
        if (!$this->config->item('cartthrob:purchased_items_order_id_field')) {
            return [];
        }

        return $this->cartthrob_entries_model->find_entries([
            'field_id_' . $this->config->item('cartthrob:purchased_items_order_id_field') => $order_id,
        ]);
    }

    /**
     * @return array
     */
    public function purchased_entry_ids()
    {
        $query = $this->db->select('field_id_' . $this->config->item('cartthrob:purchased_items_id_field') . ' AS entry_id')
            ->distinct()
            ->join('channel_data_field_' . $this->config->item('cartthrob:purchased_items_id_field'),
                'channel_data.entry_id = channel_data_field_' . $this->config->item('cartthrob:purchased_items_id_field') . '.entry_id')
            ->where('channel_id', $this->config->item('cartthrob:purchased_items_channel'))
            ->get('channel_data');

        $entry_ids = [];

        foreach ($query->result() as $row) {
            $entry_ids[] = $row->entry_id;
        }

        return $entry_ids;
    }

    /**
     * @param $entry_id
     * @param bool $limit
     * @return array
     */
    public function also_purchased($entry_id, $limit = false)
    {
        static $cache;

        if (isset($cache[$entry_id])) {
            return $cache[$entry_id];
        }

        if (!$entry_id || !$this->config->item('cartthrob:orders_channel')) {
            return [];
        }

        $purchased = [];

        $query = $this->db->select('order_id')
            ->distinct()
            ->from('cartthrob_order_items')
            ->where('entry_id', $entry_id)
            ->get();

        if ($query->num_rows() === 0) {
            return [];
        }

        $order_ids = [];

        foreach ($query->result() as $row) {
            $order_ids[] = $row->order_id;
        }

        $query->free_result();

        $query = $this->db->select('entry_id')
            ->distinct()
            ->from('cartthrob_order_items')
            ->where_in('order_id', $order_ids)
            ->where('entry_id !=', $entry_id)
            ->get();

        if ($query->num_rows() === 0) {
            return [];
        }

        foreach ($query->result() as $row) {
            if (isset($purchased[$row->entry_id])) {
                $purchased[$row->entry_id]++;
            } else {
                $purchased[$row->entry_id] = 1;
            }
        }

        $query->free_result();

        if (!$limit) {
            $limit = 20;
        }

        arsort($purchased);

        $purchased = array_slice($purchased, 0, $limit, true);

        $cache[$entry_id] = $purchased;

        return $purchased;
    }

    /**
     * @param $entry_id
     * @return bool
     */
    public function has_purchased($entry_id)
    {
        if (!$this->config->item('cartthrob:orders_channel')) {
            return false;
        }

        $status = $this->config->item('cartthrob:orders_default_status') ? $this->config->item('cartthrob:orders_default_status') : 'open';

        $site_id = $this->db->select('site_id')->where('channel_id',
            $this->config->item('cartthrob:orders_channel'))->get('channels')->row('site_id');

        $this->db->from('cartthrob_order_items')
            ->join('channel_titles', 'channel_titles.entry_id = cartthrob_order_items.order_id')
            ->where('cartthrob_order_items.entry_id', $entry_id)
            ->where('channel_titles.author_id', $this->session->userdata('member_id'))
            ->where('channel_titles.site_id', $site_id)
            ->where('channel_titles.status', $status)
            ->where('channel_titles.channel_id', $this->config->item('cartthrob:orders_channel'));

        return $this->db->count_all_results() > 0;
    }

    /**
     * @param $entry_id
     * @param $data
     * @return mixed
     */
    public function update_purchased_item($entry_id, $data)
    {
        return $this->cartthrob_entries_model->update($entry_id, $data);
    }

    /**
     * create_purchased_item
     *
     * creates ONE purchased item. Item data array should contain:
     *
     * product_id (basically entry id)
     *
     * @param array $item_data
     * @param string $order_id
     * @param string $status
     * @return string entry_id
     */
    public function create_purchased_item($item_data, $order_id, $status)
    {
        $this->load->model('cartthrob_members_model');
        $this->load->model('order_model');
        $this->load->helper('url');

        if (!$channel_id = $this->config->item('cartthrob:purchased_items_channel')) {
            return 0;
        }

        $product = $this->cartthrob->store->product($item_data['product_id']);

        $title = element('title', $item_data);

        if ($product && !$title) {
            $title = $product->title();
        }

        $wordSeparator = '_';
        switch ($this->config->item('word_separator')) {
            case 'dash':
                $wordSeparator = '-';
                break;
            default:
                $wordSeparator = '_';
        }

        $order_entry = $this->order_model->getOrder($order_id);

        $data = [
            'title' => $this->config->item('cartthrob:purchased_items_title_prefix') . $title,
            'url_title' => ee('Format')->make('Text', substr($title, 0, 35))->urlSlug(['separator' => $wordSeparator, 'lowercase' => true]) . $wordSeparator . uniqid('', true),
            'author_id' => $order_entry['author_id'],  // $this->cartthrob_members_model->get_member_id(),
            'channel_id' => $channel_id,
            'status' => ($status) ? $status : 'closed',
        ];

        $purchasedItemChannel = $this->config->item('cartthrob:purchased_items_channel');
        // double check that the purchased item channel
        // is a valid EE channel before proceding
        $queryResults = $this->db->select('site_id')->where('channel_id', $purchasedItemChannel)->get('channels');
        if ($queryResults->num_rows() == 0) {
            // channel did not exist, impossible to purchase item
            log_message('error creating a purchased item!',
                'Invalid channel for purchased item. Make sure the channel exists in both the CartThrob configuration and ExpressionEngine.');

            return false; // no entry id to return!
        } else {
            // channel exists!
            $data['site_id'] = $queryResults->row('site_id');
        }

        if (!empty($item_data['meta']['expires'])) {
            $data['expiration_date'] = $this->localize->now + ($item_data['meta']['expires'] * 24 * 60 * 60);
        }

        if ($this->config->item('cartthrob:purchased_items_id_field') && isset($item_data['product_id'])) {
            $data['field_id_' . $this->config->item('cartthrob:purchased_items_id_field')] = $item_data['product_id'];
        }

        if ($this->config->item('cartthrob:purchased_items_quantity_field') && isset($item_data['quantity'])) {
            $data['field_id_' . $this->config->item('cartthrob:purchased_items_quantity_field')] = $item_data['quantity'];
        }

        if ($this->config->item('cartthrob:purchased_items_price_field')) {
            if (!empty($item_data['price'])) {
                $data['field_id_' . $this->config->item('cartthrob:purchased_items_price_field')] = $item_data['price'];
            } else {
                if ($product) {
                    $data['field_id_' . $this->config->item('cartthrob:purchased_items_price_field')] = $product->price();
                }
            }
        }

        if ($this->config->item('cartthrob:purchased_items_order_id_field') && $order_id) {
            $data['field_id_' . $this->config->item('cartthrob:purchased_items_order_id_field')] = $order_id;
        }

        if ($this->config->item('cartthrob:purchased_items_package_id_field') && $order_id) {
            if (!empty($item_data['package_id'])) {
                $data['field_id_' . $this->config->item('cartthrob:purchased_items_package_id_field')] = $item_data['package_id'];
            }
        }

        if ($this->config->item('cartthrob:purchased_items_license_number_field') && !empty($item_data['meta']['license_number'])) {
            $limit = 25;

            $license_number_field = 'field_id_' . $this->config->item('cartthrob:purchased_items_license_number_field');

            $license_number_field = 'field_id_' . $this->config->item('cartthrob:purchased_items_license_number_field');

            do {
                $license_number = generate_license_number($this->config->item('cartthrob:purchased_items_license_number_type'));

                $licenses = ee('Model')->get('ChannelEntry')
                    ->filter($license_number_field, $license_number)
                    ->count();

                $limit--;
            } while ($licenses > 0 && $limit >= 0);

            if ($limit >= 0 && $license_number) {
                $data['field_id_' . $this->config->item('cartthrob:purchased_items_license_number_field')] = $license_number;
            }
        }

        if ($this->config->item('cartthrob:purchased_items_discount_field')) {
            if (!empty($item_data['discount'])) {
                $data['field_id_' . $this->config->item('cartthrob:purchased_items_discount_field')] = $item_data['discount'];
            }
        }

        // @NOTE: this should not be operating on POST data.
        // a controller should be setting up this stuff, not the model.
        foreach ($this->cartthrob_field_model->get_fields_by_channel($channel_id) as $field) {
            if ($this->input->post($field['field_name']) !== false) {
                $field_data = $this->input->post($field['field_name'], true);
                if (is_array($field_data)) {
                    $field_data = implode('|', $field_data);
                }
                $data['field_id_' . $field['field_id']] = $field_data;
            }

            if (isset($item_data['item_options'][$field['field_name']])) {
                $field_data = $item_data['item_options'][$field['field_name']];
                if (is_array($field_data)) {
                    $field_data = implode('|', $field_data);
                }
                $data['field_id_' . $field['field_id']] = $field_data;
            }

            // this looks for a field like "purchased_seller" and an item option called "seller"
            if (preg_match('/^purchased_(.*)/', $field['field_name'],
                $match) && isset($item_data['item_options'][$match[1]])) {
                $field_data = $item_data['item_options'][$match[1]];
                if (is_array($field_data)) {
                    $field_data = implode('|', $field_data);
                }
                $data['field_id_' . $field['field_id']] = $field_data;
            }

            // this looks for fields like "seller" where you have a purchased items channel field called purchased_seller
            if (preg_match('/^purchased_(.*)/', $field['field_name'],
                $match) && ($this->input->post($match[1]) !== false)) {
                $field_data = $this->input->post($match[1], true);
                if (is_array($field_data)) {
                    $field_data = implode('|', $field_data);
                }
                $data['field_id_' . $field['field_id']] = $field_data;
            }
        }

        return $this->cartthrob_entries_model->create_entry($data);
    }
}
