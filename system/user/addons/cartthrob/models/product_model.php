<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use CartThrob\Math\Number;

class Product_model extends CI_Model
{
    private $category_posts = [];

    /**
     * Product_model constructor.
     */
    public function __construct()
    {
        $this->load->model('cartthrob_settings_model');
        $this->load->model('cartthrob_entries_model');
        $this->load->helper('array');
    }

    /**
     * Returns an array of product entry_id's of products within the specified price range
     * In order to get this to work, you need to change the MySQL field type of your price field to INT or FLOAT
     *
     * @param float $price_min
     * @param float $price_max
     * @return array
     */
    public function get_products_in_price_range($price_min, $price_max)
    {
        $this->load->model('cartthrob_field_model');

        $entry_ids = [];

        $channel_ids = ($this->config->item('cartthrob:product_channels')) ? $this->config->item('cartthrob:product_channels') : [];

        foreach ($channel_ids as $channel_id) {
            if ($field_id = array_value($this->config->item('cartthrob:product_channel_fields'), $channel_id,
                'price')) {
                $MaxMin = ee('Model')->get('ChannelEntry')
                    ->with('Channel')
                    ->fields('entry_id')
                    ->filter('field_id_' . $field_id, '!=', '');

                if ($price_min !== '' && $price_min !== false) {
                    $MaxMin->filter('field_id_' . $field_id, '>=', $price_min);
                }
                if ($price_max !== '' && $price_max !== false) {
                    $MaxMin->filter('field_id_' . $field_id, '<=', $price_max);
                }
                $results = $MaxMin->all();

                // $myarr = $MaxMin->getValues();
                foreach ($results as $entries) {
                    $entry_ids[] = $entries->entry_id;
                }
            }
        }

        return $entry_ids;
    }

