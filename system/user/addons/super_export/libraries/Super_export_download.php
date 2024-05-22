<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Super_export_download
{

    public $site_id     = "";
    public $data        = array();
    public $delim       = ",";
    public $enclosure   = '"';
    public $xmlRoot     = "root";
    public $xmlElement  = "element";
    public $newline     = "\n";
    public $tab         = "\t";

    public function __construct()
    {

        $this->site_id = ee()->config->item("site_id");
        ee()->load->model('Super_export_model', 'superExportModel');
        ee()->lang->loadfile('super_export');

    }

    function process($vars)
    {

        $exportSetting = false;
        if((isset($vars['id']) && $vars['id'] != "") || (isset($_GET['id']) && $_GET['id'] != ""))
        {
            $id = (isset($vars['id']) && $vars['id'] != "") ? $vars['id'] : $_GET['id'];
            $exportSetting = ee('Model')->get('super_export:ExportData', $id)->first();
        }
        elseif((isset($vars['token']) && $vars['token'] != "") || (isset($_GET['token']) && $_GET['token'] != ""))
        {
            $token = (isset($vars['token']) && $vars['token'] != "") ? $vars['token'] : $_GET['token'];
            $exportSetting = ee('Model')->get('super_export:ExportData')->filter('token', $token)->first();
        }

        if(! $exportSetting)
        {

            $error = lang('error_no_export_settings_found');

            /*if($vars['type'] = "ajax")
            {
                return $this->_dumpJson(array(
                    'status'    => 'error',
                    'error'     => $error
                ));
            }
            else
            {
            }*/
            if(defined('REQ') && REQ == 'CP')
            {
                show_error($error);
            }
            else
            {
                return ee()->output->show_user_error('general', $error);
            }
        }

        $this->data['vars']                         = $vars;
        $this->data['exportSettingQuery']           = $exportSetting;
        $this->data['exportSetting']                = $exportSetting->getValues();
        $this->data['exportSetting']['settings']    = json_decode(base64_decode($this->data['exportSetting']['settings']), true);
        $this->data['generalSettings']              = ee()->exportSettings->getExportSettings($this->data['exportSetting']['site_id'])->getValues();
        $fields                                     = array();

        $channel = ee('Model')->get('Channel', $this->data['exportSetting']['channel_id'])->first();
        $this->data['exportSetting']['categoryGroups'] = array();
        foreach ($channel->getCategoryGroups() as $cat_group)
        {
            $this->data['exportSetting']['categoryGroups'][$cat_group->group_id] = "category_" . $this->_sanitize($cat_group->group_name);
        }

        if(isset($this->data['exportSetting']['settings']['dynamic_fields']) && count($this->data['exportSetting']['settings']['dynamic_fields']))
        {

            foreach ($channel->getAllCustomFields() as $field)
            {

                if(in_array($field->field_id, $this->data['exportSetting']['settings']['dynamic_fields']))
                {

                    $fields[$field->field_id] = array(
                        'field_id'          => $field->field_id,
                        'field_label'       => $field->field_label,
                        'field_type'        => $field->field_type,
                        'field_name'        => $field->field_name,
                        'legacy_field_data' => $field->legacy_field_data,
                        'field_settings'    => $field->field_settings,
                    );

                }

            }

        }
        $this->data['exportSetting']['settings']['dynamic_fields'] = $fields;

        $temp = ee('Model')->get('UploadDestination')->all()->getDictionary('id', 'url');
        $this->data['exportSetting']['settings']['upload_destinations']         = array();
        $this->data['exportSetting']['settings']['upload_destination_search']   = array();
        $this->data['exportSetting']['settings']['upload_destination_replace']  = array();
        foreach ($temp as $key => $value)
        {
            $this->data['exportSetting']['settings']['upload_destinations'][$key] = $value;
            $this->data['exportSetting']['settings']['upload_destination_search'][]   = '{filedir_' . $key . '}';
            $this->data['exportSetting']['settings']['upload_destination_replace'][]  = $value;
        }

        if(
            isset($this->data['exportSetting']['settings']['export_extra']) &&
            is_array($this->data['exportSetting']['settings']['export_extra']) &&
            (
                in_array('pages', $this->data['exportSetting']['settings']['export_extra']) ||
                in_array('structure', $this->data['exportSetting']['settings']['export_extra'])
            )
        )
        {
            $this->data['pages'] = ee()->superExportModel->getPagesData($this->data['exportSetting']['site_id']);
            $base_url = ee()->config->item('base_url');
            $this->data['pages']['url'] = rtrim(str_replace("{base_url}", $base_url, $this->data['pages']['url']), '/');

            if(in_array('structure', $this->data['exportSetting']['settings']['export_extra']))
            {
                $this->data['structure'] = ee()->superExportModel->getStructureData($this->data['exportSetting']['site_id']);
            }
        }

        unset($exportSetting);
        unset($fields);
        unset($channel);
        unset($temp);

        return $this->generateExportData();

    }

    function generateExportData()
    {

        $cnt                        = 0;
        $this->data['exportData']   = array();

        $this->transcribe = [];
        $this->transcribe['languages'] = false;
        $this->transcribe['allowedLangs'] = [];
        $this->transcribe['installed'] = ee('Model')->get('Module')->filter('module_name', "Transcribe")->first();
        if($this->transcribe['installed']) { $this->transcribe['installed'] = true; }

        $channelEntries = ee('Model')->get('ChannelEntry')->filter('channel_id', $this->data['exportSetting']['channel_id']);
        if(isset($this->data['exportSetting']['settings']['status']) && is_array($this->data['exportSetting']['settings']['status']) && count($this->data['exportSetting']['settings']['status']))
        {
            foreach ($this->data['exportSetting']['settings']['status'] as $key => $value)
            {
                if($value == "")
                {
                    unset($this->data['exportSetting']['settings']['status'][$key]);
                }
            }

            if(! empty($this->data['exportSetting']['settings']['status']))
            {
                $channelEntries = $channelEntries->filter('status_id', 'IN', $this->data['exportSetting']['settings']['status']);
            }
        }

        if(isset($this->data['exportSetting']['settings']['from']) && $this->data['exportSetting']['settings']['from'] != "")
        {
            $channelEntries = $channelEntries->filter('entry_date', '>=', strtotime($this->data['exportSetting']['settings']['from']));
        }

        if(isset($this->data['exportSetting']['settings']['to']) && $this->data['exportSetting']['settings']['to'] != "")
        {
            $channelEntries = $channelEntries->filter('entry_date', '<=', strtotime($this->data['exportSetting']['settings']['to']));
        }

        if($this->transcribe['installed'])
        {

            $this->transcribe['languages'] = ee()->superExportModel->getTranscribeLanguages();
            if($this->transcribe['languages'])
            {

                if(
                    isset($this->data['exportSetting']['settings']['transcribe_language']) &&
                    ! empty($this->data['exportSetting']['settings']['transcribe_language']) &&
                    is_array($this->data['exportSetting']['settings']['transcribe_language']) &&
                    $this->data['exportSetting']['settings']['transcribe_language'][0] != ''
                )
                {
                    foreach ($this->transcribe['languages'] as $key => $value)
                    {
                        if(in_array($value['id'], $this->data['exportSetting']['settings']['transcribe_language']))
                        {
                            $this->transcribe['allowedLangs'][] = $value['id'];
                        }
                    }

                    $entryIds = (clone $channelEntries)->all()->pluck('entry_id');
                    $this->transcribe['relationship'] = ee()->superExportModel->transcribeRelationshipData($entryIds, $this->transcribe['allowedLangs']);
                    $relatedEntries = [];

                    if(! empty($this->transcribe['relationship']))
                    {
                        $channelEntries->filter('entry_id', 'IN', array_keys($this->transcribe['relationship']));
                    }
                    else
                    {
                        // If no entry found in transcribe, fallback set so not all the entries get exported from diff languages
                        $channelEntries->filter('entry_id', '99999999999999999999');
                    }
                }
                else
                {
                    foreach ($this->transcribe['languages'] as $key => $value)
                    {
                        $this->transcribe['allowedLangs'][] = $value['id'];
                    }

                    $entryIds = (clone $channelEntries)->all()->pluck('entry_id');
                    $this->transcribe['relationship'] = ee()->superExportModel->transcribeRelationshipData($entryIds);
                }

            }
        }

        if($this->data['vars']['type'] == "ajax")
        {
            if($this->data['vars']['limit'] == 0)
            {
                $this->data['vars']['limit'] = $this->data['exportSetting']['settings']['batch'];
            }

            $this->data['vars']['total'] = $channelEntries->count();
        }
        else
        {
            $this->data['vars']['limit'] = null;
        }

        $channelEntries = $channelEntries->limit($this->data['vars']['limit'])->offset($this->data['vars']['offset'])->all();

        if(isset($this->data['exportSetting']['settings']['export_extra']) && is_array($this->data['exportSetting']['settings']['export_extra']) && in_array('seo_lite', $this->data['exportSetting']['settings']['export_extra']))
        {
            $entry_ids = $channelEntries->pluck('entry_id');
            if(! empty($entry_ids))
            {
                $this->data['seo_lite'] = ee()->superExportModel->getSeoLiteData($entry_ids);
            }
        }

        foreach ($channelEntries as $entry)
        {

            $this->data['exportData'][$cnt] = array();
            foreach ($this->data['exportSetting']['settings']['default_fields'] as $key => $field)
            {

                if($field == "")
                {
                    unset($this->data['exportSetting']['settings']['default_fields'][$key]);
                    continue;
                }

                $fieldKey = $field;
                if($this->data['generalSettings']['csv_export_key'] == "field_label" && $this->data['exportSetting']['format'] == "csv")
                {
                    $fieldKey = lang($field);
                }
                $this->data['exportData'][$cnt][$fieldKey] = ($field != "" && isset($entry->$field)) ? $entry->$field : "";

            }

            foreach ($this->data['exportSetting']['settings']['dynamic_fields'] as $field)
            {

                $fieldKey = $field['field_name'];
                if($this->data['generalSettings']['csv_export_key'] == "field_label" && $this->data['exportSetting']['format'] == "csv")
                {
                    $fieldKey = $field['field_label'];
                }

                $fieldID = "field_id_" . $field['field_id'];
                $this->data['exportData'][$cnt][$fieldKey] = $entry->$fieldID;

                switch ($field['field_type'])
                {

                    case 'grid':
                    case 'file_grid':
                        $params = array(
                            'entry_id' => $entry->entry_id,
                            'field_id' => $field['field_id'],
                            'fluid'    => false
                        );
                        $this->data['exportData'][$cnt][$fieldKey] = $this->_parseGrid($params);
                        break;

                    case 'relationship':
                        $params = array(
                            'parent_id' => $entry->entry_id,
                            'field_id'  => $field['field_id'],
                            'fluid'     => false
                        );
                        $this->data['exportData'][$cnt][$fieldKey] = $this->_parseRelationships($params);
                        break;

                    case 'file':
                        if (strpos($this->data['exportData'][$cnt][$fieldKey], '{file:') !== false && preg_match('/{file\:(\d+)\:url}/', $this->data['exportData'][$cnt][$fieldKey], $matches)) {
                            $this->data['exportData'][$cnt][$fieldKey] = $this->_parseFile($matches);
                        } else {
                            $this->data['exportData'][$cnt][$fieldKey] = str_replace($this->data['exportSetting']['settings']['upload_destination_search'], $this->data['exportSetting']['settings']['upload_destination_replace'], $this->data['exportData'][$cnt][$fieldKey]);
                        }
                        break;

                    case 'assets':
                        $params = array(
                            'entry_id'  => $entry->entry_id,
                            'field_id'  => $field['field_id'],
                            'fluid'     => false
                        );
                        $this->data['exportData'][$cnt][$fieldKey] = $this->_parseAssets($params);
                        break;

                    case 'low_events':
                        $params = array(
                            'entry_id'  => $entry->entry_id,
                            'field_id'  => $field['field_id'],
                            'fluid'     => false
                        );
                        $this->data['exportData'][$cnt][$fieldKey] = $this->_parseLowEvents($params);
                        break;

                    case 'fluid_field':
                        $params = array(
                            'entry_id'          => $entry->entry_id,
                            'fluid_field_id'    => $field['field_id'],
                            'fluid'             => true
                        );
                        $this->data['exportData'][$cnt][$fieldKey] = $this->_parseFluidField($params);
                        break;

                    case 'fieldpack_checkboxes':
                    case 'fieldpack_list':
                    case 'fieldpack_multiselect':
                        $this->data['exportData'][$cnt][$fieldKey] = implode("|", explode("\n", $entry->$fieldID));
                        break;

                    case 'tag':
                        $this->data['exportData'][$cnt][$fieldKey] = implode(",", explode("\n", $entry->$fieldID));
                        break;

                    case 'matrix':
                        $params = array(
                            'entry_id' => $entry->entry_id,
                            'field_id' => $field['field_id'],
                            'fluid'    => false
                        );
                        $this->data['exportData'][$cnt][$fieldKey] = $this->_parseMatrix($params);
                        break;

                    case 'playa':
                        $params = array(
                            'parent_entry_id' => $entry->entry_id,
                            'parent_field_id' => $field['field_id'],
                            'parent_row_id'   => "",
                            'parent_col_id'   => "",
                            'fluid'           => false
                        );
                        $this->data['exportData'][$cnt][$fieldKey] = $this->_parsePlaya($params);
                        break;

                    case 'cartthrob_order_items':
                        $params = array(
                            'entry_id' => $entry->entry_id,
                            'field_id' => $field['field_id'],
                        );
                        $this->data['exportData'][$cnt][$fieldKey] = $this->_parseOrderitems($params);

                        break;

                    case 'cartthrob_price_modifiers':
                    case 'cartthrob_price_modifiers_region':
                    case 'cartthrob_price_by_member_group':
                    case 'cartthrob_price_by_region':
                    case 'cartthrob_package':
                    case 'cartthrob_discount':
                    case 'cartthrob_price_modifiers_configurator':
                    case 'cartthrob_price_quantity_thresholds':
                        if($this->data['exportData'][$cnt][$fieldKey] != "")
                        {
                            $this->data['exportData'][$cnt][$fieldKey] = unserialize(base64_decode($this->data['exportData'][$cnt][$fieldKey]));
                        }
                        break;

                    case 'bloqs':
                        $params = array(
                            'entry_id' => $entry->entry_id,
                            'field_id' => $field['field_id'],
                            'fluid'    => false,
                            'bloqs'    => true,
                        );
                        $this->data['exportData'][$cnt][$fieldKey] = $this->_parseBloqs($params);
                        break;

                    case 'channel_files ':
                    case 'channel_images':
                    case 'channel_videos':
                        break;

                    case 'super_address_field':
                        $this->data['exportData'][$cnt][$fieldKey] = @json_decode($this->data['exportData'][$cnt][$fieldKey], true);
                        break;

                }

            }

            if(isset($this->data['exportSetting']['settings']['export_extra']) && is_array($this->data['exportSetting']['settings']['export_extra']))
            {

                if(in_array('category', $this->data['exportSetting']['settings']['export_extra']))
                {
                    foreach ($this->data['exportSetting']['categoryGroups'] as $catKey => $catValue)
                    {
                        $this->data['exportData'][$cnt][$catValue] = $entry->Categories->filter('group_id', $catKey)->pluck('cat_name');
                    }
                }

                if(in_array('pages', $this->data['exportSetting']['settings']['export_extra']) || in_array('structure', $this->data['exportSetting']['settings']['export_extra']))
                {

                    $page_uri       = 'page_uri';
                    $page_template  = 'page_template';
                    if($this->data['generalSettings']['csv_export_key'] == "field_label" && $this->data['exportSetting']['format'] == "csv")
                    {
                        $page_uri       = lang('page_uri');
                        $page_template  = lang('page_template');
                    }

                    $this->data['exportData'][$cnt][$page_uri]         = "";
                    $this->data['exportData'][$cnt][$page_template]   = "";

                    if(isset($this->data['pages']['uris']) && isset($this->data['pages']['uris'][$entry->entry_id]))
                    {
                        $this->data['exportData'][$cnt][$page_uri] = $this->data['pages']['url'] . '/' . ltrim($this->data['pages']['uris'][$entry->entry_id], '/');
                    }

                    if(isset($this->data['pages']['templates']) && isset($this->data['pages']['templates'][$entry->entry_id]))
                    {
                        $this->data['exportData'][$cnt][$page_template] = $this->data['pages']['templates'][$entry->entry_id];
                    }

                    if(in_array('structure', $this->data['exportSetting']['settings']['export_extra']))
                    {
                        $structure_parent_id    = 'structure_parent_id';
                        $structure_listing_cid  = 'structure_listing_cid';
                        $structure_lft          = 'structure_lft';
                        $structure_rgt          = 'structure_rgt';
                        $structure_hidden       = 'structure_hidden';
                        $structure_url_title    = 'structure_url_title';

                        if($this->data['generalSettings']['csv_export_key'] == "field_label" && $this->data['exportSetting']['format'] == "csv")
                        {
                            $structure_parent_id    = lang('structure_parent_id');
                            $structure_listing_cid  = lang('structure_listing_cid');
                            $structure_lft          = lang('structure_lft');
                            $structure_rgt          = lang('structure_rgt');
                            $structure_hidden       = lang('structure_hidden');
                            $structure_url_title    = lang('structure_url_title');
                        }

                        $this->data['exportData'][$cnt][$structure_parent_id]   = "";
                        $this->data['exportData'][$cnt][$structure_listing_cid] = "";
                        $this->data['exportData'][$cnt][$structure_lft]         = "";
                        $this->data['exportData'][$cnt][$structure_rgt]         = "";
                        $this->data['exportData'][$cnt][$structure_hidden]      = "";
                        $this->data['exportData'][$cnt][$structure_url_title]   = "";

                        if(! empty($this->data['structure'][$entry->entry_id]))
                        {
                            $this->data['exportData'][$cnt][$structure_parent_id]   = $this->data['structure'][$entry->entry_id]['structure_parent_id'];
                            $this->data['exportData'][$cnt][$structure_listing_cid] = $this->data['structure'][$entry->entry_id]['structure_listing_cid'];
                            $this->data['exportData'][$cnt][$structure_lft]         = $this->data['structure'][$entry->entry_id]['structure_lft'];
                            $this->data['exportData'][$cnt][$structure_rgt]         = $this->data['structure'][$entry->entry_id]['structure_rgt'];
                            $this->data['exportData'][$cnt][$structure_hidden]      = $this->data['structure'][$entry->entry_id]['structure_hidden'];
                            $this->data['exportData'][$cnt][$structure_url_title]   = $this->data['structure'][$entry->entry_id]['structure_url_title'];
                        }

                    }

                }

                if(in_array('seo_lite', $this->data['exportSetting']['settings']['export_extra']))
                {

                    $seo_lite_title         = "seo_lite_title";
                    $seo_lite_keywords      = "seo_lite_keywords";
                    $seo_lite_description   = "seo_lite_description";

                    if($this->data['generalSettings']['csv_export_key'] == "field_label" && $this->data['exportSetting']['format'] == "csv")
                    {
                        $seo_lite_title         = lang("seo_lite_title");
                        $seo_lite_keywords      = lang("seo_lite_keywords");
                        $seo_lite_description   = lang("seo_lite_description");
                    }

                    $this->data['exportData'][$cnt][$seo_lite_title]        = "";
                    $this->data['exportData'][$cnt][$seo_lite_keywords]     = "";
                    $this->data['exportData'][$cnt][$seo_lite_description]  = "";
                    if(isset($this->data['seo_lite'][$entry->entry_id]))
                    {
                        $this->data['exportData'][$cnt][$seo_lite_title]        = $this->data['seo_lite'][$entry->entry_id]['seo_lite_title'];
                        $this->data['exportData'][$cnt][$seo_lite_keywords]     = $this->data['seo_lite'][$entry->entry_id]['seo_lite_keywords'];
                        $this->data['exportData'][$cnt][$seo_lite_description]  = $this->data['seo_lite'][$entry->entry_id]['seo_lite_description'];
                    }

                }

                if(in_array('transcribe', $this->data['exportSetting']['settings']['export_extra']))
                {

                    $language_id                = "language_id";
                    $language_name              = "language_name";
                    $language_abbreviation      = "language_abbreviation";
                    $language_relationship_id   = "language_relationship_id";
                    $language_entry_id          = "entry_id";

                    if($this->data['generalSettings']['csv_export_key'] == "field_label" && $this->data['exportSetting']['format'] == "csv")
                    {
                        $language_id                = lang("language_id");
                        $language_name              = lang("language_name");
                        $language_abbreviation      = lang("language_abbreviation");
                        $language_relationship_id   = lang("language_relationship_id");
                        $language_entry_id          = lang("language_entry_id");
                    }

                    $this->data['exportData'][$cnt][$language_id]               = "";
                    $this->data['exportData'][$cnt][$language_name]             = "";
                    $this->data['exportData'][$cnt][$language_abbreviation]     = "";
                    $this->data['exportData'][$cnt][$language_relationship_id]  = "";

                    if(isset($this->transcribe['relationship'][$this->data['exportData'][$cnt][$language_entry_id]]))
                    {
                        $this->data['exportData'][$cnt][$language_id]               = $this->transcribe['relationship'][$this->data['exportData'][$cnt][$language_entry_id]]['language_id'];
                        $this->data['exportData'][$cnt][$language_relationship_id]  = $this->transcribe['relationship'][$this->data['exportData'][$cnt][$language_entry_id]]['relationship_id'];
                        $this->data['exportData'][$cnt][$language_name]             = $this->transcribe['languages'][$this->data['exportData'][$cnt][$language_id]]['name'];
                        $this->data['exportData'][$cnt][$language_abbreviation]     = $this->transcribe['languages'][$this->data['exportData'][$cnt][$language_id]]['abbreviation'];
                    }

                }

            }

            $cnt++;

        }

        array_walk_recursive($this->data['exportData'], array($this, "_generalRecursiveActions"));
        switch ($this->data['exportSetting']['format'])
        {

            case 'xml':
                return $this->renderXML();
                break;

            case 'json':
                return $this->renderJSON();
                break;

            case 'csv':
            default:
                return $this->renderCSV();
                break;

        }

    }

    function renderXML()
    {

        if(isset($this->data['generalSettings']['ob_clean']) && $this->data['generalSettings']['ob_clean'] == 1)
        {
            @ob_clean();
        }

        if(isset($this->data['generalSettings']['ob_start']) && $this->data['generalSettings']['ob_start'] == 1)
        {
            @ob_start();
        }

        $this->xmlRoot      = $this->data['generalSettings']['xml_root_name'];
        $this->xmlElement   = $this->data['generalSettings']['xml_element_name'];
        $xml                = "";

        if($this->data['vars']['type'] != "ajax" || ($this->data['vars']['type'] == "ajax" && $this->data['vars']['offset'] == 0))
        {
            $xml .= "<" . $this->xmlRoot . ">" . $this->newline;
        }

        if(is_array($this->data['exportData']) && count($this->data['exportData']))
        {

            foreach ($this->data['exportData'] as $row)
            {

                $xml .= $this->tab . "<" . $this->xmlElement . ">" . $this->newline;
                foreach ($row as $key => $value)
                {

                    if(is_array($value) && count($value))
                    {
                        $xml .= $this->_createArrayXML(2, $key, $value);
                    }
                    else
                    {
                        $value  = is_array($value) ? "" : $value;
                        $xml    .= $this->tab . $this->tab . "<" . $key . "><![CDATA[" . $value . "]]></" . $key . ">" . $this->newline;
                    }

                }
                $xml .= $this->tab . "</" . $this->xmlElement . ">" . $this->newline;

            }

        }

        if($this->data['vars']['type'] != "ajax" || ($this->data['vars']['type'] == "ajax" && (($this->data['vars']['offset'] + $this->data['vars']['limit']) >= $this->data['vars']['total'])))
        {
            $this->_increaseDownloadCounter();
            $xml .= "</" . $this->xmlRoot . ">";
        }

        if($this->data['vars']['type'] == "ajax")
        {
            return $this->_ajaxFileHandler($xml);
        }

        $now = gmdate("D, d M Y H:i:s");
        header('Expires: Wed, 21 Oct 2015 07:28:00 GMT');
        header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
        header("Last-Modified: {$now} GMT");
        header("Content-type: text/xml");
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');
        header('Content-Disposition: attachment;filename=super_export_' .$this->data['exportSetting']['id'] . '.xml');
        header('Content-Transfer-Encoding: binary');
        echo $xml;
        exit(ob_get_clean());

    }

    function renderJSON()
    {

        if(isset($this->data['generalSettings']['ob_clean']) && $this->data['generalSettings']['ob_clean'] == 1)
        {
            @ob_clean();
        }

        if(isset($this->data['generalSettings']['ob_start']) && $this->data['generalSettings']['ob_start'] == 1)
        {
            @ob_start();
        }

        if($this->data['vars']['type'] != "ajax" || ($this->data['vars']['type'] == "ajax" && (($this->data['vars']['offset'] + $this->data['vars']['limit']) >= $this->data['vars']['total'])))
        {
            $this->_increaseDownloadCounter();
        }

        if($this->data['vars']['type'] == "ajax")
        {
            return $this->_ajaxFileHandler($this->data['exportData']);
        }

        $json = json_encode($this->data['exportData']);
        $now = gmdate("D, d M Y H:i:s");
        header('Expires: Wed, 21 Oct 2015 07:28:00 GMT');
        header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
        header("Last-Modified: {$now} GMT");
        header("Content-type: application/json");
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');
        header('Content-Disposition: attachment;filename=super_export_' .$this->data['exportSetting']['id'] . '.json');
        header('Content-Transfer-Encoding: binary');
        echo $json;
        exit(ob_get_clean());

    }

    function renderCSV()
    {

        if(isset($this->data['generalSettings']['ob_clean']) && $this->data['generalSettings']['ob_clean'] == 1)
        {
            @ob_clean();
        }

        if(isset($this->data['generalSettings']['ob_start']) && $this->data['generalSettings']['ob_start'] == 1)
        {
            @ob_start();
        }

        $csv        = '';
        $search     = array('"');
        $replace    = array("\"");

        if(is_array($this->data['exportData']) && count($this->data['exportData']))
        {

            if($this->data['vars']['type'] != "ajax" || ($this->data['vars']['type'] == "ajax" && $this->data['vars']['offset'] == 0))
            {
                foreach (array_keys($this->data['exportData'][0]) as $fieldName)
                {
                    $csv .= $this->_enclose($fieldName);
                }
                $csv .= $this->newline;
            }

            foreach ($this->data['exportData'] as $row)
            {

                foreach ($row as $key => $value)
                {

                    if(is_array($value) && count($value))
                    {

                        if($this->_isMultiDimensionalArray($value) || ! isset($value[0]))
                        {

                            switch ($this->data['generalSettings']['csv_separator_m_array'])
                            {

                                case 'serialize':
                                    $value = serialize($value);
                                    break;

                                case 'json_base64':
                                    $value = base64_encode(json_encode($value));
                                    break;

                                case 'serialize_base64':
                                    $value = base64_encode(serialize($value));
                                    break;

                                case 'json':
                                default:
                                    $value = json_encode($value);
                                    break;

                            }

                        }
                        else
                        {
                            $value = implode($this->data['generalSettings']['csv_separator_s_array'], $value);
                        }

                    }
                    else
                    {
                        $value = is_array($value) ? "" : $value;
                    }

                    $csv .= $this->_enclose(str_replace($search, $replace, $value));

                }

                $csv = rtrim($csv);
                $csv .= $this->newline;

            }

        }
        else
        {

            if(isset($this->data['exportSetting']['settings']['default_fields']) && is_array($this->data['exportSetting']['settings']['default_fields']) && count($this->data['exportSetting']['settings']['default_fields']))
            {

                foreach ($this->data['exportSetting']['settings']['default_fields'] as $field)
                {
                    $fieldKey = $field;
                    if($this->data['generalSettings']['csv_export_key'] == "field_label" && $this->data['exportSetting']['format'] == "csv")
                    {
                        $fieldKey = lang($field);
                    }

                    $csv .= $this->_enclose($fieldKey);

                }

            }

            if(isset($this->data['exportSetting']['settings']['dynamic_fields']) && is_array($this->data['exportSetting']['settings']['dynamic_fields']) && count($this->data['exportSetting']['settings']['dynamic_fields']))
            {

                foreach ($this->data['exportSetting']['settings']['dynamic_fields'] as $field)
                {
                    $fieldKey = $field['field_name'];
                    if($this->data['generalSettings']['csv_export_key'] == "field_label" && $this->data['exportSetting']['format'] == "csv")
                    {
                        $fieldKey = $field['field_label'];
                    }

                    $csv .= $this->_enclose($fieldKey);
                }

            }

        }

        if($this->data['vars']['type'] == "ajax")
        {
            $this->_increaseDownloadCounter();
            return $this->_ajaxFileHandler($csv);
        }

        $now = gmdate("D, d M Y H:i:s");
        header('Expires: Wed, 21 Oct 2015 07:28:00 GMT');
        header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
        header("Last-Modified: {$now} GMT");
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');
        header('Content-Disposition: attachment;filename=super_export_' .$this->data['exportSetting']['id'] . '.csv');
        header('Content-Transfer-Encoding: binary');
        echo $csv;
        exit(ob_get_clean());

    }


    function _parseGrid($params)
    {

        $grid = ee()->superExportModel->gridData($params);
        if(! $grid)
        {
            return false;
        }

        $ret = array();
        $cnt = 0;
        foreach ($grid['data'] as $row)
        {

            $ret[$cnt] = array();
            foreach ($row as $key => $value)
            {

                if(isset($grid['fields'][$key]))
                {

                    $fieldKey = $grid['fields'][$key]['col_name'];
                    if($this->data['generalSettings']['csv_export_key'] == "field_label" && $this->data['exportSetting']['format'] == "csv")
                    {
                        $fieldKey = $grid['fields'][$key]['col_label'];
                    }

                    $ret[$cnt][$fieldKey] = $value;
                    switch ($grid['fields'][$key]['col_type'])
                    {

                        case 'relationship':
                            $subParams = array(
                                'grid_row_id'   => $row['row_id'],
                                'parent_id'     => $row['entry_id'],
                                'grid_col_id'   => $grid['fields'][$key]['col_id'],
                                'grid_field_id' => $grid['fields'][$key]['field_id'],
                                'fluid'         => $params['fluid'],
                                'fluid_field_data_id' => isset($params['fluid_field_data_id']) ? $params['fluid_field_data_id'] : "",
                            );

                            $ret[$cnt][$fieldKey] = $this->_parseRelationships($subParams);
                            // Meta information for debug
                            /*$ret[$cnt][$fieldKey]['param'] = $params;
                            $ret[$cnt][$fieldKey]['subparam'] = $subParams;*/
                            break;

                        case 'file':
                            if (strpos($ret[$cnt][$fieldKey], '{file:') !== false && preg_match('/{file\:(\d+)\:url}/', $ret[$cnt][$fieldKey], $matches)) {
                                $ret[$cnt][$fieldKey] = $this->_parseFile($matches);
                            } else {
                                $ret[$cnt][$fieldKey] = str_replace($this->data['exportSetting']['settings']['upload_destination_search'], $this->data['exportSetting']['settings']['upload_destination_replace'], $ret[$cnt][$fieldKey]);
                            }
                            break;

                        case 'assets':
                            $subParams = array(
                                'entry_id'  => $row['entry_id'],
                                'field_id'  => $grid['fields'][$key]['field_id'],
                                'row_id'    => $row['row_id'],
                                'col_id'    => $grid['fields'][$key]['col_id'],
                                'fluid'     => $params['fluid'],
                            );
                            $ret[$cnt][$fieldKey] = $this->_parseAssets($subParams);
                            break;

                        case 'fieldpack_checkboxes':
                        case 'fieldpack_list':
                        case 'fieldpack_multiselect':
                            $ret[$cnt][$fieldKey] = implode("|", explode("\n", $ret[$cnt][$fieldKey]));
                            break;

                        case 'tag':
                            $ret[$cnt][$fieldKey] = implode(",", explode("\n", $ret[$cnt][$fieldKey]));
                            break;

                        case 'super_address_field':
                            $ret[$cnt][$fieldKey] = @json_decode($ret[$cnt][$fieldKey], true);
                            break;

                    }

                }

            }

            $cnt++;

        }

        return $ret;

    }

    function _parseMatrix($params)
    {

        $grid = ee()->superExportModel->matrixData($params);

        if(! $grid)
        {
            return false;
        }

        $ret = array();
        $cnt = 0;
        foreach ($grid['data'] as $row)
        {

            $ret[$cnt] = array();
            foreach ($row as $key => $value)
            {

                if(isset($grid['fields'][$key]))
                {

                    $fieldKey = $grid['fields'][$key]['col_name'];
                    if($this->data['generalSettings']['csv_export_key'] == "field_label" && $this->data['exportSetting']['format'] == "csv")
                    {
                        $fieldKey = $grid['fields'][$key]['col_label'];
                    }

                    $ret[$cnt][$fieldKey] = $value;
                    switch ($grid['fields'][$key]['col_type'])
                    {

                        case 'assets':
                            $subParams = array(
                                'entry_id'  => $row['entry_id'],
                                'field_id'  => $grid['fields'][$key]['field_id'],
                                'row_id'    => $row['row_id'],
                                'col_id'    => $grid['fields'][$key]['col_id'],
                                'fluid'     => $params['fluid'],
                            );
                            $ret[$cnt][$fieldKey] = $this->_parseAssets($subParams);
                            break;

                        case 'playa':
                            $params = array(
                                'parent_entry_id' => $row['entry_id'],
                                'parent_field_id' => $grid['fields'][$key]['field_id'],
                                'parent_row_id'   => $row['row_id'],
                                'parent_col_id'   => $grid['fields'][$key]['col_id'],
                                'fluid'           => $params['fluid']
                            );
                            $ret[$cnt][$fieldKey] = $this->_parsePlaya($params);
                            break;

                        case 'file':
                            if (strpos($ret[$cnt][$fieldKey], '{file:') !== false && preg_match('/{file\:(\d+)\:url}/', $ret[$cnt][$fieldKey], $matches)) {
                                $ret[$cnt][$fieldKey] = $this->_parseFile($matches);
                            } else {
                                $ret[$cnt][$fieldKey] = str_replace($this->data['exportSetting']['settings']['upload_destination_search'], $this->data['exportSetting']['settings']['upload_destination_replace'], $ret[$cnt][$fieldKey]);
                            }
                            break;

                        case 'fieldpack_checkboxes':
                        case 'fieldpack_list':
                        case 'fieldpack_multiselect':
                            $ret[$cnt][$fieldKey] = implode("|", explode("\n", $ret[$cnt][$fieldKey]));
                            break;

                        case 'tag':
                            $ret[$cnt][$fieldKey] = implode(",", explode("\n", $ret[$cnt][$fieldKey]));
                            break;

                        case 'super_address_field':
                            $ret[$cnt][$fieldKey] = @json_decode($ret[$cnt][$fieldKey], true);
                            break;

                    }

                }

            }

            $cnt++;

        }

        return $ret;

    }

    function _parseRelationships($params)
    {
        $relations = ee()->superExportModel->relationshipsData($params, $this->data['generalSettings']['relationships_key']);
        return $relations;
    }

    function _parsePlaya($params)
    {
        $relations = ee()->superExportModel->playaData($params, $this->data['generalSettings']['relationships_key']);
        return $relations;
    }

    function _parseOrderItems($params)
    {
        $items = ee()->superExportModel->orderItems($params);
        return $items;
    }

    function _parseFile($matches)
    {
        $file_id = $matches[1];
        $file = ee('Model')->get('File', $file_id)->first();

        return $file ? $file->getAbsoluteURL() : '';
    }

    function _parseAssets($params)
    {

        $ret    = array();
        $assets = ee()->superExportModel->assetsData($params);

        if(! empty($assets))
        {
            for ($i = 0; $i < count($assets); $i++)
            {

                if($assets[$i]['filedir_id'] != "" && isset($this->data['exportSetting']['settings']['upload_destinations'][$assets[$i]['filedir_id']]))
                {
                    $ret[] = $this->data['exportSetting']['settings']['upload_destinations'][$assets[$i]['filedir_id']] . $assets[$i]['full_path'] . $assets[$i]['file_name'];
                }
                elseif(isset($assets[$i]['settings']) && $assets[$i]['settings'] != "")
                {
                    $assets[$i]['settings'] = json_decode($assets[$i]['settings'], true);
                    if(isset($assets[$i]['settings']['url_prefix']))
                    {
                        $ret[] = $assets[$i]['settings']['url_prefix'] . $assets[$i]['full_path'] . $assets[$i]['file_name'];
                    }
                }

            }
        }

        return $ret;

    }

    function _parseLowEvents($params)
    {
        $events = ee()->superExportModel->lowEventsData($params);
        return ($events !== false) ? $events : array();
    }

    function _parseFluidField($params)
    {

        $cnt = 0;
        $ret = array();
        $fluidRows = ee('Model')->get('fluid_field:FluidField')
            ->filter('entry_id', $params['entry_id'])
            ->filter('fluid_field_id', $params['fluid_field_id'])
            ->all();

        foreach ($fluidRows as $key => $row)
        {

            $field      = $row->getField();
            $fieldData  = $row->getFieldData();
            $field_name = "field_id_" . $row->field_id;

            $fieldKey = $field->getItem('field_name');
            if($this->data['generalSettings']['csv_export_key'] == "field_label" && $this->data['exportSetting']['format'] == "csv")
            {
                $fieldKey = $field->getItem('field_label');
            }

            /*echo "<pre>";
            print_r($row->getValues());
            print_r($fieldData->getValues());
            print_r($field->getItem('field_id')); // field_id, field_type, field_name, field_label,field_settings
            exit();*/

            $ret[$cnt][$fieldKey]  = $fieldData->$field_name;
            switch ($field->getItem('field_type'))
            {

                case 'grid':
                case 'file_grid':
                    $subParams = array(
                        'entry_id'              => $params['entry_id'],
                        'field_id'              => $field->getItem('field_id'),
                        'fluid_field_data_id'   => $row->id,
                        'fluid'                 => true,
                    );
                    $ret[$cnt][$fieldKey] = $this->_parseGrid($subParams);
                    break;

                case 'relationship':
                    $subParams = array(
                        'parent_id'             => $params['entry_id'],
                        'field_id'              => $field->getItem('field_id'),
                        'fluid_field_data_id'   => $row->id,
                        'fluid'                 => true,
                    );
                    $ret[$cnt][$fieldKey] = $this->_parseRelationships($subParams);
                    break;

                case 'file':
                    if (strpos($ret[$cnt][$fieldKey], '{file:') !== false && preg_match('/{file\:(\d+)\:url}/', $ret[$cnt][$fieldKey], $matches)) {
                        $ret[$cnt][$fieldKey] = $this->_parseFile($matches);
                    } else {
                        $ret[$cnt][$fieldKey] = str_replace($this->data['exportSetting']['settings']['upload_destination_search'], $this->data['exportSetting']['settings']['upload_destination_replace'], $ret[$cnt][$fieldKey]);
                    }
                    break;

                case 'fieldpack_checkboxes':
                case 'fieldpack_list':
                case 'fieldpack_multiselect':
                    $ret[$cnt][$fieldKey] = implode("|", explode("\n", $ret[$cnt][$fieldKey]));
                    break;

                case 'tag':
                    $ret[$cnt][$fieldKey] = implode(",", explode("\n", $ret[$cnt][$fieldKey]));
                    break;

                case 'super_address_field':
                    $ret[$cnt][$fieldKey] = @json_decode($ret[$cnt][$fieldKey], true);
                    break;

            }

            $cnt++;

        }

        return $ret;

    }

    function _parseBloqs($params)
    {

        $cnt     = 0;
        $ret     = array();
        $adapter = new BoldMinded\Bloqs\Database\Adapter(ee());
        $blocks  = $this->_getBlocks($params['entry_id'], $params['field_id']);

        foreach ($blocks as $blockKey => $block)
        {

            $fieldKey = $block->definition->shortname;
            if($this->data['generalSettings']['csv_export_key'] == "field_label" && $this->data['exportSetting']['format'] == "csv")
            {
                $fieldKey = $block->definition->name;
            }

            $ret[$cnt][$fieldKey] = [];
            foreach ($block->atoms as $atomKey => $atom)
            {

                $ret[$cnt][$fieldKey][$atomKey] = $atom->value;
                switch ($atom->definition->type)
                {

                    case 'assets':
                        $subParams = array(
                            'entry_id'  => $params['entry_id'],
                            'field_id'  => $params['field_id'],
                            'value'     => $atom->value,
                            'fluid'     => false,
                            'bloqs'     => true,
                        );
                        $ret[$cnt][$fieldKey][$atomKey] = $this->_parseAssets($subParams);
                        break;

                    case 'relationship':
                        $subParams = array(
                            'field_id'      => $atom->definition->getId(),
                            'grid_col_id'   => $atom->definition->getId(),
                            'grid_row_id'   => $block->getId(),
                            'parent_id'     => $params['entry_id'],
                            'grid_field_id' => $params['field_id'],
                            'fluid'         => false,
                            'bloqs'         => true,
                            'fluid_field_data_id' => "",
                        );
                        $ret[$cnt][$fieldKey][$atomKey] = $this->_parseRelationships($subParams);
                        break;

                    case 'file':
                        if (strpos($ret[$cnt][$fieldKey][$atomKey], '{file:') !== false && preg_match('/{file\:(\d+)\:url}/', $ret[$cnt][$fieldKey][$atomKey], $matches)) {
                            $ret[$cnt][$fieldKey][$atomKey] = $this->_parseFile($matches);
                        } else {
                            $ret[$cnt][$fieldKey][$atomKey] = str_replace($this->data['exportSetting']['settings']['upload_destination_search'], $this->data['exportSetting']['settings']['upload_destination_replace'], $ret[$cnt][$fieldKey][$atomKey]);
                        }
                        break;

                    case 'super_address_field':
                        $ret[$cnt][$fieldKey][$atomKey] = @json_decode($ret[$cnt][$fieldKey][$atomKey], true);
                    break;

                }

            }

            $cnt++;

        }

        return $ret;

    }

    private function _getBlocks($entryId, $fieldId)
    {
        $key = "blocks|fetch|entry_id:$entryId;field_id:$fieldId";
        $blocks = ee()->session->cache(__CLASS__, $key, false);

        if ($blocks) {
            return $blocks;
        }


        $adapter = new BoldMinded\Bloqs\Database\Adapter(ee());
        $blocks = $adapter->getBlocks(
            $entryId,
            $fieldId
        );

        ee()->session->set_cache(__CLASS__, $key, $blocks);

        return $blocks;
    }

    function _isMultiDimensionalArray($arr)
    {
        rsort($arr);
        return (isset($arr[0]) && is_array($arr[0]));
    }

    function _enclose($data)
    {
        return $this->enclosure . str_replace($this->enclosure, $this->enclosure . $this->enclosure, $data) . $this->enclosure . $this->delim;
    }

    function _createArrayXML($tabCount, $key, $value)
    {

        $xml  = "";
        $tabs = "";
        for ($i=0; $i < $tabCount; $i++)
        {
            $tabs = $tabs . $this->tab;
        }

        if (! is_array($value))
        {
            $xml .= $tabs . "<" . $key . "><![CDATA[" . $value . "]]></" . $key . ">" . $this->newline;
        }
        elseif($this->_isMultiDimensionalArray($value))
        {

            $xml .= $tabs . "<" . $key . ">" . $this->newline;
            foreach ($value as $subKey => $subValue)
            {
                if(is_numeric($subKey))
                {
                    $subKey = "data";
                }
                $xml .= $this->_createArrayXML(($tabCount+1), $key . "_" . $subKey, $subValue);
            }
            $xml .= $tabs . "</" . $key . ">" . $this->newline;

        }
        else
        {

            $xml .= $tabs . "<" . $key . ">" . $this->newline;
            foreach ($value as $subKey => $subValue)
            {
                if(is_numeric($subKey))
                {
                    $subKey = "data";
                }
                $xml .= $tabs . $this->tab . "<" . $key . "_" .  $subKey . "><![CDATA[" . $subValue . "]]></" . $key . "_" .  $subKey . ">" . $this->newline;
            }
            $xml .= $tabs . "</" . $key . ">" . $this->newline;

        }

        return $xml;

    }

    function _generalRecursiveActions(&$item, $key)
    {

        if(is_object($item))
        {

            if($item instanceof DateTime)
            {
                $item = $item->getTimestamp();
            }
            else
            {
                $item = (array) $item;
                $item = reset($item);
            }

        }

        if($this->data['generalSettings']['encode'] == "encode")
        {
            $item = $item ? mb_convert_encoding($item,'HTML-ENTITIES','utf-8') : $item;
        }
        elseif($this->data['generalSettings']['encode'] == "decode")
        {
            $item = $item ? html_entity_decode($item) : $item;
        }

        if ($this->data['generalSettings']['date_format'] != "" && is_numeric($item) && strlen($item) === 10)
        {
            $item = date($this->data['generalSettings']['date_format'], $item);
        }

        if ($this->data['generalSettings']['encode_html'] == 1)
        {
            $item = $item ? htmlentities($item) : $item;
        }

    }

    function _ajaxFileHandler($out)
    {

        $exportPath     = rtrim(SYSPATH, "/") . "/user/cache/super_export/";
        if (! is_dir($exportPath))
        {
            mkdir($exportPath, 0777, TRUE);
        }

        $filename = 'super_export_' .$this->data['exportSetting']['id'] . "." . $this->data['exportSetting']['format'];
        if($this->data['vars']['offset'] == 0)
        {
            @unlink($exportPath . $filename);
        }

        $type = $this->data['exportSetting']['format'] == "json" ? (($this->data['vars']['offset'] != 0) ? "rw" : "w") : "a";
        $handle = fopen($exportPath . $filename, $type) or die('Cannot open file:  "' . $exportPath . $filename . '". Make sure cache folder path is correct and cache folder and super_export folder inside cache folder has 777 recursive permission');

        if($this->data['exportSetting']['format'] == "json")
        {

            if($this->data['vars']['offset'] != 0)
            {
                $contents = fread($handle, filesize($exportPath . $filename));
                $contents = @json_decode($contents, true);

                if($contents != "")
                {
                    $out = array_merge($contents, $out);
                    unset($contents);
                }
            }

            $out = json_encode($out);

        }

        fwrite($handle, $out);
        fclose($handle);
        @chmod($exportPath . $filename, 0777);

        $ret = array(
            'status'    => 'pending',
            'offset'    => $this->data['vars']['offset'] + $this->data['vars']['limit'],
            'limit'     => $this->data['vars']['limit'],
            'total'     => $this->data['vars']['total'],
            'format'    => $this->data['exportSetting']['format'],
            'url'       => "",
        );

        if($ret['offset'] >= $ret['total'])
        {

            $actionID = ee('Model')->get('Action')->filter('method', 'super_export_download')->first();
            if($actionID)
            {
                $actionID = $actionID->action_id;
            }

            $ret['offset']  = $ret['total'];
            $ret['status']  = "completed";
            $ret['url']     = ee()->functions->create_url("?ACT=" . $actionID . '&file=' . $filename);
        }
        else
        {
            $query              = $_GET;
            $query['offset']    = $ret['offset'];
            $query['limit']     = $ret['limit'];

            unset($query['D']);
            unset($query['C']);
            unset($query['M']);
            unset($query['S']);
            $query_result       = http_build_query($query);

            if(defined('REQ') && REQ == 'CP')
            {
                $ret['next_batch']  = ee()->exportSettings->createURL('download', array('id' => $this->data['vars']['id'])) ."&". $query_result;
            }
            else
            {
                $ret['next_batch'] = ee()->functions->create_url("?" . $query_result);
            }

        }

        return $this->_dumpJson($ret);

    }

    function _dumpJson($array)
    {
        if(defined('REQ') && REQ == 'CP')
        {
            echo json_encode($array);exit();
        }

        return $array;
    }

    function _increaseDownloadCounter()
    {

        if(! is_numeric($this->data['exportSettingQuery']->counter))
        {
            $this->data['exportSettingQuery']->counter = 0;
        }
        else
        {
            $this->data['exportSettingQuery']->counter = $this->data['exportSettingQuery']->counter + 1;
        }
        $this->data['exportSettingQuery']->last_modified_date = ee()->localize->now;
        $this->data['exportSettingQuery']->save();

    }

    function _sanitize($title)
    {

        $title = strip_tags($title);
        $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
        $title = str_replace('%', '', $title);
        $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);

        if ($this->_seems_utf8($title))
        {

            if (function_exists('mb_strtolower'))
            {
                $title = mb_strtolower($title, 'UTF-8');
            }

            $title = $this->_utf8_uri_encode($title, 200);

        }

        $title = strtolower($title);
        $title = preg_replace('/&.+?;/', '', $title); // kill entities
        $title = str_replace('.', '_', $title);
        $title = str_replace('-', '_', $title);

        $title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
        $title = preg_replace('/\s+/', '_', $title);
        $title = preg_replace('|_+|', '_', $title);

        $title = trim($title, '_');

        return $title;

    }

    function _utf8_uri_encode( $utf8_string, $length = 0 )
    {

        $unicode = '';
        $values = array();
        $num_octets = 1;
        $unicode_length = 0;
        $string_length = strlen( $utf8_string );

        for ($i = 0; $i < $string_length; $i++ )
        {

            $value = ord( $utf8_string[ $i ] );

            if ( $value < 128 )
            {

                if ( $length && ( $unicode_length >= $length ) )
                {
                    break;
                }

                $unicode .= chr($value);
                $unicode_length++;

            }
            else
            {

                if ( count( $values ) == 0 ) $num_octets = ( $value < 224 ) ? 2 : 3;

                $values[] = $value;

                if ( $length && ( $unicode_length + ($num_octets * 3) ) > $length )
                {
                    break;
                }

                if ( count( $values ) == $num_octets )
                {

                    if ($num_octets == 3)
                    {
                        $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]) . '%' . dechex($values[2]);
                        $unicode_length += 9;
                    }
                    else
                    {
                        $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]);
                        $unicode_length += 6;
                    }

                    $values = array();
                    $num_octets = 1;

                }

            }

        }

        return $unicode;

    }

    function _seems_utf8($str)
    {

        $length = strlen($str);

        for ($i=0; $i < $length; $i++)
        {

            $c = ord($str[$i]);

            if ($c < 0x80) $n = 0;
            elseif (($c & 0xE0) == 0xC0) $n=1;
            elseif (($c & 0xF0) == 0xE0) $n=2;
            elseif (($c & 0xF8) == 0xF0) $n=3;
            elseif (($c & 0xFC) == 0xF8) $n=4;
            elseif (($c & 0xFE) == 0xFC) $n=5;
            else return false;

            for ($j=0; $j<$n; $j++)
            {
                if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
                {
                    return false;
                }
            }

        }

        return true;

    }
}