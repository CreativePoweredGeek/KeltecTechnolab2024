<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Tax_model extends CI_Model
{
    public $cartthrob;
    public $store;
    public $cart;
    private $table = 'cartthrob_tax';

    /**
     * Tax_model constructor.
     */
    public function __construct()
    {
        $this->load->model('cartthrob_field_model');
        $this->load->model('cartthrob_entries_model');

        $this->cartthrob_loader->setup($this);
    }

    /**
     * @param null $id
     * @return bool
     */
    public function delete($id = null)
    {
        if ($id) {
            $this->db->delete($this->table, ['id' => $id]);
        }

        return true;
    }

    /**
     * @param array $data
     * @param $id
     * @return bool
     */
    public function update($data = [], $id = null)
    {
        return $this->create($data, $id);
    }

    /**
     * @param array $sent_data
     * @param null $id
     * @return bool
     */
    public function create($sent_data = [], $id = null)
    {
        $fields = $this->db->list_fields($this->table);
        foreach ($fields as $field) {
            $db_keys[$field] = true;
        }
        $data = array_intersect_key($sent_data, $db_keys);
        if (isset($data['percentage'])) {
            // adding zero = lazy number casting
            $data['percent'] += 0;

            if ($data['percent'] > 100) {
                $data['percent'] = 100;
            }
            if ($data['percent'] < 0) {
                $data['percent'] = 0;
            }
        }

        if ($id) {
            $this->db->where('id', $id)->update($this->table, $data);
        } else {
            $this->db->insert($this->table, $data);
        }

        return true;
    }

    /**
     * @param array $location_data
     * @param string $limit
     * @param null $order_by
     * @return mixed
     */
    public function get_by_location($location_data = [], $limit = '100', $order_by = null)
    {
        $db_keys = [];
        $fields = $this->db->list_fields($this->table);
        foreach ($fields as $field) {
            $db_keys[$field] = true;
        }
        $search_fields = array_intersect_key($location_data, $db_keys);

        foreach ($search_fields as $key => $data) {
            $this->db->where($key, $data);
        }

        if ($order_by) {
            $this->db->order_by($order_by);
        }

        $this->db->limit($limit);
        $this->db->select('*');
        $query = $this->db->get($this->table);

        return $query->result_array();
    }

    /**
     * @param null $id
     * @param int $limit
     * @param int $offset
     * @param string $order_by
     * @return array
     */
    public function get($id = null, int $limit = 100, int $offset = 0, string $order_by = 'country')
    {
        $data = [];
        $tax = [];

        if (is_int($id)) {
            $query = $this->db
                ->select('*')
                ->limit(1)
                ->where('id', $id)
                ->get($this->table);
        } else {
            $query = $this->db
                ->select('*')
                ->limit($limit)
                ->offset($offset)
                ->order_by($order_by)
                ->get($this->table);
        }

        if ($id === false) {
            $id = null;
        }

        $fields = $this->db->list_fields($this->table);
        foreach ($query->result() as $row) {
            foreach ($fields as $field) {
                if ($field == 'percent') {
                    $tax[$field] = (float)$row->$field;
                } else {
                    $tax[$field] = $row->$field;
                }
            }

            $data[] = $tax;
        }

        return $data;
    }
}
