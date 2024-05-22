<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Super_export_model extends CI_Model
{

	function __construct()
	{

	}

    function getPagesData($site_id)
    {

        $this->db->select('site_pages');
        $this->db->from('sites');
        $this->db->where('site_id', $site_id);

        $get = $this->db->get();
        if($get->num_rows == 0)
        {
            return false;
        }

        $pages = $get->row("site_pages");
        $pages = unserialize(base64_decode($pages));
        return isset($pages[1]) ? $pages[1] : false;

    }

    function getStructureData($site_id)
    {

        $this->db->limit(99999);
        $this->db->select('entry_id, parent_id as structure_parent_id, listing_cid as structure_listing_cid, lft as structure_lft, rgt as structure_rgt, hidden as structure_hidden, structure_url_title');
        $this->db->from('structure');
        $this->db->where('site_id', $site_id);

        $get = $this->db->get();
        if($get->num_rows == 0)
        {
            return false;
        }

        $tmp = [];
        foreach ($get->result_array() as $key => $value)
        {
            $tmp[$value['entry_id']] = $value;
            unset($tmp[$value['entry_id']]['entry_id']);
        }

        return $tmp;
    }

    function getSeoLiteData($entry_ids)
    {

        $this->db->select("entry_id, title, keywords, description");
        $this->db->from('seolite_content');
        $this->db->where_in('entry_id', $entry_ids);
        $get = $this->db->get();
        if($get->num_rows == 0)
        {
            return false;
        }

        $result = $get->result_array();
        $ret    = array();
        foreach ($result as $seo)
        {
            $ret[$seo['entry_id']] = array(
                'seo_lite_title'        => $seo['title'],
                'seo_lite_keywords'     => $seo['keywords'],
                'seo_lite_description'  => $seo['description'],
            );
        }

        return $ret;

    }

    function gridData($params)
    {

        $this->db->order_by('row_order', 'asc');
        $this->db->select("*");
        $this->db->from('channel_grid_field_' . $params['field_id']);
        $this->db->where('entry_id', $params['entry_id']);
        if(isset($params['fluid']) && $params['fluid'] === true)
        {
            $this->db->where('fluid_field_data_id', $params['fluid_field_data_id']);
        }
        else
        {
            $this->db->where('fluid_field_data_id', '0');
        }
        $get = $this->db->get();

        if($get->num_rows == 0) return false;
        $data = $get->result_array();

        $this->db->order_by('col_order', 'asc');
        $this->db->select('col_id, field_id, col_type, col_name, col_label, col_settings');
        $this->db->from('grid_columns');
        $this->db->where('field_id', $params['field_id']);
        $this->db->where('content_type', 'channel');
        $get = $this->db->get();

        if($get->num_rows == 0) return false;
        $colData    = $get->result_array();
        $fields     = array();

        foreach ($colData as $field)
        {
            $fields['col_id_' . $field['col_id']] = $field;
            $fields['col_id_' . $field['col_id']]['col_settings'] = ($fields['col_id_' . $field['col_id']]['col_settings'] != "") ? json_decode($fields['col_id_' . $field['col_id']]['col_settings'], true) : "";
        }
        unset($colData);

        return [
            'data'      => $data,
            'fields'    => $fields,
        ];

    }

    function matrixData($params)
    {

        $this->db->order_by('row_order', 'asc');
        $this->db->select("*");
        $this->db->from('matrix_data');
        $this->db->where('entry_id', $params['entry_id']);
        $this->db->where('field_id', $params['field_id']);
        $get = $this->db->get();

        if($get->num_rows == 0) return false;
        $data = $get->result_array();

        $this->db->order_by('col_order', 'asc');
        $this->db->select('col_id, field_id, col_type, col_name, col_label, col_settings');
        $this->db->from('matrix_cols');
        $this->db->where('field_id', $params['field_id']);
        $get = $this->db->get();

        if($get->num_rows == 0) return false;
        $colData    = $get->result_array();
        $fields     = array();

        foreach ($colData as $field)
        {
            $fields['col_id_' . $field['col_id']] = $field;
            $fields['col_id_' . $field['col_id']]['col_settings'] = ($fields['col_id_' . $field['col_id']]['col_settings'] != "") ? unserialize(base64_decode($fields['col_id_' . $field['col_id']]['col_settings'])) : "";
        }
        unset($colData);

        return [
            'data'      => $data,
            'fields'    => $fields,
        ];

    }

    function relationshipsData($params, $field = "title")
    {

        $this->db->order_by('order', 'asc');
        $this->db->select('ct.' . $field);
        $this->db->from('relationships r');
        $this->db->join('channel_titles ct', 'ct.entry_id = r.child_id');
        $this->db->where('r.parent_id', $params['parent_id']);

        if(isset($params['grid_row_id']) && $params['grid_row_id'] != "")
        {
            $this->db->where('r.grid_row_id', $params['grid_row_id']);
            $this->db->where('r.grid_col_id', $params['grid_col_id']);
            $this->db->where('r.grid_field_id', $params['grid_field_id']);
        }
        else
        {
            $this->db->where('r.field_id', $params['field_id']);
            $this->db->where('r.grid_field_id', '0');
        }

        if(isset($params['fluid']) && $params['fluid'] === true)
        {
            $this->db->where('r.fluid_field_data_id', $params['fluid_field_data_id']);
        }


        $get = $this->db->get();
        if($get->num_rows == 0) return false;

        $result = $get->result_array();
        $ret    = array();
        foreach ($result as $value)
        {
            $ret[] = $value[$field];
        }

        unset($get);
        unset($result);

        return $ret;

    }

    function playaData($params, $field = "title")
    {

        $this->db->order_by('rel_order', 'asc');
        $this->db->select('ct.' . $field);
        $this->db->from('playa_relationships r');
        $this->db->join('channel_titles ct', 'ct.entry_id = r.child_entry_id');
        $this->db->where('r.parent_entry_id', $params['parent_entry_id']);
        $this->db->where('r.parent_field_id', $params['parent_field_id']);

        if(isset($params['parent_row_id']) && $params['parent_row_id'] != "")
        {
            $this->db->where('r.parent_row_id', $params['parent_row_id']);
            $this->db->where('r.parent_col_id', $params['parent_col_id']);
        }

        $get = $this->db->get();
        if($get->num_rows == 0) return false;

        $result = $get->result_array();
        $ret    = array();
        foreach ($result as $value)
        {
            $ret[] = $value[$field];
        }

        unset($get);
        unset($result);

        return $ret;

    }

    function assetsData($params)
    {

        $this->db->select('asource.settings, af.source_id, af.filedir_id, folder.full_path, folder.source_type, af.file_name');
        $this->db->from('assets_files af');
        $this->db->join('assets_selections aselections', 'aselections.file_id = af.file_id');
        $this->db->join('assets_sources asource', 'asource.source_id = af.source_id', "left");
        $this->db->join('assets_folders folder', 'folder.folder_id = af.folder_id', "left");

        if(isset($params['bloqs']) && $params['bloqs'] === true && $params['value'] != "")
        {
            $this->db->where_in("file_name", explode("\n", $params['value']));
            $this->db->group_by("file_name");
        }
        else
        {
            $this->db->where("entry_id", $params['entry_id']);
            $this->db->where("field_id", $params['field_id']);
        }

        if(isset($params['row_id']))
        {
            $this->db->where("row_id", $params['row_id']);
            $this->db->where("col_id", $params['col_id']);
        }

        $get = $this->db->get();
        if($get->num_rows == 0){return false;}

        return $get->result_array();

    }

    function lowEventsData($params)
    {

        $this->db->limit('1');
        $this->db->select('start_date, start_time, end_date, end_time, all_day');
        $this->db->from('low_events');
        $this->db->where("entry_id", $params['entry_id']);
        $this->db->where("field_id", $params['field_id']);

        $get = $this->db->get();
        if($get->num_rows == 0){return false;}

        $result = $get->result_array();
        return $result[0];

    }

    function getTranscribeLanguages()
    {
        $this->db->select('id, name, abbreviation');
        $this->db->from('transcribe_languages');
        $get = $this->db->get();
        if($get->num_rows == 0){return false;}

        $ret = [];
        foreach ($get->result_array() as $key => $value) {
            $ret[$value['id']] = $value;
        }

        return $ret;
    }

    function transcribeRelationshipData($entry_ids = [], $language_ids = [])
    {
        ee()->db->select('entry_id, relationship_id, language_id');
        if(! empty($entry_ids))
        {
            ee()->db->where_in('entry_id', $entry_ids);
        }

        if(! empty($language_ids))
        {
            ee()->db->where_in('language_id', $language_ids);
        }

        ee()->db->limit('999999');
        $relationships = ee()->db->get('transcribe_entries_languages');
        if($relationships->num_rows == 0){return false;}

        $ret = [];
        foreach ($relationships->result_array() as $key => $value) {
            $ret[$value['entry_id']] = $value;
        }

        return $ret;
        // return $relationships->result_array();
    }

    function orderItems($params)
    {
        ee()->db->select('*');
        ee()->db->from("cartthrob_order_items");
        ee()->db->where("order_id", $params["entry_id"]);
        $get = ee()->db->get();

        $result = $get->result_array();
        foreach ($result as $key => &$item) {
            $item["extra"] = @unserialize(@base64_decode($item["extra"]));
        }

        return $result;
    }
}