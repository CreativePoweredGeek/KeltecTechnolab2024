<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\Table;

class Super_export_settings_lib
{

    public $included = FALSE;
    public $site_id  = "";
    public $perPage  = 25;
    public function __construct()
    {

        $this->site_id = ee()->config->item("site_id");
        ee()->load->model('Super_export_model', 'superExportModel');
        ee()->lang->loadfile('super_export');

    }

    function settingsForm($vars)
    {

        $exportSettings = $this->getExportSettings($this->site_id);
        if(isset($_POST) && count($_POST))
        {
            $exportSettings->set($_POST);
        }

        $vars['sections'] = array(
            array(
                array(
                    'title'  => lang('relationships_key_title'),
                    'desc'   => lang('relationships_key_desc'),
                    'fields' => array(
                        'id' => array(
                            'type'      => 'hidden',
                            'value'     => $exportSettings->id,
                        ),
                        'relationships_key' => array(
                            'type'      => 'radio',
                            'required'  => true,
                            'value'     => $exportSettings->relationships_key,
                            'choices'   => array(
                                'entry_id'  => lang('entry_id'),
                                'title'     => lang('title'),
                                'url_title' => lang('url_title'),
                            ),
                        )
                    )
                ),

                array(
                    'title' => lang('encode_title'),
                    'desc'  => lang('encode_desc'),
                    'fields' => array(
                        'encode' => array(
                            'type'      => 'radio',
                            'required'  => true,
                            'value'     => $exportSettings->encode,
                            'choices'   => array(
                                'no'        => lang('no_encode_decode'),
                                'encode'    => lang('encode_utf8'),
                                'decode'    => lang('decode_utf8'),
                            ),
                        )
                    )
                ),

                array(
                    'title' => lang('date_format_title'),
                    'desc'  => lang('date_format_desc'),
                    'fields' => array(
                        'date_format' => array(
                            'type'          => 'text',
                            'placeholder'   => "Y-m-d H:i:s",
                            // 'required'      => true,
                            'value'         => $exportSettings->date_format,
                        )
                    )
                ),

                array(
                    'title' => lang('encode_html_title'),
                    'desc'  => lang('encode_html_desc'),
                    'fields' => array(
                        'encode_html' => array(
                            'type'      => 'toggle',
                            'required'  => true,
                            'value'     => $exportSettings->encode_html,
                        )
                    )
                ),

                array(
                    'title' => lang('ob_clean_title'),
                    'desc'  => lang('ob_clean_desc'),
                    'fields' => array(
                        'ob_clean' => array(
                            'type'      => 'toggle',
                            'required'  => false,
                            'value'     => $exportSettings->ob_clean,
                        )
                    )
                ),

                array(
                    'title' => lang('ob_start_title'),
                    'desc'  => lang('ob_start_desc'),
                    'fields' => array(
                        'ob_start' => array(
                            'type'      => 'toggle',
                            'required'  => false,
                            'value'     => $exportSettings->ob_start,
                        )
                    )
                ),
            ),

            'csv_settings' => array(
                array(
                    'title' => lang('csv_export_key_title'),
                    'desc'  => lang('csv_export_key_desc'),
                    'fields' => array(
                        'csv_export_key' => array(
                            'type'      => 'radio',
                            'required'  => true,
                            'choices'   => array(
                                'field_name'  => lang('field_name'),
                                'field_label' => lang('field_label'),
                            ),
                            'value'     => $exportSettings->csv_export_key,
                        )
                    )
                ),

                array(
                    'title' => lang('csv_separator_s_array_title'),
                    'desc'  => lang('csv_separator_s_array_desc'),
                    'fields' => array(
                        'csv_separator_s_array' => array(
                            'type'      => 'text',
                            'required'  => true,
                            'value'     => $exportSettings->csv_separator_s_array,
                        )
                    )
                ),

                array(
                    'title' => lang('csv_separator_m_array_title'),
                    'desc'  => lang('csv_separator_m_array_desc'),
                    'fields' => array(
                        'csv_separator_m_array' => array(
                            'type'      => 'radio',
                            'required'  => true,
                            'value'     => $exportSettings->csv_separator_m_array,
                            'choices'   => array(
                                'json'              => lang('json'),
                                'serialize'         => lang('serialize'),
                                'json_base64'       => lang('json_base64'),
                                'serialize_base64'  => lang('serialize_base64'),
                            )
                        )
                    )
                ),
            ),

            'xml_settings' => array(
                array(
                    'title' => lang('xml_root_name_title'),
                    'desc'  => lang('xml_root_name_desc'),
                    'fields' => array(
                        'xml_root_name' => array(
                            'type'      => 'text',
                            'required'  => true,
                            'value'     => $exportSettings->xml_root_name,
                        )
                    )
                ),

                array(
                    'title' => lang('xml_element_name_title'),
                    'desc'  => lang('xml_element_name_desc'),
                    'fields' => array(
                        'xml_element_name' => array(
                            'type'      => 'text',
                            'required'  => true,
                            'value'     => $exportSettings->xml_element_name,
                        )
                    )
                ),
            )
        );

        $vars += array(
            'base_url'              => ee('CP/URL', 'addons/settings/super_export/settings'),
            'cp_page_title'         => lang('general_settings'),
            'save_btn_text'         => lang('save'),
            'save_btn_text_working' => lang('saving')
        );

        return $vars;

    }

