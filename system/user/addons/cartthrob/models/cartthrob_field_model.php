<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cartthrob_field_model extends CI_Model
{
    protected $fields = [];
    protected $channels;
    protected $matrix_cols;
    protected $matrix_rows;
    protected $grid_cols;
    protected $grid_rows;

    public function __construct()
    {
        parent::__construct();

        $this->load->helper('array');

        $site_id = $this->config->item('site_id');

        if ($this->config->item('cartthrob:msm_show_all')) {
            $site_id = 'all';
        }
        $this->loadFields($site_id);
    }

    /**
     * By default the model only loads channels/fields from the current site
     * Use this to fetch the channel/field data from another site
     *
     * @param mixed $site_id either the numeric site_id or the string "all"
     */
    public function loadFields($site_id)
    {
        $fields = [];

        $query_field = ee('Model')
            ->get('ChannelField')
            ->fields('field_id', 'field_name', 'field_label', 'field_type', 'field_settings', 'field_fmt')
            ->order('field_order', 'asc')
            ->all();

        $query = $query_field->getValues();

        if (empty($query)) {
            return;
        }

        foreach ($query as $row) {
            $fields[$row['field_id']] = $row;
        }

        unset($query);
        // $query->free_result();

        // cannot use array merge because it will reindex, and we don't want that
        $this->fields = array_diff_key($this->fields, $fields) + $fields;
    }

    public function channel_has_fieldtype($channel_id, $fieldtype, $return_field_id = false)
    {
        return $this->group_has_fieldtype($this->get_field_group($channel_id), $fieldtype, $return_field_id);
    }

    public function group_has_fieldtype($group_id, $fieldtype, $return_field_id = false)
    {
        $fields = $this->get_fields_by_group($group_id);
        $this->load->add_package_path(PATH_THIRD . 'cartthrob');

        $this->load->library('data_filter');

        $this->data_filter->filter($fields, 'field_type', $fieldtype);

        if ($return_field_id === true) {
            $field = current($fields);

            return ($field) ? $field['field_id'] : false;
        }

        return count($fields) > 0;
    }

    public function get_fields_by_group($group_id)
    {
        $fields = $this->get_fields();
        $field_ids = $return = [];
        $query = ee()->db->where('group_id', $group_id)->get('channel_field_groups_fields');
        if ($query) {
            foreach ($query->result_array() as $result) {
                $field_ids[] = $result['field_id'];
            }
        }

        foreach ($fields as $field) {
            $field_id = $field['field_id'] ?? null;
            if (in_array($field_id, $field_ids)) {
                $return[] = $field;
            }
        }

        return $return;
    }

    public function get_fields($params = [], $limit = false)
    {
        $this->load->add_package_path(PATH_THIRD . 'cartthrob');

        $this->load->library('data_filter');

        $fields = $this->fields ? $this->fields : [];

        foreach ($params as $key => $value) {
            $this->data_filter->filter($fields, $key, $value);
        }

        if ($limit !== false) {
            $this->data_filter->limit($fields, $limit);
        }

        return $fields;
    }

    public function get_field_group($channel_id)
    {
        if (is_null($this->channels)) {
            $query = $this->db->select('group_id, channel_id')
                ->from('channels_channel_field_groups')
                ->get();

            foreach ($query->result() as $row) {
                $this->channels[$row->channel_id] = $row->group_id;
            }

            $query->free_result();
        }

        return element($channel_id, $this->channels);
    }

    public function get_matrix_cols($field_id)
    {
        if (!$field_id) {
            return [];
        }

        $settings = $this->get_field_settings($field_id);

        if (!isset($this->matrix_cols[$field_id])) {
            $this->matrix_cols[$field_id] = (empty($settings['col_ids']))
                ? []
                : $this->db->where_in('col_id', $settings['col_ids'])
                    ->get('matrix_cols')
                    ->result_array();
        }

        return $this->matrix_cols[$field_id];
    }

    /**
     * @param int $field_id
     * @return array
     */
    public function get_grid_cols(int $field_id): array
    {
        if (!$field_id) {
            return [];
        }

        if (!isset($this->grid_cols[$field_id])) {
            $this->grid_cols[$field_id] = $this->db->where_in('field_id', $field_id)
                ->get('grid_columns')
                ->result_array();
        }

        return $this->grid_cols[$field_id];
    }

    /**
     * @param mixed $value
     * @param int $col_order
     * @param int $field_id
     * @param int $entry_id
     * @return int|null
     */
    public function get_grid_row_id(mixed $value, int $col_order, int $field_id, int $entry_id): ?int
    {
        $return = null;
        $table = 'channel_grid_field_' . $field_id;
        if ($this->db->table_exists($table)) {
            $data = $this->db->where('col_id_' . $col_order, $value)
                ->where('entry_id', $entry_id)
                ->get($table);

            if ($data) {
                $return = $data->row('row_order');
            }
        }

        return $return;
    }

    public function update_grid_field(int $field_id, array $what, array $where)
    {
        $table = 'channel_grid_field_' . $field_id;
        if ($this->db->table_exists($table)) {
            $this->db->update($table, $what, $where);
        }
    }

    public function get_field_settings($field_id)
    {
        if (!isset($this->fields[$field_id])) {
            return false;
        }

        if ($this->fields[$field_id]['field_settings'] !== false || !is_array($this->fields[$field_id]['field_settings'])) {
            $this->fields[$field_id]['field_settings'] = _unserialize($this->fields[$field_id]['field_settings'], true);
        }

        return $this->fields[$field_id]['field_settings'];
    }

    public function get_matrix_rows($entry_id, $field_id)
    {
        if (!$entry_id || !$field_id) {
            return [];
        }

        if (!isset($this->matrix_rows[$entry_id][$field_id])) {
            $this->matrix_rows[$entry_id][$field_id] = $this->db->where('entry_id', $entry_id)
                ->where('field_id', $field_id)
                ->order_by('row_order')
                ->get('matrix_data')
                ->result_array();
        }

        return $this->matrix_rows[$entry_id][$field_id];
    }

    public function get_grid_rows(int $entry_id, int $field_id): array
    {
        if (!$entry_id || !$field_id) {
            return [];
        }

        if (!isset($this->grid_rows[$entry_id][$field_id])) {
            $table = 'channel_grid_field_' . $field_id;
            if ($this->db->table_exists($table)) {
                $this->matrix_rows[$entry_id][$field_id] = $this->db->where('entry_id', $entry_id)
                    ->order_by('row_order')
                    ->get($table)
                    ->result_array();
            }
        }

        return $this->matrix_rows[$entry_id][$field_id] ?? [];
    }

    public function get_fields_by_type($field_type)
    {
        return $this->get_fields(['field_type' => $field_type]);
    }

    public function get_field_id($field_name)
    {
        return element('field_id', $this->get_field_by_name($field_name));
    }

    public function get_field_by_name($field_name)
    {
        return current($this->get_fields(['field_name' => $field_name], 1));
    }

    public function get_field_name($field_id)
    {
        return element('field_name', $this->get_field_by_id($field_id));
    }

    public function get_field_by_id($field_id)
    {
        return element($field_id, $this->fields);
    }

    public function get_field_label($field_id)
    {
        return element('field_label', $this->get_field_by_id($field_id));
    }

    public function get_field_fmt($field_id)
    {
        return element('field_fmt', $this->get_field_by_id($field_id));
    }

    /**
     * @param $channel_id
     * @return mixed
     */
    public function get_fields_by_channel($channel_id)
    {
        $cache_key = is_array($channel_id) ? implode('|', $channel_id) : $channel_id;
        $cache_key = 'fields_by_channel_' . md5($cache_key);

        if (!ee()->session->cache(__CLASS__, $cache_key)) {
            $channels = ee('Model')->get('Channel', $channel_id)->first();

            if (!$channels) {
                $data = [];
            } else {
                foreach ($channels->getAllCustomFields() as $field) {
                    $fieldsarr[] = [
                        'field_id' => $field->field_id,
                        'field_name' => $field->field_name,
                        'field_type' => $field->field_type,
                        'field_label' => $field->field_label,
                        'field_settings' => $field->field_settings,
                        'field_fmt' => $field->field_fmt,
                    ];
                }

                $data = $fieldsarr;
            }

            ee()->session->set_cache(__CLASS__, $cache_key, $data);
        }

        return ee()->session->cache(__CLASS__, $cache_key);
    }

    /**
     * @param $field_id
     * @return bool
     */
    public function get_field_type($field_id)
    {
        return element('field_type', $this->get_field_by_id($field_id));
    }

    /**
     * @param bool $where
     * @param bool $value
     * @param bool $key
     * @return bool|mixed
     */
    public function get_category_fields($where = false, $value = false, $key = false)
    {
        static $cache;
        $this->load->add_package_path(PATH_THIRD . 'cartthrob');

        $this->load->library('data_filter');

        if (is_null($cache)) {
            $query = $this->db->get('category_fields');

            $cache = $query->result_array();

            $query->free_result();
        }

        $category_fields = $cache;

        switch (func_num_args()) {
            case 0:
                return $category_fields;
            case 1:
                $this->data_filter->filter($category_fields, 'field_id', $where);

                return current($category_fields);
            case 2:
                $this->data_filter->filter($category_fields, $where, $value);

                return $category_fields;
            case 3:
            default:
                $this->data_filter->filter($category_fields, $where, $value);

                return element($key, current($category_fields));
        }
    }
}
