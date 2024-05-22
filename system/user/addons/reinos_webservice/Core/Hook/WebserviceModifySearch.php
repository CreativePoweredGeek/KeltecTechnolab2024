<?php

namespace Reinos\Webservice\Core\Hook;

/**
 * @author		Rein de Vries <support@reinos.nl>
 * @link		http://addons.reinos.nl
 * @copyright 	Copyright (c) 2011 - 2021 Reinos.nl Internet Media
 * @license     http://addons.reinos.nl/commercial-license
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
class WebserviceModifySearch extends AbstractHook
{
    /**
     *
     * When a member is about to be deleted, this hook gives the chance to run a custom
     * deletion routine and/or stop ExpressionEngine from running its own member deletion
     * routine for certain members
     *
     * @param $member
     * @param $values
     * @return void
     */
    public function execute($values, $fields)
    {
        //firstly build the Grid Models
        //@todo move this to a hook where we know the grid is updated instead of building it every time
        //$this->load_dynamic_grid_model();

        //set an array for the entry_ids
        $entry_ids = array();

        if(!empty($values))
        {
            foreach($values as $key=>$value)
            {
                //is an array, we got
                if(is_array($value) && !empty($value))
                {
                    //first reset the index for the $value array
                    reset($value);

                    //now get the field name of the grid field
                    $field = key($value);

                    //and get the value
                    $value = $value[$field];

                    //make sure we also check some grid or matrix fields
                    if(!empty($fields))
                    {
                        foreach($fields as $field_data)
                        {
                            //grid, we are gonna search also in the grid data
                            if($field_data['field_type'] == 'grid' && $field_data['field_name'] == $key)
                            {
                                //get the column
                                $column = ee()->db->from('grid_columns')->where('field_id', $field_data['field_id'])->where('col_name', $field)->get();

                                if($column->num_rows() > 0)
                                {

                                    //setup the query
                                    $query = ee()->db->select('entry_id')->from('channel_grid_field_'.$field_data['field_id']);

                                    //include the grid model
                                    //$query->with('Grid_'.$field_data['field_id']);

                                    //get the name
                                    $field_identifier = 'col_id_'.$column->row('col_id');

                                    //setup the search values and options
                                    $search_options = ee()->webservice_entry_lib->detect_search($value, $field_identifier, false);
                                    if(!empty($search_options))
                                    {
                                        foreach($search_options as $search_option)
                                        {

                                            $value = $search_option['term'];
                                            $search_method = $search_option['search_method'] == 'search' ? 'like' : 'where';
                                            $search_type = $search_option['search_type'];

                                            //execute the search method on the query
                                            ee()->webservice_entry_lib->execute_search_method_db($query, $search_method, $search_type, $field_identifier, $value);
                                        }
                                    }

                                    //execute
                                    $result = $query->get();

                                    if($result->num_rows() > 0)
                                    {
                                        foreach($result->result() as $row)
                                        {
                                            $entry_ids[] = $row->entry_id;
                                        }
                                    }
                                }

                                return $entry_ids;
                            }

                            //@todo test with EE3 and EE4 so the new search search mechanism will work here also
                            //Matrix, we are gonna search in the matrix data
                            if($field_data['field_type'] == 'matrix' && $field_data['field_name'] == $key)
                            {
                                ee()->db->where('col_name', $field);
                                ee()->db->from('matrix_cols');
                                $query = ee()->db->get();

                                if($query->num_rows())
                                {
                                    ee()->db->select('entry_id');
                                    if (strncmp($value, '=', 1) ==  0)
                                    {
                                        $value = substr($value, 1);
                                        ee()->db->where('col_id_'.$query->row()->col_id, $value);
                                    }
                                    else
                                    {
                                        ee()->db->like('col_id_'.$query->row()->col_id, $value);
                                    }

                                    ee()->db->from('matrix_data');
                                    $query = ee()->db->get();

                                    $entry_id = array();
                                    if($query->num_rows())
                                    {

                                        foreach($query->result() as $entry)
                                        {
                                            $entry_id[$entry->entry_id] = $entry->entry_id;
                                        }
                                    }
                                }

                                return $entry_ids;
                            }
                        }
                    }
                }
            }

//            // quick Publisher hack, to search in the Publisher DB as the ->with() for the model service does not work
//            // https://github.com/ExpressionEngine/ExpressionEngine/issues/31
//            if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->is_installed('publisher'))
//            {
//                //entry holder
//                $entry_ids = array();
//
//                //start the query
//                ee()->db->from('publisher_data');
//
//                //first get the fields and their id
//                foreach($values as $key => $value)
//                {
//                    foreach($fields as $field)
//                    {
//                        if($field['field_name'] == $key)
//                        {
//                            ee()->db->like('field_id_'.$field['field_id'], $value);
//                        }
//                    }
//                }
//
//                $publisher_query = ee()->db->get();
//                if($publisher_query->num_rows() > 0)
//                {
//                    foreach($publisher_query->result() as $row)
//                    {
//                        $entry_ids[] = $row->entry_id;
//                    }
//                }
//
//                return $entry_ids;
//            }
        }
    }

    private function load_dynamic_grid_model()
    {
        //load the grid fields
        $query = ee('Model')->get('ChannelField')->filter('field_type', 'grid');

        if($query->count() > 0)
        {
            //get the copy of the grid base file
            $_grid_model_content = file_get_contents(PATH_THIRD.'reinos_webservice/Model/Grid.php');

            foreach($query->all() as $field)
            {
                //make a copy
                $grid_model_content = $_grid_model_content;

                $grid_model_columns = array();

                //get the fields
                $columns = ee()->db->where('field_id', $field->field_id)->from('grid_columns')->get();
                if($columns->num_rows() > 0)
                {
                    foreach($columns->result() as $column)
                    {
                        $grid_model_columns[] = 'protected $col_id_'.$column->col_id.';';
                    }
                }

                $grid_model_name = 'Grid_'.$field->field_id;
                $grid_model_content = str_replace('{n}', $field->field_id, $grid_model_content);
                $grid_model_content = str_replace('{fields}', implode("\n", $grid_model_columns), $grid_model_content);

                file_put_contents(PATH_THIRD.'reinos_webservice/Model/'.$grid_model_name.'.php', $grid_model_content);
            }
        }
    }

    private function get_column($col_name = '')
    {
        return ee()->db->from('grid_columns')->where('col_name', $col_name)->get();
    }
}