    function settingsFormPost()
    {

        $values = array();
        foreach ($_POST as $key => $value)
        {
            $values[$key] = ee()->input->post($key, true);
        }

        $exportSettings = ee('Model')->get('super_export:ExportSettings', $values['id'])->first();
        $exportSettings->set($values);

        $result = $exportSettings->validate();
        if (! $result->isValid())
        {
            return $result;
        }

        $exportSettings->save();
        return TRUE;

    }

    function entryList($vars)
    {

        $table = ee('CP/Table', array(
            'sortable'  => true,
            'reorder'   => false
        ));

        $table->setColumns(
            array(
                'id' => array(
                    'type'  => Table::COL_ID
                ),
                'title' => array(
                    'encode' => FALSE
                ),
                'created_date' => array(
                    'encode' => FALSE
                ),
                'last_modified_date' => array(
                    'encode' => FALSE
                ),
                'counter' => array(
                    'encode' => FALSE
                ),
                'format' => array(
                    'encode' => FALSE
                ),
                'manage' => array(
                    'type'  => Table::COL_TOOLBAR
                ),
                array(
                    'type'  => Table::COL_CHECKBOX
                )
            )
        );
        $table->setNoResultsText(sprintf(lang('no_found'), lang('exports')), 'create_new', $this->createURL('entry'));

        $sort_col       = ee()->input->get('sort_col', true) ? ee()->input->get('sort_col', true) : "id";
        $sort_dir       = ee()->input->get('sort_dir', true) ? ee()->input->get('sort_dir', true) : "asc";
        $exportData     = ee('Model')->get('super_export:ExportData')->filter('site_id', $this->site_id);
        $total          = $exportData->count();
        $currentpage    = ((int) ee()->input->get('page')) ?: 1;
        $offset         = ($currentpage - 1) * $this->perPage;
        $data           = array();
        $exportList     = $exportData->limit($this->perPage)->offset($offset)->order($sort_col, $sort_dir)->all();

        $base_url = $this->createURL();
        if(isset($_GET['search']))
        {
            $base_url->setQueryStringVariable('search', $_GET['search']);
        }
        if(isset($_GET['sort_col']))
        {
            $base_url->setQueryStringVariable('sort_col', $_GET['sort_col']);
        }
        if(isset($_GET['sort_dir']))
        {
            $base_url->setQueryStringVariable('sort_dir', $_GET['sort_dir']);
        }

        $vars['pagination'] = ee('CP/Pagination', $total)
            ->perPage($this->perPage)
            ->currentPage($currentpage)
            ->render($base_url);

        $actionID = ee('Model')->get('Action')->filter('method', 'super_export_frontend')->first();
        if($actionID)
        {
            $actionID = $actionID->action_id;
        }

        foreach ($exportList as $expData)
        {

            $expData->settings = json_decode(base64_decode($expData->settings), true);
            $columns = array(
                'id'                    => $expData->id,
                'title'                 => $expData->title,
                'created_date'          => ee()->localize->human_time($expData->created_date),
                'last_modified_date'    => ee()->localize->human_time($expData->last_modified_date),
                'counter'               => $expData->counter,
                'format'                => $expData->format,
                array('toolbar_items' => array(
                    'edit' => array(
                        'href'      => $this->createURL('entry', array('id' => $expData->id)),
                        'title'     => strtolower(lang('edit'))
                    ),
                    'download' => array(
                        'href'      => $this->createURL('download', array('id' => $expData->id)),
                        'title'     => strtolower(lang('download')),
                        'class'     => "super_export_download" . ($expData->settings['enable_ajax_export'] ? " ajax" : "")
                    ),
                    'rte-link' => array(
                        'href'     => 'javascript:void(0);',
                        'title'     => strtolower(lang('url')),
                        'class'     => 'passkey',
                        'copy-url' => ee()->functions->create_url("?ACT=" . $actionID . AMP . 'id=' . $expData->id . ($expData->settings['enable_ajax_export'] ? AMP . "type=ajax" : "")),
                    ),
                )),
                array(
                    'name'  => 'selection[]',
                    'value' => $expData->id,
                    'data'  => array(
                        'confirm' => lang('export') . ': <b>' . htmlentities($expData->title, ENT_QUOTES, 'UTF-8') . '</b>'
                    )
                )
            );

            $attrs = array();
            if (ee()->session->flashdata('return') == $expData->id)
            {
                $attrs = array('class' => 'selected');
            }

            $data[] = array(
                'attrs' => $attrs,
                'columns' => $columns
            );

        }
        unset($exportList);
        $table->setData($data);
        $vars['table'] = $table->viewData($this->createURL());
        $vars['popup'] = array(
            array(
                'id'        => "copy-clipboard",
                'title'     => "Copy the link to export data without logged in.",
                'content'   => '<div class="copy-clipboard"></div>',
                'btn_label' => "Copy to Clipboard",
                'btn_class' => 'copy_to_clipboard_btn',
            ),
            array(
                'id'        => "ajax-export",
                'title'     => "Export is in process. Please wait till we build the export.",
                'content'   => '<div class="export-wrapper"><strong class="export-percent">0%</strong> completed. Please do not close or refresh the page. <svg class="spinner" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid"><g transform="rotate(0 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.9166666666666666s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(30 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.8333333333333334s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(60 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.75s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(90 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.6666666666666666s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(120 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.5833333333333334s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(150 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.5s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(180 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.4166666666666667s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(210 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.3333333333333333s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(240 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.25s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(270 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.16666666666666666s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(300 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.08333333333333333s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(330 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="0s" repeatCount="indefinite"></animate></rect></g></svg></div>',
                'link_label'=> "Download Export",
                'link_class'=> 'hidden download_export',
                'link_icon' => 'icon--export',
            ),
        );

        $vars += array(
            'form_url'          => $this->createURL('delete'),
            'new_url'           => $this->createURL('entry'),
            'cp_heading'        => lang('super_export_list'),
            'cp_heading_desc'   => "",
        );
        ee()->cp->set_right_nav(array(lang('export_form_title')  => $vars['new_url']));

        ee()->javascript->set_global('lang.remove_confirm', lang('export_list') . ': <b>### ' . lang('export_list') . '</b>');
        ee()->cp->add_js_script('file', 'cp/confirm_remove');
        return $vars;

    }

