<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Generic_model
 *
 * This model is a generic public CRUD model
 *    Use it like this:
 *    $this->load->model('generic_model');
 *    $my_model= new Generic_model("my_table_name");
 *    all data is passed in through arrays
 *
 * @uses crud_model
 * @uses crud_interface
 **/
class Generic_model extends Crud_model implements Crud_interface
{
    /**
     * $this->table_name
     * this is the name of the database table to act on
     * @var string
     **/
    protected $table_name = '';

    /**
     * constructor
     *
     * @param string $table_name Name of the database table
     **/
    public function __construct($table_name = null)
    {
        //	parent::Crud_model();
        $this->table_name = $table_name;
    }

    /**
     * @param $array
     * @return int
     */
    public function create($array)
    {
        return $this->_create($array);
    }

    /**
     * @param null $id
     * @param null $order_by
     * @param string $order_direction
     * @param null $field_name
     * @param null $string
     * @param null $limit
     * @param null $offset
     * @return object
     */
    public function read(
        $id = null,
        $order_by = null,
        $order_direction = 'asc',
        $field_name = null,
        $string = null,
        $limit = null,
        $offset = null
    ) {
        return $this->_read($id, $order_by, $order_direction, $field_name, $string, $limit, $offset);
    }

    /**
     * @param $id
     * @param $array
     * @return int
     */
    public function update($id, $array)
    {
        return $this->_update($id, $array);
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        return $this->_delete($id);
    }

    /**
     * @param $fields_array
     * @param $search_terms_array
     * @param null $limit
     * @param null $offset
     * @param string $like_or
     * @return object
     */
    public function search($fields_array, $search_terms_array, $limit = null, $offset = null, $like_or = 'like')
    {
        return $this->_search($fields_array, $search_terms_array, $like_or, $limit, $offset);
    }

    /**
     * @return string
     */
    public function get_table_name()
    {
        return $this->_get_table_name();
    }
}