    /**
     * @param $entry_id
     * @return bool|mixed
     */
    public function get_base_price($entry_id)
    {
        $price = false;
        $data = $this->get_product($entry_id);

        if (!$channel_id = element('channel_id', $data)) {
            return false;
        }

        if (!$field_id = array_value($this->config->item('cartthrob:product_channel_fields'), $channel_id, 'price')) {
            return false;
        }
        $this->load->model('cartthrob_field_model');

        if ($field_type = $this->cartthrob_field_model->get_field_type($field_id)) {
            $this->load->library('api');
            $this->legacy_api->instantiate('channel_fields');
            $this->api_channel_fields->include_handler($field_type);

            if ($this->api_channel_fields->setup_handler($field_type) && $this->api_channel_fields->check_method_exists('cartthrob_price')) {
                return $this->api_channel_fields->apply('cartthrob_price', [$data['field_id_' . $field_id]]);
            } else {
                $FieldName = ee('Model')->get('ChannelField', $field_id)->fields('field_name')->first();
                $GetName = $FieldName->getValues();

                if (array_key_exists($GetName['field_name'], $data)) {
                    return $data['price'];
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * @param $entryId
     * @return array
     */
    public function get_product($entryId)
    {
        if (!$product = $this->cartthrob_entries_model->entry($entryId)) {
            return [];
        }

        $fieldIds = [];

        foreach ($this->cartthrob_field_model->get_fields_by_channel($product['channel_id']) as $fieldId) {
            $fieldIds[] = $fieldId['field_id'];
        }

        foreach ($product as $key => $value) {
            if (substr($key, 0, 6) !== 'field_') {
                continue;
            }

            $fieldId = substr($key, 9);

            if (!in_array($fieldId, $fieldIds)) {
                unset($product[$key]);
            }
        }

        foreach (['inventory', 'price', 'weight', 'shipping'] as $key) {
            $fieldId = array_value($this->config->item('cartthrob:product_channel_fields'), $product['channel_id'], $key);

            if ($fieldId) {
                $product[$key] = $product['field_id_' . $fieldId] ?? 0;
            }
        }

        $product['product_id'] = $entryId;

        if (!isset($this->category_posts[$entryId])) {
            $query = $this->db->select('cat_id')
                ->where('entry_id', $entryId)
                ->get('category_posts');

            $this->category_posts[$entryId] = [];

            foreach ($query->result() as $row) {
                $this->category_posts[$entryId][] = $row->cat_id;
            }
        }

        $product['categories'] = $this->category_posts[$entryId];

        return $product;
    }

    /**
     * @param $entry_id
     * @param $field_name
     * @param $option_value
     * @return bool|mixed
     */
    public function get_price_modifier_value($entry_id, $field_name, $option_value)
    {
        $this->load->model('cartthrob_field_model');
        $modifier = $this->get_price_modifiers($entry_id, $this->cartthrob_field_model->get_field_id($field_name));

        foreach ($modifier as $mod) {
            $current_option_value = element('option_value', $mod);
            if ($current_option_value !== false && $current_option_value == $option_value) {
                return $mod;
            }
        }

        return false;
    }

    /**
     * @param $entry_id
     * @param $field_id
     * @return array|mixed
     */
    public function get_price_modifiers($entry_id, $field_id)
    {
        $price_modifiers = $this->get_all_price_modifiers($entry_id);

        $field_name = $this->cartthrob_field_model->get_field_name($field_id);

        return (isset($price_modifiers[$field_name])) ? $price_modifiers[$field_name] : [];
    }

    /**
     * @param $entry_id
     * @param bool $configurations
     * @return array
     */
    public function get_all_price_modifiers($entry_id, $configurations = true)
    {
        if (isset($this->session->cache['cartthrob']['product_model']['all_price_modifiers'][$entry_id])) {
            return $this->session->cache['cartthrob']['product_model']['all_price_modifiers'][$entry_id];
        }

        $price_modifiers = [];

        if ($this->extensions->active_hook('cartthrob_get_all_price_modifiers') === true) {
            // @TODO hook params
            $additional_price_modifiers = $this->extensions->call('cartthrob_get_all_price_modifiers', $entry_id);

            if ($this->extensions->end_script === true) {
                // we need to turn this back on, otherwise this hook can't get called again, and that's not really the point.
                // if it gets called again, and can't be called, we get a white screen of death
                $this->extensions->end_script = false;
                $this->session->cache['cartthrob']['product_model']['all_price_modifiers'][$entry_id] = $additional_price_modifiers;

                return $additional_price_modifiers;
            }

            if (is_array($additional_price_modifiers)) {
                $price_modifiers = $additional_price_modifiers;
            }
        }

        $product = $this->get_product($entry_id);

        $field_ids = [];

        if (!empty($product['channel_id'])) {
            foreach ($this->cartthrob_field_model->get_fields_by_channel($product['channel_id']) as $field) {
                if (strncmp($field['field_type'], 'cartthrob_price_modifiers', 25) === 0) {
                    $field_ids[] = $field['field_id'];
                } else {
                    if ($field['field_type'] === 'matrix') {
                        $cols = $this->cartthrob_field_model->get_matrix_cols($field['field_id']);

                        $is_price_modifier = false;

                        foreach ($cols as $col) {
                            if ($col['col_name'] === 'option_value') {
                                $is_price_modifier = true;
                                break;
                            }
                        }

                        if ($is_price_modifier) {
                            $field_ids[] = $field['field_id'];
                        }
                    } elseif ($field['field_type'] === 'grid') {
                        $cols = $this->cartthrob_field_model->get_grid_cols($field['field_id']);

                        $is_price_modifier = false;

                        foreach ($cols as $col) {
                            if ($col['col_name'] === 'option_value') {
                                $is_price_modifier = true;
                                break;
                            }
                        }

                        if ($is_price_modifier) {
                            $field_ids[] = $field['field_id'];
                        }
                    }
                }
            }
        }

        // $field_ids = array_value($this->config->item('cartthrob:product_channel_fields'), $product['channel_id'], 'price_modifiers');

        if ($field_ids && $product) {
            foreach ($field_ids as $field_id) {
                if (!array_key_exists('field_id_' . $field_id, $product)) {
                    continue;
                }

                $field_type = $this->cartthrob_field_model->get_field_type($field_id);
                if ($field_type == 'matrix') {
                    $cols = $this->cartthrob_field_model->get_matrix_cols($field_id);
                    $rows = $this->cartthrob_field_model->get_matrix_rows($entry_id, $field_id);
                    $data = [];

                    foreach ($rows as $row) {
                        $_row = [
                            'option_name' => '',
                            'option_value' => '',
                            'price' => 0,
                            'inventory' => '',
                        ];

                        foreach ($cols as $col) {
                            switch ($col['col_name']) {
                                case 'option':
                                    $_row['option_value'] = $row['col_id_' . $col['col_id']];
                                    break;
                                default:
                                    $_row[$col['col_name']] = $row['col_id_' . $col['col_id']];
                            }
                        }

                        $data[] = $_row;
                    }

                    $price_modifiers[$this->cartthrob_field_model->get_field_name($field_id)] = $data;
                } elseif ($field_type == 'grid') {
                    $cols = $this->cartthrob_field_model->get_grid_cols($field_id);
                    $rows = $this->cartthrob_field_model->get_grid_rows($entry_id, $field_id);
                    $data = [];
                    foreach ($rows as $row) {
                        $_row = [
                            'option_name' => '',
                            'option_value' => '',
                            'price' => 0,
                            'inventory' => '',
                        ];

                        foreach ($cols as $col) {
                            switch ($col['col_name']) {
                                case 'option':
                                    $_row['option_value'] = $row['col_id_' . $col['col_id']];
                                    break;
                                default:
                                    $_row[$col['col_name']] = $row['col_id_' . $col['col_id']];
                            }
                        }

                        $data[] = $_row;
                    }

                    $price_modifiers[$this->cartthrob_field_model->get_field_name($field_id)] = $data;
                } else {
                    $this->load->library('api');
                    $this->legacy_api->instantiate('channel_fields');
                    $this->api_channel_fields->include_handler($field_type);

                    if ($this->api_channel_fields->setup_handler($field_type) && $this->api_channel_fields->check_method_exists('item_option_groups') && $configurations == true) {
                        $field_short_name = $this->cartthrob_field_model->get_field_name($field_id);

                        $groups = $this->api_channel_fields->apply('item_option_groups',
                            [_unserialize($product['field_id_' . $field_id], true), $field_short_name]);
                        $price_modifiers[$this->cartthrob_field_model->get_field_name($field_id)] = $this->api_channel_fields->apply('item_options',
                            [_unserialize($product['field_id_' . $field_id], true)]);

                        foreach ($groups as $config => $group) {
                            $price_modifiers['configuration:' . $field_short_name . ':' . $config] = $group;
                        }
                    } elseif ($this->api_channel_fields->setup_handler($field_type) && $this->api_channel_fields->check_method_exists('item_options')) {
                        $price_modifiers[$this->cartthrob_field_model->get_field_name($field_id)] = $this->api_channel_fields->apply('item_options',
                            [_unserialize($product['field_id_' . $field_id], true)]);
                    } else {
                        $price_modifiers[$this->cartthrob_field_model->get_field_name($field_id)] = _unserialize($product['field_id_' . $field_id],
                            true);
                    }
                }
            }
        }
        if ($this->extensions->active_hook('cartthrob_get_all_price_modifiers_end') === true) {
            $updated_price_modifiers = $this->extensions->call('cartthrob_get_all_price_modifiers_end',
                $price_modifiers);

            if (is_array($updated_price_modifiers)) {
                $price_modifiers = $updated_price_modifiers;
            }
            if ($this->extensions->end_script === true) {
                // we need to turn this back on, otherwise this hook can't get called again, and that's not really the point.
                // if it gets called again, and can't be called, we get a white screen of death
                $this->extensions->end_script = false;
            }
        }
        $this->session->cache['cartthrob']['product_model']['all_price_modifiers'][$entry_id] = $price_modifiers;
        $this->load->add_package_path(PATH_THIRD . 'cartthrob');

        return $price_modifiers;
    }

    /**
     * @param $entry_id
     * @param int $quantity
     * @param array $item_options
     * @return false|float|int
     */
    public function check_inventory($entry_id, $quantity = 1, $item_options = [])
    {
        $data = $this->get_product($entry_id);

        if (!$channel_id = element('channel_id', $data)) {
            return false;
        }

        if (!$field_id = array_value($this->config->item('cartthrob:product_channel_fields'), $channel_id, 'inventory')) {
            return false;
        }

        $this->load->model('cartthrob_field_model');

        $field_type = $this->cartthrob_field_model->get_field_type($field_id);

        if ($this->isModifier($field_type)) {
            $field_name = $this->cartthrob_field_model->get_field_name($field_id);

            // getting the price modifiers
            $price_modifiers = $this->get_all_price_modifiers($entry_id, $get_configurations = false);
            foreach ($price_modifiers as $index => $price_modifier) {
                if (array_key_exists('inventory', $price_modifier) && $price_modifier['inventory'] !== '' &&
                    isset($item_options[$field_name]) && $item_options[$field_name] == $price_modifier['option_value']
                ) {
                    return Number::sanitize($price_modifier['inventory']) - Number::sanitize($quantity);
                }
            }

            return false;
        }

        return false;
    }

    /**
     * @param $entry_id
     * @param $field_name
     * @param array $configuration
     */
    public function get_base_variation($entry_id, $field_name, $configuration = [])
    {
        $this->load->library('api');
        $this->load->model('cartthrob_field_model');
        $this->legacy_api->instantiate('channel_fields');

        $field_id = $this->cartthrob_field_model->get_field_id($field_name);
        $field_type = $this->cartthrob_field_model->get_field_type($field_id);

        $this->api_channel_fields->include_handler($field_type);

        if ($this->api_channel_fields->setup_handler($field_type) && $this->api_channel_fields->check_method_exists('compare') && $this->api_channel_fields->check_method_exists('item_option_groups')) {
            $product = $this->get_product($entry_id);
            $field_ids = [];

            if (empty($product['channel_id'])) {
                return null;
            }

            if (!array_key_exists('field_id_' . $field_id, $product)) {
                return null;
            }
            $data = _unserialize($product['field_id_' . $field_id], true);

            $sku = $this->api_channel_fields->apply('compare', [$data, $configuration]);

            $this->load->add_package_path(PATH_THIRD . 'cartthrob');
            if ($sku !== null && $sku !== false && $sku != '') {
                return $sku;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * @param $entry_id
     * @param int $quantity
     * @param array $item_options
     * @return bool|float|int
     */
    public function increaseInventory($entry_id, $quantity = 1, $item_options = [])
    {
        return $this->adjust_inventory($entry_id, $quantity, $item_options, false);
    }

    /**
     * @param $entry_id
     * @param int $quantity
     * @param array $item_options
     * @param bool $reduce
     * @return bool|float|int|void
     */
    public function adjust_inventory($entry_id, $quantity = 1, $item_options = [], $reduce = true)
    {
        $inventory = false;
        $data = $this->get_product($entry_id);

        if (!$channel_id = element('channel_id', $data)) {
            return;
        }

        if (!$field_id = array_value($this->config->item('cartthrob:product_channel_fields'), $channel_id,
            'inventory')) {
            return;
        }

        $this->load->model('cartthrob_field_model');

        $field_type = $this->cartthrob_field_model->get_field_type($field_id);

        if ($this->isModifier($field_type)) {
            $field_name = $this->cartthrob_field_model->get_field_name($field_id);

            $price_modifiers = $this->get_price_modifiers($entry_id, $field_id);

            foreach ($price_modifiers as $index => $price_modifier) {
                if (isset($price_modifier['inventory']) && $price_modifier['inventory'] !== '' && isset($item_options[$field_name]) && $item_options[$field_name] == $price_modifier['option_value']) {
                    if ($reduce) {
                        $inventory = Number::sanitize($price_modifier['inventory']) - Number::sanitize($quantity);
                    } else {
                        $inventory = Number::sanitize($price_modifier['inventory']) + Number::sanitize($quantity);
                    }

                    if ($field_type == 'matrix') {
                        if (empty($data['field_id_' . $field_id])) {
                            return;
                        }

                        if (!$field_settings = $this->cartthrob_field_model->get_field_settings($field_id)) {
                            return;
                        }

                        $query = $this->db->select('col_id, col_name')
                            ->from('matrix_cols')
                            ->where_in('col_id', $field_settings['col_ids'])
                            ->where_in('col_name', ['inventory', 'option_value'])
                            ->get();

                        if ($query->num_rows() != 2) {
                            return;
                        }

                        foreach ($query->result_array() as $row) {
                            switch ($row['col_name']) {
                                case 'inventory':
                                    $inventory_col_id = $row['col_id'];
                                    break;
                                case 'option_value':
                                    $option_col_id = $row['col_id'];
                                    break;
                            }
                        }

                        // update ze cache
                        $this->session->cache['cartthrob']['product_model']['all_price_modifiers'][$entry_id][$field_name][$index]['inventory'] = $inventory;

                        $this->db->update(
                            'matrix_data',
                            [
                                'col_id_' . $inventory_col_id => $inventory,
                            ],
                            [
                                'field_id' => $field_id,
                                'col_id_' . $option_col_id => $item_options[$field_name],
                                'entry_id' => $entry_id,
                            ]
                        );
                    } elseif ($field_type == 'grid') {
                        if (!array_key_exists('field_id_' . $field_id, $data)) {
                            return;
                        }

                        $cols = $this->cartthrob_field_model->get_grid_cols($field_id);
                        $inventory_col_id = $option_col_id = null;
                        foreach ($cols as $row) {
                            switch ($row['col_name']) {
                                case 'inventory':
                                    $inventory_col_id = $row['col_id'];
                                    break;

                                case 'option_value':
                                    $option_col_id = $this->cartthrob_field_model->get_grid_row_id($item_options[$field_name], $row['col_order'], $field_id, $entry_id);
                                    break;
                            }
                        }

                        if (!is_null($inventory_col_id) && !is_null($option_col_id)) {
                            $this->session->cache['cartthrob']['product_model']['all_price_modifiers'][$entry_id][$field_name][$index]['inventory'] = $inventory;
                            $what = ['col_id_' . $inventory_col_id => $inventory];
                            $where = ['entry_id' => $entry_id, 'row_order' => $option_col_id];
                            $this->cartthrob_field_model->update_grid_field($field_id, $what, $where);
                        }
                    } else {
                        $price_modifiers[$index]['inventory'] = $inventory;

                        // update ze cache
                        $this->session->cache['cartthrob']['product_model']['all_price_modifiers'][$entry_id][$field_name][$index]['inventory'] = $inventory;

                        $field_data = $price_modifiers;

                        $inventory_data = [
                            'field_id_' . $field_id => $field_data,
                        ];

                        $entry = ee('Model')->get('ChannelEntry', $entry_id)
                            ->first();
                        $entry->set($inventory_data);
                        $entry->save();

                        $this->load->model('cartthrob_entries_model');

                        $this->cartthrob_entries_model->clear_cache($entry_id);
                    }
                }
            }
        } elseif (isset($data['field_id_' . $field_id]) && $data['field_id_' . $field_id] !== '') {
            if ($reduce) {
                $inventory = Number::sanitize($data['field_id_' . $field_id]) - Number::sanitize($quantity);
            } else {
                $inventory = Number::sanitize($data['field_id_' . $field_id]) + Number::sanitize($quantity);
            }
            $inventory_data = [
                'field_id_' . $field_id => $inventory,
            ];

            $entry = ee('Model')->get('ChannelEntry', $entry_id)
                ->first();
            $entry->set($inventory_data);
            $entry->save();

            $this->load->model('cartthrob_entries_model');

            // Clear cache to purge saved entry now that inventory has been modifified
            $this->cartthrob_entries_model->clear_cache($entry_id);

            // reload the entry so that the new inventory is available.
            $this->cartthrob_entries_model->loadEntriesByEntryId($entry_id);
        }

        return $inventory;
    }

    /**
     * @param $entry_id
     * @param int $quantity
     * @param array $item_options
     * @return bool|float|int|void
     */
    public function reduce_inventory($entry_id, $quantity = 1, $item_options = [])
    {
        $inventory = $this->adjust_inventory($entry_id, $quantity, $item_options);

        if ($this->extensions->active_hook('cartthrob_product_reduce_inventory')) {
            $this->extensions->call('cartthrob_product_reduce_inventory', $entry_id, $this->get_product($entry_id),
                $quantity, $item_options, $inventory);
        }

        return $inventory;
    }

    /**
     * @return array
     */
    public function get_categories()
    {
        if (!$this->config->item('cartthrob:product_channels')) {
            return [];
        }

        $channels = ee('Model')
            ->get('Channel')
            ->filter('channel_id', 'IN', $this->config->item('cartthrob:product_channels'));

        $cat_group = [];

        foreach ($channels->all() as $row) {
            if ($row->cat_group) {
                $cat_group = array_merge($cat_group, explode('|', $row->cat_group));
            }
        }

        if (!$cat_group) {
            return [];
        }

        return $this->db->select('cat_id AS category_id, cat_name AS category_name, cat_url_title AS category_url_title, cat_description AS category_description, cat_image AS category_image, cat_order AS category_order, group_id, parent_id')
            ->where('site_id', $this->config->item('site_id'))
            ->where_in('group_id', $cat_group)
            ->order_by('cat_order, cat_name')
            ->get('categories')
            ->result_array();
    }

    /**
     * @param $entry_ids
     * @return $this
     */
    public function load_products($entry_ids)
    {
        $this->cartthrob_entries_model->loadEntriesByEntryId($entry_ids);

        foreach ($entry_ids as $i => $entry_id) {
            if (!isset($this->category_posts[$entry_id])) {
                $this->category_posts[$entry_id] = [];
            } else {
                unset($entry_ids[$i]);
            }
        }

        if (count($entry_ids) > 0) {
            $query = $this->db->select('cat_id, entry_id')
                ->where_in('entry_id', $entry_ids)
                ->get('category_posts');

            foreach ($query->result() as $row) {
                $this->category_posts[$row->entry_id][] = $row->cat_id;
            }
        }

        return $this;
    }

    /**
     * @param string $fieldType
     * @return bool
     */
    private function isModifier(string $fieldType = ''): bool
    {
        return in_array($fieldType, ['cartthrob_price_modifiers', 'matrix', 'grid']) || strncmp($fieldType,
            'cartthrob_price_modifiers', 25) === 0;
    }
}