    function entryData($vars)
    {

        ee()->lang->loadfile('fieldtypes');

        if($vars['id'] == "")
        {
            $exportData = ee('Model')->make('super_export:ExportData');
        }
        else
        {
            $exportData = ee('Model')->get('super_export:ExportData', $vars['id'])->first();
        }

        if(isset($_POST) && count($_POST))
        {
            $exportData->set($_POST);
        }
        else
        {
            $exportData->settings = ($exportData->settings == "") ? array() : json_decode(base64_decode($exportData->settings), true);
        }

        $channels = ee('Model')->get('Channel')
                ->fields('channel_id', 'channel_title')
                ->filter('site_id', $this->site_id)
                ->all()->getDictionary('channel_id', 'channel_title');

        $channelData = false;
        if($exportData->channel_id != "")
        {
            $channelData = ee('Model')->get('Channel', $exportData->channel_id)
                // ->with('FieldGroups', 'CustomFields')
                ->first();
        }

        $export_extra = array(
            'category' => lang('category')
        );

        $modules = ee('Model')->get('Module')
            ->filter('module_name', "IN", array("Seo_lite", "Pages", "Structure", "Transcribe"))
            ->all();

        foreach ($modules as $module)
        {
            $export_extra[strtolower($module->module_name)] = $module->module_name;
        }

        $languages = false;
        $transcribeLanguages = [];
        $transcribeDefaultVal = [];
        if(isset($export_extra['transcribe']))
        {
            $languages = ee()->superExportModel->getTranscribeLanguages();

            if(! $languages)
            {
                unset($export_extra['transcribe']);
            }
            else
            {
                foreach ($languages as $key => $value)
                {
                    $transcribeLanguages[$value['id']] = $value['name'] . " (" . $value['abbreviation'] . ")";
                    // $transcribeDefaultVal[] = $value['id'];
                }
            }
        }

        $filters = [];
        $filters[] = [
            'title'  => lang('status_title'),
            'desc'   => lang('status_desc'),
            'fields' => array(
                'settings[status]' => array(
                    'type'      => 'html',
                    'content'   => $this->_renderStatuses($exportData->channel_id, (isset($exportData->settings['status']) ? $exportData->settings['status'] : array()), $channelData),
                    'class'     => "super_status"
                )
            )
        ];

        if($languages)
        {
            $filters[] = [
                'title'  => lang('transcribe_language_title'),
                'desc'   => lang('transcribe_language_desc'),
                'fields' => array(
                    'settings[transcribe_language]' => array(
                        'type'      => 'checkbox',
                        'choices'   => $transcribeLanguages,
                        'value'     => (isset($exportData->settings['transcribe_language'])) ? $exportData->settings['transcribe_language'] : $transcribeDefaultVal,
                    )
                )
            ];
        }

        $filters[] = [
            'title'  => lang('from_title'),
            'desc'   => lang('from_desc'),
            'fields' => array(
                'settings[from]' => array(
                    'type'      => 'text',
                    'value'     => (isset($exportData->settings['from']) && $exportData->settings['from'] != "") ? ee()->localize->human_time($exportData->settings['from']) : "",
                    'class'     => "super_from",
                    'attrs'     => "rel='date-picker'"
                )
            )
        ];

        $filters[] = [
            'title'  => lang('to_title'),
            'desc'   => lang('to_desc'),
            'fields' => array(
                'settings[to]' => array(
                    'type'      => 'text',
                    'value'     => (isset($exportData->settings['to']) && $exportData->settings['to'] != "") ? ee()->localize->human_time($exportData->settings['to']) : "",
                    'class'     => "super_to",
                    'attrs'     => "rel='date-picker'"
                )
            )
        ];

        $vars['sections'] = array(
            array(
                array(
                    'title'  => lang('channel_id_title'),
                    'desc'   => lang('channel_id_desc'),
                    'fields' => array(
                        'id' => array(
                            'type'      => 'hidden',
                            'value'     => $exportData->id,
                        ),
                        'channel_id' => array(
                            'type'      => 'dropdown',
                            'value'     => $exportData->channel_id,
                            'choices'   => $channels,
                            'required'  => true,
                            'class'     => "super_channel_id"
                        )
                    )
                ),

                array(
                    'title'  => lang('default_fields_title'),
                    'desc'   => lang('default_fields_desc'),
                    'fields' => array(
                        'settings[default_fields]' => array(
                            'type'      => 'html',
                            'content'   => $this->_renderStaticField((isset($exportData->settings['default_fields']) ? $exportData->settings['default_fields'] : array()), $channelData),
                            'class'     => 'default_fields'
                        )
                    )
                ),

                array(
                    'title'  => lang('dynamic_fields_title'),
                    'desc'   => lang('dynamic_fields_desc'),
                    'fields' => array(
                        'settings[dynamic_fields]' => array(
                            'type'      => 'html',
                            'content'   => $this->_renderDynamicField($exportData->channel_id, (isset($exportData->settings['dynamic_fields']) ? $exportData->settings['dynamic_fields'] : array()), $channelData),
                            'class'     => 'dynamic_fields'
                        )
                    )
                ),

                array(
                    'title'  => lang('export_extra_title'),
                    'desc'   => lang('export_extra_desc'),
                    'fields' => array(
                        'settings[export_extra]' => array(
                            'type'      => 'checkbox',
                            'choices'   => $export_extra,
                            'value'     => (isset($exportData->settings['export_extra'])) ? $exportData->settings['export_extra'] : "",
                        )
                    )
                ),

            ),

            'filters' => $filters,

            'general_settings' => array(
                array(
                    'title'  => lang('title_title'),
                    'desc'   => lang('title_desc'),
                    'fields' => array(
                        'title' => array(
                            'type'      => 'text',
                            'value'     => $exportData->title,
                            'required'  => true,
                        )
                    )
                ),

                array(
                    'title'  => lang('format_title'),
                    'desc'   => lang('format_desc'),
                    'fields' => array(
                        'format' => array(
                            'type'      => 'dropdown',
                            'value'     => $exportData->format,
                            'required'  => true,
                            'choices'   => array(
                                'csv'       => "csv",
                                'xml'       => "xml",
                                'json'      => "json",
                            )
                        )
                    )
                ),

                array(
                    'title'  => lang('enable_ajax_export_title'),
                    'desc'   => lang('enable_ajax_export_desc'),
                    'fields' => array(
                        'settings[enable_ajax_export]' => array(
                            'type'      => 'toggle',
                            'value'     => (isset($exportData->settings['enable_ajax_export'])) ? $exportData->settings['enable_ajax_export'] : 0,
                            'group_toggle' => array(
                                '1'  => 'batch'
                            )
                        )
                    )
                ),
                array(
                    'group' => 'batch',
                    'title'  => lang('batch_title'),
                    'desc'   => lang('batch_desc'),
                    'fields' => array(
                        'settings[batch]' => array(
                            'type'      => 'text',
                            'value'     => (isset($exportData->settings['batch'])) ? $exportData->settings['batch'] : 50,
                        )
                    )
                ),
            ),
        );

        $vars += array(
            'base_url'              => ee('CP/URL', 'addons/settings/super_export/entry/' . $exportData->id),
            'cp_page_title'         => ($exportData->id == "") ? lang('create_new_export') : lang('modify_export_settings'),
            'save_btn_text'         => lang('save'),
            'save_btn_text_working' => lang('saving')
        );

        $this->_dateEnvironment();
        ee()->cp->add_to_foot("
            <script type='text/javascript'>
                var superExportURL = '". $this->createURL('render_dynamic_channel_fields') ."';
            </script>"
        );

        ee()->javascript->set_global([
            'relationship.publishCreateUrl' => ee('CP/URL')->make('publish/create/###')->compile(),
            'relationship.lang.creatingNew' => lang('creating_new_in_rel'),
            'relationship.lang.relateEntry' => lang('add_fields'),
            'relationship.lang.search'      => lang('search'),
            'relationship.lang.channel'     => lang('channel'),
            'relationship.lang.remove'      => lang('remove'),
        ]);

        ee()->cp->add_js_script([
            'plugin' => ['ui.touch.punch', 'ee_interact.event'],
            'file' => [
                'cp/form_group',
                'fields/relationship/mutable_relationship',
                'fields/relationship/relationship',
                'vendor/react/react.min',
                'vendor/react/react-dom.min',
                'components/relationship',
                'components/dropdown_button',
                'components/select_list'
            ],
            'ui' => 'sortable'
        ]);

        return $vars;

    }

