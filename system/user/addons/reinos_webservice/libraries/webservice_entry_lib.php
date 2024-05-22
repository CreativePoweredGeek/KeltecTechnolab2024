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
require_once(PATH_THIRD.'reinos_webservice/config.php');

/**
 * Include helper
 */

class Webservice_entry_lib
{

    public $custom_fields = array();
    public $custom_fields_name = array();

    // set an array with special fields that we need to handle with detect_search
    public $custom_special_fields_name = array('channel');

    public $offset = 0;
    public $limit = 99;
    public $absolute_results = 0;
    public $total_results = 0;

    // include, here you can specify if the channel and/or categories are includeed
    public $include = array();

    //remove those fields from the filter
    public $remove_in_filter_post_fields = array('include', 'publisher_lang_id', 'return');

    // the data that the user posted
    private $post_data = [];

    // ----------------------------------------------------------------

    public function __construct()
    {

    }

    // ----------------------------------------------------------------

    /**
     * Get entry based on entry_id
     * It also has the pre_proces call to attach the data
     * We return an array of the values, not for futher use only for reading
     *
     * @access    public
     * @param int $entry_id
     * @return array
     * @internal param list $parameter
     */
    function get_entry($entry_id = 0)
    {
        //get the entry
        $entry = ee('Model')->get('ChannelEntry')->filter('entry_id', $entry_id);

        // no result, go back
        if($entry->count() == 0)
        {
            return false;
        }

        //get the first record of the collection
        $entry = $entry->first();

        //fetch the custom fields
        $this->fetch_custom_channel_fields($entry->channel_id);

        /** ---------------------------------------
        /**  Process the data per field
        /** ---------------------------------------*/
        $entry = $this->pre_process($entry);

        /** ---------------------------------------
        /** set the data correct and return it!
        /** ---------------------------------------*/
        return $this->readable($entry);
    }

    // ----------------------------------------------------------------

