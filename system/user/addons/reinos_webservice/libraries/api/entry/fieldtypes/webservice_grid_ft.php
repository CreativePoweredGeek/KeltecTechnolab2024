<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author		Rein de Vries <support@reinos.nl>
 * @link		https://addons.reinos.nl
 * @copyright 	Copyright (c) 2011 - 2021 Reinos.nl Internet Media
 * @license     https://addons.reinos.nl/commercial-license
 *
 * Copyright (c) 2011 - 2021 Reinos.nl Internet Media
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Rein de Vries and
 * Reinos.nl Internet Media) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

/**
 * Include the config file
 */
require_once PATH_THIRD.'reinos_webservice/config.php';

class Webservice_grid_ft
{
    public $name = 'grid';

    private $tmp_entry_id = 42949672;

    // ----------------------------------------------------------------

    /**
     * Preps the data for saving
     *
     * @param  mixed $data
     * @param  bool $is_new
     * @param  int $entry_id
     * @return void
     */
    public function webservice_save($data = null, $is_new = false, $entry_id = 0)
    {
        if(!empty($data) && is_array($data))
        {
            //set the rows
            $rows = isset($data['rows']) ? $data['rows'] : array();

            //max rows?
            if($this->field_settings['grid_max_rows'] != '' && (count($rows) > $this->field_settings['grid_max_rows']) )
            {
                $rows = array_slice($rows, 0, $this->field_settings['grid_max_rows']);
            }

            //if not new, an update (doh ;-), delete the old ones
            if($is_new == false)
            {
                if(ee()->db->table_exists('channel_grid_field_'.$this->field_data['field_id']))
                {
                    ee()->db->where('entry_id', $entry_id);
                    ee()->db->delete('channel_grid_field_'.$this->field_data['field_id']);
                }
            }

            //get te grid settings
            $grid_columns = $this->get_grid_settings($this->field_data['field_id']);

            //set the insert array
            $insert_array = array();

            //loop over the items
            foreach($rows as $order => $row)
            {
                //loop over the fields
                foreach($row as $key => $val)
                {
                    //get the fieldtype settings
                    $col_settings = isset($grid_columns[$key]) ? $grid_columns[$key] : null;

                    //get the data
                    $v = ee()->webservice_fieldtype->save_grid($val, $col_settings['col_type'], $col_settings['col_name'], $col_settings, $entry_id);

                    //grid insert array, set it correct
                    $insert_array['new_row_'.$order]['col_id_'.$this->get_grid_col_id($this->field_data['field_id'], $key)] = $v;
                }
            }

            return $insert_array;
        }

        //return nothing when there is nothing
        return '';

        /*
            Array
            (
                [new_row_2] =&gt; Array
                    (
                        [col_id_3] =&gt; asdf
                        [col_id_4] =&gt; sdfsf
                    )

            )

        */
    }

    // ----------------------------------------------------------------

    /**
     * Validate the field
     *
     * @param  mixed $data
     * @param  bool $is_new
     * @return void
     */
    public function webservice_validate($data = null, $is_new = false)
    {
        //validate the min rows
        if($this->field_settings['grid_min_rows'] != '' && (count($data) < $this->field_settings['grid_min_rows']) )
        {
            $this->validate_error = 'You must add a min of '.$this->field_settings['grid_min_rows'].' rows';
            return false;
        }

        //validate the min rows
        if($this->field_settings['grid_max_rows'] != '' && (count($data) > $this->field_settings['grid_max_rows']) )
        {
            $this->validate_error = 'You reach the limit of '.$this->field_settings['grid_max_rows'].' rows';
            return false;
        }

        return true;
    }

    // ----------------------------------------------------------------------

