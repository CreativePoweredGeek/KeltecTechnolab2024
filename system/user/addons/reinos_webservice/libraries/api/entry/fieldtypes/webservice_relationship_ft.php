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

class Webservice_relationship_ft
{
    public $name = 'relationship';

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
            //only one item allowed
            if(!$this->field_settings['allow_multiple'])
            {
                $data = array_slice($data, 0, 1);
            }

            //if not new, an update (doh ;-), delete the old ones
            if($is_new == false)
            {
                ee()->db->where('parent_id', $entry_id);
                ee()->db->delete('relationships');
            }

            //set the insert array
            $insert_array = array();

            //loop over the items
            foreach($data as $order => $row)
            {
                $insert_array['data'][] = $row;
                $insert_array['sort'][] = $order;
            }

            //return the data
            return $insert_array;
        }

        //return empty, because the values are in the relationship table
        return '';

        /*
            Array
(
    [sort] => Array
        (
            [0] => 3
            [1] => 2
            [2] => 1
            [3] => 4
        )

    [data] => Array
        (
            [0] => 42
            [1] => 33
            [2] => 36
            [3] => 38
        )

)
        */
    }

    // ----------------------------------------------------------------------

    /**
     * Preps the data for saving
     *
     * @param  mixed $data
     * @param  int $entry_id
     * @return void
     */
    public function webservice_save_grid($data = null, $entry_id = 0)
    {
        if(!empty($data) && is_array($data))
        {

            //only one item allowed
            if(!$this->col_settings['allow_multiple'])
            {
                $data = array_slice($data, 0, 1);
            }

            //delete the old ones
            ee()->db->where('grid_col_id', $this->col_id);
            ee()->db->where('parent_id', $entry_id);
            ee()->db->delete('relationships');

            //set the insert array
            $insert_array = array();

            //loop over the items
            foreach($data as $order => $row)
            {
                $insert_array['data'][] = $row;
                $insert_array['sort'][] = $order;
            }

            //return the data
            return $insert_array;
        }

        //return empty, because the values are in the relationship table
        return '';

    }

    // ----------------------------------------------------------------------

    /**
     * Preprocess the data to be returned
     *
     * @param  mixed $data
     * @param bool|string $free_access
     * @param  int $entry_id
     * @return mixed string
     */
    public function webservice_pre_process($data = null, $free_access = false, $entry_id = 0)
    {
        return $this->_parse_data($data, $free_access, $entry_id);
    }

    // ----------------------------------------------------------------------

    /**
     * Preprocess the data to be returned
     *
     * @param  mixed $data
     * @param bool|string $free_access
     * @param  int $entry_id
     * @return mixed string
     */
    public function webservice_pre_process_grid($data = null, $free_access = false, $entry_id = 0)
    {
        return $this->_parse_data($data, $free_access, $entry_id);
    }

    // ----------------------------------------------------------------------

    /**
     * get the channel id for an entry_id
     *
     */
    public function channel_id($entry_id = 0)
    {
        ee()->db->select('channel_id');
        ee()->db->where('entry_id', $entry_id);
        $query = ee()->db->get('channel_titles');

        if($query->num_rows() > 0)
        {
            return $query->row()->channel_id;
        }

        return false;
    }

    // ----------------------------------------------------------------------

    /**
     * Preprocess the data to be returned
     *
     * @param  mixed $data
     * @param bool|string $free_access
     * @param  int $entry_id
     * @return mixed string
     */
    private function _parse_data($data = null, $free_access = false, $entry_id = 0)
    {
        //get the data
        ee()->db->select('child_id as entry_id, order');
        ee()->db->where('parent_id', $entry_id);
        if(isset($this->field_id) && $this->field_id !== null)
        {
            ee()->db->where('field_id', $this->field_id);
        }
        $query = ee()->db->get('relationships');

        if($query->num_rows() > 0)
        {
            $return = array();
            foreach($query->result_array() as $row)
            {
                if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('parse_rel_data'))
                {
                    $return[] = ee()->webservice_entry_lib->get_entry($row['entry_id'], array('*'), true);
                }
                else
                {
                    $return[] = $row;
                }
            }

            return $return;
        }

        return $data;
    }
}