    /**
     * Get entry based on entry_id
     * It also has the pre_proces call to attach the data
     * We return an array of the values, not for futher use only for reading
     *
     * @access    public
     * @param $values
     * @param string $username
     * @param string $method
     * @return array
     * @internal param int $entry_id
     * @internal param list $parameter
     */
    function search_entry($values, $username = '', $method = '')
    {
        //clone values to post data
        $this->post_data = $values;

        //build the model query
        $query = ee('Model')->get('ChannelEntry');

        //fetch the custom fields
        $this->fetch_custom_channel_fields();

        //relationship model checks
        $channel_loaded = false;
        $category_loaded = false;

        $allowed_orderby = array('title', 'entry_date', 'entry_id');
        $orderby = $default_orderby = 'entry_date';
        $sort = $default_sort = 'desc';
        $results = null;

        //set the site_id
        $site_id = isset($values['site_id']) ? $values['site_id'] : ee()->config->item('site_id');
        unset($values['site_id']);

        //check include
        $this->include = isset($values['include']) ? explode('|', $values['include']) : array() ;

        //dates
        $start_on = null;
        $stop_before = null;
        $show_expired = true;

        //set the default fields
//        $field_names = array(
//            'title' => 'title',
//            'url_title' => 'url_title',
//            'channel_id' => 'channel_id',
//            'entry_id' => 'entry_id',
//            'status' => 'status',
//            'author_id' => 'author_id',
//            'site_id' => 'site_id',
//        );
//
//        //set the full field _name
//        if(!empty($this->custom_fields_name))
//        {
//            foreach($this->custom_fields_name as $name => $id )
//            {
//                $field_names[$name] = 'field_id_'.$id;
//            }
//        }

        //set the init time
        $offset_time = ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('offset_time');
        $time = $offset_time != '' ? ee()->localize->string_to_timestamp($offset_time) : ee()->localize->now;

        //round up or down
        if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('round_date') == 'up')
        {
            $time = strtotime(date('d-m-Y', $time). ' 23:59');
        }
        else if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('round_date') == 'down')
        {
            $time = strtotime(date('d-m-Y', $time). ' 00:00');
        }

        //------------------------------------------------------
        // search in all custom fields with the magic keyword search_all
        //------------------------------------------------------
        if(isset($values['search_all']))
        {
            if(!empty($this->custom_fields_name))
            {
                foreach($this->custom_fields_name as $name => $id)
                {
                    $values[$name] = $values['search_all'];
                }

                //inlcude title
                $values['title'] = 'or '.$values['search_all'];
            }

            unset($values['search_all']);
        }

        //---------------------------------------------------------------------------------------------
        // Special Date filters
        //---------------------------------------------------------------------------------------------
        $start_on = isset($values['start_on']) ? $values['start_on'] : false;
        $stop_before = isset($values['stop_before']) ? $values['stop_before'] : false;
        $show_expired = isset($values['show_expired']) ? $values['show_expired'] : false;
        $date_filters = $start_on !== false || $stop_before !== false || $show_expired !== false;

        //unset the value
        unset($values['start_on']);
        unset($values['stop_before']);
        unset($values['show_expired']);

        // Wrap it in a group
        if($date_filters)
        {
            $query->filterGroup();
        }

        // start_on
        // https://docs.expressionengine.com/latest/channel/channel_entries.html#start-on
        if($start_on !== false)
        {
            $query->filter('entry_date', '>=', strtotime($start_on));
        }

        // https://docs.expressionengine.com/latest/channel/channel_entries.html#stop-before
        if($stop_before !== false)
        {
            $query->filter('entry_date', '<=', strtotime($stop_before));
        }
        //or by setting
        else if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('show_feature_entries') == 0)
        {
            $query->filter('entry_date', '<=', $time);
        }

        //https://docs.expressionengine.com/latest/channel/channel_entries.html#show-expired
        if(isset($values['show_expired']))
        {
            $query->filter('expiration_date', '>=', $time);
        }
        //by setting
        else if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('show_expiration_entries') == 0)
        {
            $query->filter('expiration_date', '>=', time());
        }

        //end group
        if($date_filters)
        {
            $query->endFilterGroup();
        }

        //---------------------------------------------------------------------------------------------
        // First detect the sorting, limit etc
        //---------------------------------------------------------------------------------------------
        //detect limit
        if(isset($values['limit']))
        {
            $this->limit = $limit = (int)$values['limit'];
            unset($values['limit']);
        }

        //detect offset
        if(isset($values['offset']))
        {
            $this->offset = $offset = $values['offset'];
            unset($values['offset']);
        }

        //detect sort
        if(isset($values['sort']) && in_array($values['sort'], array('desc', 'asc')))
        {
            $sort = $values['sort'];
            unset($values['sort']);
        }
        else
        {
            $sort = $default_sort;
            unset($values['sort']);
        }

        //detect orderby
        if(isset($values['orderby']))
        {
            //custom field?
            if(array_key_exists($values['orderby'], $this->custom_fields_name))
            {
                $orderby = 'field_id_'.$this->custom_fields_name[$values['orderby']];
            }

            //default field
            else
            {
                $orderby = $values['orderby'];
            }

            unset($values['orderby']);
        }

        $query->order($orderby, $sort);

        // -------------------------------------------


        //Check the values array for an key with array. This array should be an and search
        //e.g data[group_and][group_one_name_just_pick_one][][title]=test|john
        // cat_id = 3 || (cat_id = 4 and cat_id = 5)
        $search_values = array();
        foreach($values as $field_name => $value)
        {
            //ignore some field out of the filters
            if(!in_array($field_name, $this->remove_in_filter_post_fields))
            {
                //with an array and key, we start a filterGroup
                //otherwise just extend the data without start a group
                if(
                    is_array($value) &&
                    !empty($value) &&
                    (
                        isset($value['group_and']) ||
                        isset($value['group_or'])
                    )
                )
                {
                    foreach($value as $group_type => $val)
                    {
                        if(!empty($val))
                        {
                            //loop over the groups
                            foreach($val as $v)
                            {
                                if(!empty($v))
                                {
                                    //detect what type of group
                                    $search_values[] = $group_type === 'group_or' ? 'startOrGroup' : 'startAndGroup';

                                    //loop over the fields
                                    foreach($v as $group_field_name => $term)
                                    {
                                        $search_values = array_merge($search_values, $this->detect_search($term, $group_field_name));
                                    }

                                    //end the group
                                    $search_values[] = 'endGroup';
                                }
                            }
                        }
                        else
                        {
                            $search_values = array_merge($search_values, $this->detect_search($val, $field_name));
                        }
                    }
                }
                else if(is_string($value))
                {
                    $search_values = array_merge($search_values, $this->detect_search($value, $field_name));
                }
            }
        }

        //------------------------------------------------------
        // Category prep
        //------------------------------------------------------
        $category_included_in_query = false;
        if(!empty($search_values))
        {
            foreach($search_values as &$search_options)
            {
                if(is_array($search_options))
                {
                    if(preg_match("/cat:/", $search_options['field_name']))
                    {
                        //include the category in the query
                        if(!$category_included_in_query)
                        {
                            $query->with('Categories');
                            $category_included_in_query = true;
                        }

                        //remove the prefix
                        $val = str_replace('cat:', '', $search_options['field_name']);

                        //any custom fields?
                        if (isset($this->_cat_fields[$site_id][$val]))
                        {
                            $search_options['field_name'] = 'Categories.'.'field_id_'.$this->_cat_fields[$site_id][$val];
                        }
                        else
                        {
                            //set the value again in the $values array
                            $search_options['field_name'] = 'Categories.'.$val;
                        }
                    }
                }
            }
        }

        //---------------------------------------------------------------------------------------------
        //loop over the values and build the correct where statement
        //---------------------------------------------------------------------------------------------
        foreach($search_values as $value)
        {
            //------------------------------------------------------
            // Channel search, formatter
            //------------------------------------------------------
            if (is_array($value) && ($value['field_name'] == 'channel' || $value['field_name'] == 'channel_name'))
            {
                //need tho load the channel model in the query?
                if(!$channel_loaded)
                {
                    $channel_loaded = true;
                    $query->with('Channel');
                }

                $value['field_name'] = 'Channel.channel_name';
            }

            //is it a group?
            if(is_string($value))
            {
                if($value === 'startAndGroup')
                {
                    $query->filterGroup();
                }

                else if($value === 'startOrGroup')
                {
                    $query->orFilterGroup();
                }

                else if($value === 'endGroup')
                {
                    $query->endFilterGroup();
                }
            }

            //------------------------------------------------------
            // Default search handle
            //------------------------------------------------------
            else
            {
                $this->execute_search_method($query, $value['search_method'], $value['search_type'], $value['field_name'], $value['term']);
            }
        }

        //the channels where the user may search
        //do not check when te user has free access
        if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Permissions')->has_free_access($method, $username) == 0)
        {
            //load the channel into the query if needed
            if(!$channel_loaded)
            {
                $channel_loaded = true;
                $query->with('Channel');
            }

            $assigned_channels = ee()->session->userdata('assigned_channels');

            if(is_array($assigned_channels) && !empty($assigned_channels))
            {
                $channel_ids = array_keys(ee()->session->userdata('assigned_channels'));

                $query->filterGroup();
                $query->filter('Channel.channel_id', 'IN', $channel_ids);
                $query->endFilterGroup();
            }

            else
            {
                return false;
            }
        }

        //------------------------------------------------------
        // set the site_id
        //------------------------------------------------------
        $query->filterGroup();
        $query->filter('site_id', $site_id);
        $query->endFilterGroup();

        //add a hook that return some entry_ids
        //* -------------------------------------------
        /* 'webservice_modify_search' hook.
        /*  - Added: 3.5.1
        */
        $modify_search_entry_ids = ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->add_hook('modify_search', $values, false, $this->custom_fields);
        if(is_array($modify_search_entry_ids))
        {
            //if there is nothing found, we need to trick the model so it found nothing. If we omit this, it will search
            // without in achtneming van de grid veld
            // entry_id will never exists :-)
            if(empty($modify_search_entry_ids))
            {
                $modify_search_entry_ids = array(0);
            }

            //perform the filter
            $query->filter('entry_id', 'IN', $modify_search_entry_ids);
        }

        //finally set the limit
        $query->limit($this->limit);

        //set the absolute_result count
        try
        {
            $this->absolute_results = $query->count();
        }
        catch (Exception $e)
        {
            return array('error' => $e->getMessage());
        }

        //place the offset here, so the count can work :|
        $query->offset($this->offset);

        // print_r($query->getFilters());
        // print_r(ee('Database')->getLog()->getQueries());exit;

       return $query;
    }

    /**
     * parse the query that we get from search_entry
     *
     */
    public function parseQuery($query) {
        //define return array
        $return_entry_data = array();

        // any result?
        if ($this->absolute_results > 0)
        {
            foreach($query->all() as $entry)
            {
                /* -------------------------------------------
                /* 'webservice_entry_row' hook.
                /*  - Added: 6.1.0
                */
                $entry = ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->add_hook('post_entry_row', $entry, false, $this->custom_fields, $this->post_data);
                // -------------------------------------------

                /** ---------------------------------------
                /**  Process the data per field
                /** ---------------------------------------*/
                $entry = $this->pre_process($entry);

                /** ---------------------------------------
                /** set the data correct and return it!
                /** ---------------------------------------*/
                $entry_data = $this->readable($entry);

                /* -------------------------------------------
                /* 'webservice_search_entry_end' hook.
                /*  - Added: 3.2
                */
                $entry_data = ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->add_hook('search_entry_per_entry', $entry_data, false, $this->custom_fields, $this->post_data);
                $entry_data = ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->add_hook('read_entry_per_entry', $entry_data, false, $this->custom_fields, $this->post_data);
                // -------------------------------------------

                /* -------------------------------------------
                /* 'webservice_entry_row' hook.
                /*  - Added: 3.5
                */
                $entry_data = ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->add_hook('entry_row', $entry_data, false, $this->custom_fields, $this->post_data);
                // -------------------------------------------

                //assign the data to the array
                $return_entry_data[] = $entry_data;
            }

            $this->total_results = count($return_entry_data);

            return $return_entry_data;
        }

        return false;
    }

    // --------------------------------------------------------------------

    /**
     *
     * Process the data per field
     *
     * @param $entry
     * @return mixed
     */
    private function pre_process($entry)
    {
        if(!empty($this->custom_fields))
        {
            foreach($this->custom_fields as $key => $val)
            {
                $field_name = 'field_id_'.$val['field_id'];

                $entryArray = $entry->toArray();

                if(array_key_exists($field_name, $entryArray))
                {
                    $entry->{$field_name} = ee()->webservice_fieldtype->pre_process($entry->{$field_name}, $val['field_type'], $val['field_name'], $val, null, 'search_entry', $entry->entry_id);
                }
            }
        }

        return $entry;
    }

    // ----------------------------------------------------------------

    /**
     * make the data readable for the end user
     *
     * @param $entry
     * @return mixed
     */
    private function readable($entry)
    {
        $entry_array = $entry->toArray();
        if(in_array('channel', $this->include) || in_array('channels', $this->include))
        {
            $entry_array['channel'] = $entry->Channel->toArray();
        }

        if(in_array('categories', $this->include) || in_array('category', $this->include))
        {
            $entry_array['categories'] = $entry->Categories->toArray();
        }

        if(in_array('member', $this->include) || in_array('author', $this->include))
        {
            $entry_array['member'] = $entry_array['author'] = array(
                'username' => $entry->Author->username,
                'member_id' => $entry->Author->member_id,
                'screen_name' => $entry->Author->screen_name,
                'email' => $entry->Author->email
            );
        }

        $entry_array['edit_date_raw'] = json_encode($entry->edit_date);
        $entry_array['edit_date'] = $entry->edit_date == '' ? $entry->create_date :  $entry->edit_date->getTimestamp();

        // set the correct field name
        $custom_fields = preg_grep("/field_id_(.*?)/", array_keys($entry_array));
        if(!empty($custom_fields))
        {
            foreach($custom_fields as $fieldname)
            {
                $field_id = str_replace('field_id_', '', $fieldname);
                $field = $this->_search_fieldname($field_id, $this->custom_fields);

                //set the new value
                if(isset($field['field_name'])) {
                    $entry_array[$field['field_name']] = $entry_array['field_id_'.$field_id];
                }

                //remove the raw values
                unset($entry_array['field_id_'.$field_id]);
                unset($entry_array['field_ft_'.$field_id]);

            }
        }

        //@todo also convert the field_id from categories to normal names

        return array_filter($entry_array);
    }

    // ----------------------------------------------------------------

    /**
     * Search the key name of the field
     *
     * @access    public
     * @param $field_id
     * @param $fields
     * @return array
     */
    private function _search_fieldname($field_id, $fields)
    {
        foreach($fields as $field)
        {
            if($field['field_id'] == $field_id)
            {
                return $field;
            }
        }
    }

    // ----------------------------------------------------------------

    /**
     * Fetches custom channel fields from page flash cache.
     * If not cached, runs query and caches result.
     * @access private
     * @param null $channel_id
     * @return bool
     */
    public function fetch_custom_channel_fields($channel_id = null)
    {
        ee()->load->model('webservice_model');
        $fields = ee()->webservice_model->get_fields($channel_id);

        // reset the previous state
        $this->custom_fields_name = [];
        $this->custom_fields = [];

        if (!empty($fields))
        {
            foreach ($fields as $field_name => $row)
            {
                // assign standard custom fields
                $this->custom_fields_name[$field_name] = $row['field_id'];
                $this->custom_fields[] = $row;
            }
            //@todo cache the result
            //ee()->session->cache['channel']['custom_channel_fields'] = $this->fields;
            return true;
        }
        else
        {
            return false;
        }
    }

    // ----------------------------------------------------------------

    /**
     * Fetches custom category fields from page flash cache.
     * If not cached, runs query and caches result.
     * @access private
     * @return boolean
     */
    public function fetch_custom_category_fields()
    {
        $this->_cat_fields = array();

        if (isset(ee()->session->cache['webservice']['custom_category_fields']))
        {
            $this->_cat_fields = ee()->session->cache['webservice']['custom_category_fields'];
            return true;
        }

        // not found so cache them
        $query = ee('Model')->get('CategoryField');

        if ($query->count() > 0)
        {
            foreach ($query->all()->toArray() as $row)
            {
                // assign standard fields
                $this->_cat_fields[$row['site_id']][$row['field_name']] = $row['field_id'];
                return true;
            }
            ee()->session->cache['webservice']['custom_category_fields'] = $this->_cat_fields;
        }
        else
        {
            return false;
        }

    }

    // ----------------------------------------------------------------

    /**
     * @param $value
     * @param string $field_name
     * @param bool $check_id_field
     * @return array
     */
    public function detect_search($value, $field_name = '', $check_id_field = true)
    {
        //if(array_key_exists($field_name, $this->custom_fields_name) || in_array($field_name, $this->custom_special_fields_name))
        //{
        //-----------------------------------------------
        //How do we search
        //-----------------------------------------------
        $search_method = 'filter';

        //------------------------------------------------------
        // Check if we have AND(&&) or OR(|) for multiple values
        // @note both , and | are a or filter due the limitation of EE
        //------------------------------------------------------
        if (strpos($value, ',') !== false)
        {
            //set the filter method
            $search_method = 'orFilter';
            //$search_method = 'filter';

            //multiple values to array
            $value = array_filter(explode(',', $value));
        }
        else if (strpos($value, '|') !== false)
        {
            //set the filter method
            $search_method = 'orFilter';

            //multiple values to array
            $value = array_filter(explode('|', $value));
        }

        else
        {
            //also we have to check for the word OR
            if(substr( $value, 0, 3 ) === "or ")
            {
                $search_method = 'orFilter';
                $value = str_replace('or ', '', $value);
            }

            //convert to array
            $value = array($value);
        }

        //-----------------------------------------------
        //Loop over the values and check per value how we have to search
        //-----------------------------------------------
        $search_data = array();
        foreach($value as $val)
        {
            //default
            $search_type = 'LIKE';

            //exact Match e.g.: =pickle
            if (strncmp($val, '=', 1) ==  0)
            {
                //set the type
                $search_type = '==';

                //reset the value
                $val = ltrim($val, '=');
            }

            // NOT match
            else if (strncmp($val, 'not ', 4) ==  0)
            {
                //set the type
                $search_type = '!=';

                //reset the value
                $val = str_replace('not ', '', $val);
            }

            // Numeric search
            else if (strncmp($val, '< ', 1) ==  0)
            {
                //set the type
                $search_type = '<';

                //reset the value
                $val = ltrim($val, '<');
            }

            // Numeric search
            else if (strncmp($val, '> ', 1) ==  0)
            {
                //set the type
                $search_type = '>';

                //reset the value
                $val = ltrim($val, '>');
            }

            // Numeric search
            else if (strncmp($val, '<= ', 2) ==  0)
            {
                //set the type
                $search_type = '<=';

                //reset the value
                $val = str_replace('<= ', '', $val);
            }

            // Numeric search
            else if (strncmp($val, '>= ', 1) ==  0)
            {
                //set the type
                $search_type = '>=';

                //reset the value
                $val = str_replace('>= ', '', $val);
            }

            //check if the value of the field has _id, then we have to search for an exact search
            //because we can be sure that this is a int
            if ($check_id_field && strpos($field_name, '_id') !== false)
            {
                $search_type = '==';
            }

            //is it a contains search
            if($search_type == 'LIKE')
            {
                $val = '%'.$val.'%';
            }

            //check the occurrence of IS_EMPTY and make it a real empty value
            if($val == 'IS_EMPTY')
            {
                $val = '';
            }

            //check the occurrence of ~ what is an %
            $val = str_replace('~', '%', $val);

            //set the data
            $search_data[] = array(
                'search_method' => $search_method,
                'search_type' => $search_type,
                'term' => $val,
                'field_name' => isset($this->custom_fields_name[$field_name]) ? 'field_id_'.$this->custom_fields_name[$field_name] : $field_name // set the correct field
            );
        }

        return $search_data;
        // }

        //return array();

    }

    // ----------------------------------------------------------------

    /**
     * Execute search on a model query
     *
     * @param $query
     * @param $search_method
     * @param $search_type
     * @param $field_identifier
     * @param $term
     */
    public function execute_search_method($query, $search_method, $search_type, $field_identifier, $term)
    {
        //call to $query->search()
        if($search_method == 'search')
        {
            //echo '$query->'.$search_method.'('.$field_identifier.', '.$term.')';
            call_user_func(array($query, $search_method), $field_identifier, $term);
        }
        //call to $query->filter()
        else
        {
            //echo '$query->'.$search_method.'('.$field_identifier.', '.$search_type.', '.$term.')';
            call_user_func(array($query, $search_method), $field_identifier, $search_type, $term);
        }
    }

    // ----------------------------------------------------------------

    /**
     * Execute search on a DB object
     * @param $query
     * @param $search_method
     * @param $search_type
     * @param $field_identifier
     * @param $term
     *
     * @return mixed
     */
    public function execute_search_method_db($query, $search_method, $search_type, $field_identifier, $term)
    {
        //for db_search with the old ee()->db->where() a % should be removed, as the method add that
        $term = str_replace('%', '', $term);

        //call to $query->search()
        if($search_method == 'search')
        {
            //echo '$query->'.$search_method.'('.$field_identifier.', '.$term.')';
            call_user_func(array($query, $search_method), $field_identifier, $term);
        }
        //call to $query->filter()
        else
        {
            if($search_type != '==')
            {
                $search_method = 'like';
            }

            //echo '$query->'.$search_method.'('.$field_identifier.', '.$term.')';
            call_user_func(array($query, $search_method), $field_identifier, $term);
        }
    }
}