    function entryDataPost()
    {
        $values = array();
        foreach ($_POST as $key => $value)
        {
            $values[$key] = ee()->input->post($key, true);
        }

        if($values['id'] == "")
        {
            $exportData = ee('Model')->make('super_export:ExportData');
        }
        else
        {
            $exportData = ee('Model')->get('super_export:ExportData', $values['id'])->first();
        }

        if(isset($values['settings']['transcribe_language']) && is_array($values['settings']['transcribe_language']) && $values['settings']['transcribe_language'][0] == "")
        {
            unset($values['settings']['transcribe_language'][0]);
            $values['settings']['transcribe_language'] = array_values($values['settings']['transcribe_language']);
        }

        $values['settings'] = base64_encode(json_encode($values['settings']));
        $exportData->set($values);
        $result = $exportData->validate();
        if (! $result->isValid())
        {
            return $result;
        }

        if(! $exportData->id)
        {
            $exportData->site_id            = $this->site_id;
            $exportData->member_id          = ee()->session->userdata('member_id');
            $exportData->created_date       = ee()->localize->now;
            $exportData->counter            = 0;
            $exportData->token              = strtolower(ee()->functions->random('md5',10));
        }

        $exportData->last_modified_date = ee()->localize->now;
        $exportData->save();

        return TRUE;

    }

