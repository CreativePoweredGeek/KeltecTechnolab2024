<?php
/**
 * Crud_interface
 *
 * requires standard C.R.U.D functions be available in consistent format
 * create
 * read
 * update
 * delete
 *
 **/
interface Crud_interface
{
    public function create($array);

    public function read($id = null, $order_by = null, $order_direction = 'asc', $field_name = null, $string = null, $limit = null, $offset = null);

    public function update($id, $array);

    public function delete($id);
}
// END interface
