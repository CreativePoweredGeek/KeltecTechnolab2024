<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use CartThrob\Plugins\Discount\ValidateCartInterface;

class Discount_model extends CI_Model
{
    private $discount_data;

    public function __construct()
    {
        $this->load->model('cartthrob_settings_model');

        $this->load->model('cartthrob_entries_model');
    }

    public function get_all_discounts()
    {
        if (!($channel = $this->config->item('cartthrob:discount_channel'))) {
            return [];
        }

        $filter = [
            'channel_titles.status !=' => 'closed',
            'channel_titles.channel_id' => $this->config->item('cartthrob:discount_channel'),
            'channel_titles.entry_date <=' => $this->localize->now,
            // 'channel_titles.expiration_date >=' => $this->localize->now,
        ];

        $ctEntries = $this->cartthrob_entries_model->find_entries($filter);
        $output = [];

        $entryIds = array_keys($ctEntries);
        if (!$entryIds || empty($entryIds)) {
            return $output;
        }

        if (!$entryIds || empty($entryIds)) {
            return $output;
        }

        $entries = ee('Model')->get('ChannelEntry')
            ->with('Channel')
            ->filter('entry_id', 'IN', $entryIds)
            ->filter('site_id', ee()->config->item('site_id'))
            ->all();

        if ($this->config->item('cartthrob:discount_type')) {
            foreach ($entries as $entry) {
                $entryValues = $entry->getValues();
                $data = _unserialize($entryValues['field_id_' . $this->config->item('cartthrob:discount_type')], true);
                $entryData = [
                    'entry_id' => $entryValues['entry_id'],
                    'title' => $entryValues['title'],
                    'url_title' => $entryValues['url_title'],
                    'status' => $entryValues['status'],
                ];

                $output[$entryValues['entry_id']] = array_merge($entryData, $data);
            }
        }

        return $output;
    }

    public function process_discounts()
    {
        foreach ($this->get_valid_discounts() as $entry_id => $data) {
            // discount_type is a reference to the field that stores the discounts.
            if ($this->config->item('cartthrob:discount_type')) {
                $data['used_by'] = (!empty($data['used_by'])) ? $data['used_by'] . '|' . $this->session->userdata('member_id') : $this->session->userdata('member_id');

                $data['discount_limit'] = (isset($data['discount_limit']) && strlen($data['discount_limit']) > 0) ? $data['discount_limit'] - 1 : '';

                $discount_channel_data = [
                    'field_id_' . $this->config->item('cartthrob:discount_type') => $data,
                ];
                $entry = ee('Model')->get('ChannelEntry', $entry_id)
                    ->filter('site_id', ee()->config->item('site_id'))
                    ->first();
                $entry->site_id = ee()->config->item('site_id');
                $entry->set($discount_channel_data);
                $entry->save();
            }
        }
    }

    /**
     * @return array
     */
    public function get_valid_discounts()
    {
        if (is_null($this->discount_data)) {
            $this->discount_data = [];

            $this->load->library('api/api_cartthrob_discount_plugins');
            if ($this->config->item('cartthrob:discount_channel') && $this->config->item('cartthrob:discount_type')) {
                $filter = [
                    'channel_titles.status !=' => 'closed',
                    'channel_titles.channel_id' => $this->config->item('cartthrob:discount_channel'),
                    'channel_titles.entry_date <=' => $this->localize->now,
                    // 'channel_titles.expiration_date >=' => $this->localize->now,
                ];

                // cartthrob_discount_filter hook
                if ($this->extensions->active_hook('cartthrob_discount_filter') === true) {
                    $filter = $this->extensions->call('cartthrob_discount_filter', $filter);
                }

                $entries = $this->cartthrob_entries_model->find_entries($filter);
                $products_in_cart = ee()->cartthrob->cart->product_ids();

                foreach ($entries as &$entry) {
                    if ($entry['expiration_date'] && $entry['expiration_date'] <= $this->localize->now) {
                        continue;
                    }

                    $entryget = ee('Model')->get('ChannelEntry', $entry['entry_id'])
                        ->with('Channel')
                        ->first();
                    $myarr = $entryget->getValues();

                    $data = _unserialize($myarr['field_id_' . $this->config->item('cartthrob:discount_type')], true);

                    if (!isset($data['type'])) {
                        continue;
                    }

                    $used_by = (!empty($data['used_by'])) ? array_count_values(preg_split('#\s*[,|]\s*#',
                        trim($data['used_by']))) : [];

                    if (!empty($data['per_user_limit']) && isset($used_by[$this->session->userdata('member_id')]) &&
                        ($used_by[$this->session->userdata('member_id')] >= $data['per_user_limit'])
                    ) {
                        continue;
                    }

                    if (isset($data['discount_limit']) && $data['discount_limit'] !== '' && $data['discount_limit'] <= 0) {
                        continue;
                    }

                    if (!empty($data['member_groups']) && !in_array($this->session->userdata('group_id'),
                        preg_split('#\s*[,|]\s*#', trim($data['member_groups'])))) {
                        continue;
                    }

                    if (!empty($data['member_ids']) && !in_array($this->session->userdata('member_id'),
                        preg_split('#\s*[,|]\s*#', trim($data['member_ids'])))) {
                        continue;
                    }

                    if (!empty($data['entry_ids']) && !$this->productInCart($data, $products_in_cart)) {
                        continue;
                    }

                    $data['entry_id'] = $entry['entry_id'];

                    $plugin = $this->api_cartthrob_discount_plugins->set_plugin($data['type'])->plugin();

                    if ($plugin instanceof ValidateCartInterface) {
                        if (!$plugin->set_plugin_settings($data)->validateCart()) {
                            continue;
                        }
                    }

                    $this->discount_data[$entry['entry_id']] = $data;
                }
            }

            if ($this->extensions->active_hook('cartthrob_get_valid_discounts_end') === true) {
                $this->discount_data = $this->extensions->call('cartthrob_get_valid_discounts_end',
                    $this->discount_data);
            }
        }

        return $this->discount_data;
    }

    /**
     * @param array $data
     * @param array $products_in_cart
     * @return bool
     */
    protected function productInCart(array $data = [], array $products_in_cart = []): bool
    {
        if (empty($data['entry_ids'])) {
            return false;
        }

        foreach (explode(',', $data['entry_ids']) as $product) {
            if (ee()->cartthrob->cart->hasProduct($product)) {
                return true;
            }
        }

        return false;
    }
}