    function _renderStaticField($choices = array())
    {

        $fields     = array();
        $temp       = ee()->db->list_fields('channel_titles');
        $selected   = array();

        foreach ($temp as $value)
        {

            // $fields[$value] = lang($value);
            $fields[] = [
                'value' => $value,
                'label' => lang($value),
                'instructions' => "",
                'channel_id' => ""
            ];

            if(in_array($value, $choices))
            {
                $selected[] = [
                    'value' => $value,
                    'label' => lang($value),
                    'instructions' => "",
                    'channel_id' => ""
                ];
            }

        }

        // relationship:publish
        // super_export:relationships
        return ee('View')->make('super_export:relationships')->render([
            'field_name'       => 'settings[default_fields]',
            'choices'          => $fields,
            'selected'         => $selected,
            'multi'            => true,
            'filter_url'       => "",
            'limit'            => 9999,
            'no_results'       => ['text' => lang('no_default_fields_found')],
            'no_related'       => ['text' => lang('no_default_fields_related')],
            'select_filters'   => [],
            'channels'         => [],
            'in_modal'         => true,
            'deferred'         => false,
            'rel_min'          => 0,
            'rel_max'          => '',
            'display_entry_id' => false,
        ]);

    }

    function _renderDynamicField($channel_id = "", $choices = array(), $channel = false)
    {

        $fields     = array();
        $selected   = array();

        if($channel_id == "")
        {
            $no_results = 'select_channel_to_render_fields';
        }
        else
        {

            $no_results = 'no_dynamic_fields_found';
            if(! $channel)
            {
                $channel = ee('Model')->get('Channel', $channel_id)
                    // ->with('FieldGroups', 'CustomFields')
                    ->first();
            }

            foreach ($channel->getAllCustomFields() as $field)
            {
                // if ( ! $field->legacy_field_data)
                // {
                    /*$fields[$field->field_id] = $field->field_label . " (" . $field->field_type . ")";*/
                    $fields[] = [
                        'value'         => $field->field_id,
                        'label'         => $field->field_label,
                        'instructions'  => $field->field_type,
                        'channel_id' => ""
                    ];

                    if(in_array($field->field_id, $choices))
                    {
                        $selected[] = [
                            'value' => $field->field_id,
                            'label' => $field->field_label,
                            'instructions' => "",
                            'channel_id' => ""
                        ];
                    }
                // }
            }

        }

        return ee('View')->make('super_export:relationships')->render([
            'field_name'       => 'settings[dynamic_fields]',
            'choices'          => $fields,
            'selected'         => $selected,
            'multi'            => true,
            'filter_url'       => "",
            'limit'            => 9999,
            'no_results'       => ['text' => lang($no_results)],
            'no_related'       => ['text' => lang('no_dynamic_fields_related')],
            'select_filters'   => [],
            'channels'         => [],
            'in_modal'         => true,
            'deferred'         => false,
            'rel_min'          => 0,
            'rel_max'          => '',
            'display_entry_id' => false,
        ]);

    }

