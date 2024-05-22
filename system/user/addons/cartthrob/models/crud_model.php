<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
if (class_exists('Crud_model')) {
    return;
}

/**
 * crud_model
 *
 * This model implements standard crud features
 * all data passed in is through arrays with named keys corresponding to fields in the database
 **/
class Crud_model extends CI_Model
{
    /**
     * $this->table_name
     * this is the name of the database table to act on
     * this should be set in models that extend this
     * @var string
     **/
    protected $table_name = '';

    /**
     * $this->id_field
     * this is the name of the primary key used to access records
     * this should be set in models that extend this
     * @var string
     **/
    protected $id_field = 'id';

    /**
     * @param array $params
     */
    public function __construct($params = [])
    {
        if (!empty($params['table'])) {
            $this->table_name = $params['table'];
        }
        if (!empty($params['table'])) {
            $this->id_field = $params['id_field'];
        }
    }

    /**
     * _create
     *
     * @param array $array
     * @return int $insert_id
     **/
    final public function _create($array)
    {
        $insert_id = $this->_update($id = null, $array);

        return $insert_id;
    }

    /**
     * _update
     *
     * This function will create / update database records
     * if an array key is not found in the database map it is not passed to the db.
     *
     * @param int|null $id
     * @param array $array of values
     * @return int insert_id
     */
    final public function _update(?int $id = null, array $array = []): int
    {
        $fields = $this->_db_map();
        $update_data = [];
        foreach ($array as $key => $value) {
            if (array_key_exists($key, $fields)) {
                if (is_array($value)) {
                    $value = serialize($value); // might want to change to json encode?
                }
                $update_data[$key] = $value;
            }
        }
        if ($id) {
            $update_data[$this->id_field] = $id;
            $this->db->where($this->id_field, $id);
            $this->db->update($this->table_name, $update_data);

            return $id;
        } else {
            if (isset($update_data[$this->id_field])) {
                unset($update_data[$this->id_field]);
            }
            $this->db->insert($this->table_name, $update_data);

            return $this->db->insert_id();
        }
    }

    /**
     * _db_map
     *
     * returns an array of fields in the DB.
     * @return array
     **/
    final public function _db_map()
    {
        $fields = $this->db->list_fields($this->table_name);
        $data = [];
        // Initialize map array
        foreach ($fields as $field) {
            $data[$field] = null;
        }

        return $data;
    }

    /**
     * _read
     *
     * @param int $id
     * @param string $order_by
     * @param string $order_direction asc, or desc, etc
     * @param string $field_name // $string also required; Produces: WHERE $field_name = 'whatever'
     * @param string $string // $field_name also required: Produces: WHERE whatever = '$string'
     * @param int $limit
     * @param int $offset
     * @return object
     **/
    final public function _read(
        $id = null,
        $order_by = null,
        $order_direction = 'asc',
        $field_name = null,
        $string = null,
        $limit = null,
        $offset = null,
        $group_by = null
    ) {
        $fields = $this->_db_map();

        if ($group_by) {
            $this->db->group_by($group_by);
        }
        if ($order_by) {
            $this->db->order_by($order_by, $order_direction);
        } else {
            $this->db->order_by($this->id_field, $order_direction);
        }
        if ($id) {
            $this->db->where($this->id_field, $id);
            $query = $this->db->get($this->table_name, 1);
            if ($query->num_rows() > 0 && $query->result()) {
                return $query->row_array();
            } else {
                return null;
            }
        } elseif ($string && $field_name) {
            if (array_key_exists($field_name, $fields)) {
                $this->db->where($field_name, $string);
            }
            $query = $this->db->get($this->table_name, $limit, $offset);
            if ($query->num_rows() > 0 && $query->result()) {
                return $query->result_array();
            } else {
                return null;
            }
        } else {
            $query = $this->db->get($this->table_name, $limit, $offset);
            if ($query->num_rows() > 0 && $query->result()) {
                return $query->result_array();
            } else {
                return null;
            }
        }
    }

    /**
     * _delete
     *
     * @param int $id
     * @param string $field_name for where clauses
     * @param string $string for where clauses
     **/
    final public function _delete($id = null, $field_name = null, $string = null)
    {
        if ($id) {
            $this->db->where($this->id_field, $id);
            $this->db->delete($this->table_name);
        } elseif ($string) {
            $this->db->where($field_name, $string);
            $this->db->delete($this->table_name);
        }
    }

    /**
     * _search function
     *
     * @param array $fields_array (only the first key has to be filled)
     * @param array $search_terms_array
     * @param string $like_or (like or or_like)
     * @param int $limit
     * @param int $offset
     * @return object
     **/
    final public function _search($fields_array, $search_terms_array, $like_or = 'like', $limit = null, $offset = null)
    {
        // FIRST TERM SHOULD BE SET TO LIKE;
        $like = 'like';
        foreach ($search_terms_array as $key => $item) {
            // IF USER IS LAZY AND DOESN'T PASS IN A FIELD, WE USE THE FIRST PASSED IN.
            if (!array_key_exists($key, $fields_array)) {
                $field = $fields_array[0];
            } else {
                $field = $fields_array[$key];
            }

            $this->db->$like($field, $item);
            // ADDITIONAL ITEMS SEARCH AS USER REQUESTS (LIKE, LIKE_OR, etc)
            $like = $like_or;
        }
        $query = $this->db->get($this->table_name, $limit, $offset);
        if ($query->num_rows() > 0 && $query->result()) {
            return $query->result_array();
        } else {
            return null;
        }
    }

    /**
     * _get_table_name
     *
     * returns the name of the table
     *
     * @return string
     **/
    final public function _get_table_name()
    {
        return $this->table_name;
    }

    /**
     * _set_table_name
     *
     * to dynamically change the tablename
     *
     * @param string $name
     **/
    final public function _set_table_name($name)
    {
        $this->table_name = $name;
    }
}
