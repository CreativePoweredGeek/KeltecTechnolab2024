<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Packages_field_model extends CI_Model
{
    protected $fields = [];
    protected $channels;
    protected $matrix_cols;
    protected $matrix_rows;

    public function __construct()
    {
        parent::__construct();

        $this->load->helper('array');

        $this->loadFields($this->config->item('site_id'));
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

        // cannot use array merge because it will reindex, and we don't want that
        $this->fields = array_diff_key($this->fields, $fields) + $fields;
    }

    /**
     * @param $channel_id
     * @param $fieldtype
     * @param bool $return_field_id
     * @return bool
     */
    public function channel_has_fieldtype($channel_id, $fieldtype, $return_field_id = false)
    {
        return $this->group_has_fieldtype($this->get_field_group($channel_id), $fieldtype, $return_field_id);
    }

    /**
     * @param $group_id
     * @param $fieldtype
     * @param bool $return_field_id
     * @return bool
     */
    public function group_has_fieldtype($group_id, $fieldtype, $return_field_id = false)
    {
        $fields = $this->get_fields_by_group($group_id);
        $this->load->add_package_path(PATH_THIRD . 'packages');

        $this->load->library('data_filter');

        $this->data_filter->filter($fields, 'field_type', $fieldtype);

        if ($return_field_id === true) {
            $field = current($fields);

            return ($field) ? $field['field_id'] : false;
        }

        return count($fields) > 0;
    }

    /**
     * @param $group_id
     * @return array
     */
    public function get_fields_by_group($group_id)
    {
        return $this->get_fields();
    }

    /**
     * @param array $params
     * @param bool $limit
     * @return array
     */
    public function get_fields($params = [], $limit = false)
    {
        $this->load->add_package_path(PATH_THIRD . 'packages');

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

    /**
     * @param $channel_id
     * @return bool
     */
    public function get_field_group($channel_id)
    {
        if (is_null($this->channels)) {
            $query = $this->db->select('field_group, channel_id')
                ->from('channels')
                ->get();

            foreach ($query->result() as $row) {
                $this->channels[$row->channel_id] = $row->field_group;
            }

            $query->free_result();
        }

        return element($channel_id, $this->channels);
    }

    /**
     * @param $field_id
     * @return array
     */
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
     * @param $field_id
     * @return bool
     */
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

    /**
     * @param $entry_id
     * @param $field_id
     * @return array
     */
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

    /**
     * @param $field_name
     * @return bool
     */
    public function get_field_id($field_name)
    {
        return element('field_id', $this->get_field_by_name($field_name));
    }

    /**
     * @param $field_name
     * @return mixed
     */
    public function get_field_by_name($field_name)
    {
        return current($this->get_fields(['field_name' => $field_name], 1));
    }

    /**
     * @param $field_id
     * @return bool
     */
    public function get_field_name($field_id)
    {
        return element('field_name', $this->get_field_by_id($field_id));
    }

    /**
     * @param $field_id
     * @return bool
     */
    public function get_field_by_id($field_id)
    {
        return element($field_id, $this->fields);
    }

    /**
     * @param $field_id
     * @return bool
     */
    public function get_field_label($field_id)
    {
        return element('field_label', $this->get_field_by_id($field_id));
    }

    /**
     * @param $field_id
     * @return bool
     */
    public function get_field_fmt($field_id)
    {
        return element('field_fmt', $this->get_field_by_id($field_id));
    }

    /**
     * @param $channel_id
     * @return array
     */
    public function get_fields_by_channel($channel_id)
    {
        $query = $this->db->select('channel_fields.field_id, channel_fields.field_name, channel_fields.field_type, channel_fields.field_label, channel_fields.field_settings, channel_fields.field_fmt')
            ->from('channel_fields')
            ->join('channels_channel_fields', 'channels_channel_fields.field_id = channel_fields.field_id')
            ->where('channels_channel_fields.channel_id', $channel_id)
            ->get();

        $fieldsarr = [];

        if ($query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $fieldsarr[] = [
                    'field_id' => $row->field_id,
                    'field_name' => $row->field_name,
                    'field_type' => $row->field_type,
                    'field_label' => $row->field_label,
                ];
            }
        }

        // check for more fields
        $query = $this->db->select('channel_fields.field_id, channel_fields.field_name, channel_fields.field_type, channel_fields.field_label, channel_fields.field_settings, channel_fields.field_fmt')
            ->from('channel_fields')
            ->join('channel_field_groups_fields', 'channel_field_groups_fields.field_id = channel_fields.field_id')
            ->join('channels_channel_field_groups',
                'channels_channel_field_groups.group_id = channel_field_groups_fields.group_id')
            ->where('channels_channel_field_groups.channel_id', $channel_id)
            ->get();
        // channels_channel_field_groups

        foreach ($query->result() as $row) {
            $fieldsarr[] = [
                'field_id' => $row->field_id,
                'field_name' => $row->field_name,
                'field_type' => $row->field_type,
                'field_label' => $row->field_label,
                'field_settings' => $row->field_settings,
                'field_fmt' => $row->field_fmt,
            ];
        }
        // $channelFields[] = array_unique($fieldsarr,false);

        return $fieldsarr;
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
        $this->load->add_package_path(PATH_THIRD . 'packages');

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