    function _renderStatuses($channel_id = "", $selected = array(), $channel = false)
    {

        $fields = array();
        if($channel_id == "")
        {
            $no_results = 'select_channel_to_render_fields';
        }
        else
        {

            $no_results = 'no_statuses_found';
            if(! $channel)
            {
                $channel = ee('Model')->get('Channel', $channel_id)
                    // ->with('FieldGroups', 'CustomFields')
                    ->first();
            }

            $fields = $channel->Statuses->getDictionary('status_id', 'status');

        }

        return ee('View')->make('ee:_shared/form/fields/select')->render([
            'field_name'    => 'settings[status]',
            'choices'       => $fields,
            'value'         => $selected,
            'no_results'    => ['text' => lang($no_results)],
            'multi'         => true
        ]);

    }

    function createURL($functionName = "index", $params = array())
    {

        $temp = "";
        if(count($params) > 0)
        {

            $temp = "/";
            foreach ($params as $key => $value)
            {
                $temp .= $value . "/";
            }
            rtrim($temp, "/");

        }

        return ee('CP/URL')->make('addons/settings/super_export/' . $functionName . $temp);

    }

    function _dateEnvironment()
    {

        ee()->javascript->set_global('date.date_format', ee()->localize->get_date_format());
        ee()->javascript->set_global('lang.date.months.full', array(
            lang('january'),
            lang('february'),
            lang('march'),
            lang('april'),
            lang('may'),
            lang('june'),
            lang('july'),
            lang('august'),
            lang('september'),
            lang('october'),
            lang('november'),
            lang('december')
        ));

        ee()->javascript->set_global('lang.date.months.abbreviated', array(
            lang('jan'),
            lang('feb'),
            lang('mar'),
            lang('apr'),
            lang('may'),
            lang('june'),
            lang('july'),
            lang('aug'),
            lang('sept'),
            lang('oct'),
            lang('nov'),
            lang('dec')
        ));

        ee()->javascript->set_global('lang.date.days', array(
            lang('su'),
            lang('mo'),
            lang('tu'),
            lang('we'),
            lang('th'),
            lang('fr'),
            lang('sa'),
        ));

        ee()->cp->add_js_script(array(
            'file' => array('cp/date_picker'),
        ));

    }

    function getExportSettings($site_id = "")
    {

        if($site_id == "")
        {
            $site_id = $this->site_id;
        }

        $settings = ee('Model')->get('super_export:ExportSettings')->filter('site_id', $site_id)->first();
        if(! $settings)
        {

            $settings = ee('Model')->make('super_export:ExportSettings');
            $settings->set(
                array(
                    'site_id'               => $site_id,
                    'relationships_key'     => 'title',
                    'encode'                => 'decode',
                    'date_format'           => 'Y-m-d H:i:s',
                    'encode_html'           => '0',
                    'csv_export_key'        => 'field_name',
                    'csv_separator_s_array' => ',',
                    'csv_separator_m_array' => 'json',
                    'xml_root_name'         => 'root',
                    'xml_element_name'      => 'element',
                )
            );

            $settings->save();

        }

        return $settings;

    }

}