    /**
     * Preprocess the data to be returned
     *
     * @param  mixed $data
     * @param  string $free_access
     * @param  int $entry_id
     * @return mixed string
     */
    public function webservice_pre_process($data = null, $free_access = false, $entry_id = 0)
    {
        //get the data
        if(ee()->db->table_exists('channel_grid_field_'.$this->field_data['field_id']))
        {
            //get the data
            ee()->db->where('entry_id', $entry_id);
            ee()->db->order_by('row_order', 'asc');

            //publisher installed, get the correct data
            if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->is_installed('publisher') && isset(ee()->webservice_fieldtype->post_data['publisher_lang_id']))
            {
                ee()->db->where('publisher_lang_id', ee()->webservice_fieldtype->post_data['publisher_lang_id']);
                ee()->db->where('publisher_status', isset(ee()->webservice_fieldtype->post_data['publisher_status']) ? ee()->webservice_fieldtype->post_data['publisher_status'] : 'open');
            }

            $query = ee()->db->get('channel_grid_field_'.$this->field_data['field_id']);

            $return = array();

            //format the data
            if($query->num_rows() > 0)
            {
                foreach($query->result_array() as $key=>$val)
                {
                    foreach($val as $k=>$v)
                    {
                        //attach order_id
                        if($k == 'row_order')
                        {
                            $return[$key][$k] = $v+1;
                        }

                        //set the name
                        else if(preg_match('/col_id_/', $k))
                        {
                            //get settings
                            $col_settings = $this->get_grid_col_field_settings($this->field_data['field_id'], str_replace('col_id_', '', $k));

                            //$k = $grid_labels[$k];
                            $k = $this->get_grid_col_field_name($this->field_data['field_id'], str_replace('col_id_', '', $k));

                            //if $k is empty, skip this one
                            if($k == '')
                            {
                                continue;
                            }

                            /** ---------------------------------------
                            /**  Process the data
                            /** ---------------------------------------*/
                            if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('parse_matrix_grid_data'))
                            {
                                $v = ee()->webservice_fieldtype->pre_process_grid($v, $col_settings['col_type'], $col_settings['col_name'], $col_settings, 'search_entry', $entry_id);
                            }

                            /* -------------------------------------------
                            /* 'read_entry_grid' hook.
                            /*  - Added: 3.5.1
                            */
                            $return[$key][$k] = ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->add_hook('read_entry_grid', $v, false, $col_settings, $val['row_id']);
                            /** ---------------------------------------*/
                        }
                    }
                }

                return $return;
            }
        }

        // return $data;
        return $data;
    }

    // ----------------------------------------------------------------------

    /**
     * Get the grid settings
     *
     * @return void
     */
    public function get_grid_settings($field_id = 0)
    {
        ee()->db->where('field_id', $field_id);
        $query = ee()->db->get('grid_columns');

        $grid_settings = array();

        if($query->num_rows() > 0)
        {
            foreach($query->result() as $k => $row)
            {
                foreach($row as $key => $val)
                {
                    $grid_settings[$row->col_name][$key] = $val;
                    $grid_settings[$row->col_id][$key] = $val;
                }
            }
        }

        return $grid_settings;
    }

    // ----------------------------------------------------------------------

    /**
     * Get the grid settings
     *
     * @return void
     */
    public function get_grid_col_id($field_id = 0, $col_name = '')
    {
        $cache_key = 'get_grid_col_id_'.$field_id.'_'.$col_name;
        if(ee()->session->cache('webservice_grid_ft', $cache_key))
        {
            return ee()->session->cache('webservice_grid_ft', $cache_key);
        }

        ee()->db->where('field_id', $field_id);
        ee()->db->where('col_name', $col_name);
        $query = ee()->db->get('grid_columns');

        if($query->num_rows() > 0)
        {
            $result = $query->row()->col_id;

            ee()->session->set_cache('webservice_grid_ft', $cache_key, $result);

            return $result;
        }

        return '';
    }

    // ----------------------------------------------------------------------

    /**
     * Get the Grid field_name
     *
     * @return void
     */
    public function get_grid_col_field_name($field_id = 0, $col_id = 0)
    {
        $cache_key = 'get_grid_col_field_name'.$field_id.'_'.$col_id;
        if(ee()->session->cache('webservice_grid_ft', $cache_key))
        {
            return ee()->session->cache('webservice_grid_ft', $cache_key);
        }

        ee()->db->where('field_id', $field_id);
        ee()->db->where('col_id', $col_id);
        $query = ee()->db->get('grid_columns');

        if($query->num_rows() > 0)
        {
            $result = $query->row()->col_name;

            ee()->session->set_cache('webservice_grid_ft', $cache_key, $result);

            return $result;
        }

        return '';
    }

    // ----------------------------------------------------------------------

    /**
     * Get the Grid field_type
     *
     * @return void
     */
    public function get_grid_col_field_settings($field_id = 0, $col_id = 0)
    {
        ee()->db->where('field_id', $field_id);
        ee()->db->where('col_id', $col_id);
        $query = ee()->db->get('grid_columns');

        if($query->num_rows() > 0)
        {
            return $query->row_array();
        }

        return array();
    }

}
