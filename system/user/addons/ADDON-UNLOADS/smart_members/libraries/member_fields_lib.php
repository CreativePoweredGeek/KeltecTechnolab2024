<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Service\Filter\FilterFactory as FilterFactory; // ee5
use ExpressionEngine\Service\Filter\FilterFactory as FilterFactoryEE6; //ee6

class Member_fields_lib
{

    /* Important globel variables */ 
    public $member_id;
    public $group_id;
    public $fields;
    public $form_errors;

    public $base_url;
    public $perpage;
    public $params;
    public $page;
    public $offset;

	public function __construct()
    {

        /*Setup instance to this class*/
        /*ee()->mf =& $this;*/

        /* Neeful Library classes */
        // ee()->load->library(array('form_validation','custom_validation'));

        /* Neeful Model classes */
        if(! class_exists('member_fields_model'))
        {
           ee()->load->model('member_fields_model', 'mfModel');
        }

        /*Logged in member ID and group ID*/
        $this->member_id    = ee()->session->userdata('member_id');
        $this->group_id     = ee()->session->userdata('group_id'); // ee6 : no need to change

        /*All possible fields of member form*/
        $this->fields       = $this->setArray($this->field_initialize());

    }

    function handleMemberFieldsList($vars, $perPage)
    {

        ee()->lang->loadfile('member');
        $sort_col = ee()->input->get('sort_col');
        switch ($sort_col)
        {
            case 'id':
                $sort_col = 'm_field_id';
                break;

            case 'label':
                $sort_col = 'm_field_label';
                break;

            case 'snort_name':
                $sort_col = 'm_field_name';
                break;

            case 'type':
                $sort_col = 'm_field_type';
                break;

            default:
                $sort_col = 'm_field_order';
        }

        $sort_dir = ee()->input->get('sort_dir') ?: 'asc';

        $table = ee('CP/Table', array(
            'sort_col'  => $sort_col,
            'sort_dir'  => $sort_dir,
            'sortable'  => FALSE,
            'reorder'   => TRUE,
            'save'      => ee('CP/URL')->make("members/fields/order")
        ));

        $table->setColumns(
            array(
                'id' => array(
                    'encode' => FALSE
                ),
                'label',
                'short_name' => array(
                    'encode' => FALSE
                ),
                'type',
                'manage' => array(
                    'type'  => Table::COL_TOOLBAR
                ),
                array(
                    'type'  => Table::COL_CHECKBOX
                )
            )
        );

        $table->setNoResultsText(
            sprintf(lang('no_found'), lang('custom_member_fields')),
            'add_new',
            ee()->sm->url('create_member_field')
        );

        /*Default Settings*/
        $total          = ee('Model')->get('smart_members:SmMemberField')->count();
        $this->base_url = ee()->sm->url('member_fields');
        $showAll = sprintf(lang('show_all_member_fields'), $total);
        
        if(version_compare(APP_VER, '4.0.0', '>='))
        {
            $filter = ee('CP/Filter')
                ->add('Keyword')
                ->add('Perpage', $total, $showAll);

            //ee6    
            if(SM_APP_VER_L6){
                $vars['filters'] = $this->renderFilters($filter);
            }else{
                $vars['filters'] = $this->renderFiltersEE6($filter);                
            }    
        }

        $this->perpage  = ((int) ee()->input->get('perpage')) ?: $perPage;
        $this->page     = ((int) ee()->input->get('page')) ?: 1;
        $this->offset   = ($this->page - 1) * $this->perpage; // Offset is 0 indexed

        $memberFields = ee('Model')->get('smart_members:SmMemberField')
            ->order($sort_col, $sort_dir)
            ->limit($this->perpage)
            ->offset($this->offset);
        
        if (isset($this->params['filter_by_keyword']))
        {
            $memberFields->search(['m_field_name', 'm_field_label'], $this->params['filter_by_keyword']);
        }

        $type_map = array(
            'text'          => lang('text_input'),
            'textarea'      => lang('textarea'),
            'select'        => lang('select_dropdown'),
            'radio'         => lang('radio'),
            'checkboxes'    => lang('checkboxes'),
            'file'          => lang('file'),
            'url'           => lang('mbr_url'),
            'date'          => lang('date'),
            'multi_select'  => lang('multi_select'),
        );

        $fieldData  = array();
        
        foreach ($memberFields->all() as $field)
        {
            
            $fieldType = $field->m_field_type;
            
            $smSettings = @unserialize($field->m_sm_settings);
            if(isset($smSettings['field_type']) && $smSettings['field_type'] != "")
            {
                $fieldType = $smSettings['field_type'];
            }

            $columns = array(
                'id' => $field->getId().form_hidden('order[]', $field->getId()),
                'm_field_label' => array(
                    'content' => $field->m_field_label,
                    'href' => ee()->sm->url('create_member_field', array('id' => $field->m_field_id))
                    ),
                'm_field_name' => "<var>{{$field->m_field_name}}</var>",
                'm_field_type' => $type_map[$fieldType],
                array(
                    'toolbar_items' => array(
                    'edit' => array(
                        'href' => ee()->sm->url('create_member_field', array('id' => $field->m_field_id)),
                        'title' => strtolower(lang('edit'))
                    )
                )),
                array(
                    'name' => 'selection[]',
                    'value' => $field->m_field_id,
                    'data'  => array(
                        'confirm' => lang('field') . ': <b>' . htmlentities($field->m_field_name, ENT_QUOTES, 'UTF-8') . '</b>'
                    )
                )
            );

            $attrs = array();
            if (ee()->session->flashdata('field_id') == $field->getId())
            {
                $attrs = array('class' => 'selected');
            }

            $fieldData[] = array(
                'attrs' => $attrs,
                'columns' => $columns
            );
        }

        $table->setData($fieldData);
        $vars['table'] = $table->viewData(ee()->sm->url('member_fields'));

        if ( ! empty($vars['table']['data']))
        {
            $vars['pagination'] = ee('CP/Pagination', $total)
                ->perPage($this->perpage)
                ->currentPage($this->page)
                ->render($this->base_url);
        }

        ee()->javascript->set_global('lang.remove_confirm', lang('member_fields') . ': <b>### ' . lang('member_fields') . '</b>');
        ee()->cp->add_js_script('file', 'cp/confirm_remove');
        ee()->cp->add_js_script('file', 'cp/members/member_field_reorder');
        ee()->cp->add_js_script('plugin', 'ee_table_reorder');

        $vars['callButton']     = ee()->sm->url('create_member_field');
        $vars['popupURL']       = $this->base_url;

        $reorder_ajax_fail = ee('CP/Alert')->makeBanner('reorder-ajax-fail')
        ->asIssue()
        ->canClose()
        ->withTitle(lang('member_field_ajax_reorder_fail'))
        ->addToBody(lang('member_field_ajax_reorder_fail_desc'));

        ee()->javascript->set_global('member_fields.reorder_url', ee('CP/URL')->make('members/fields/order/')->compile());
        ee()->javascript->set_global('alert.reorder_ajax_fail', $reorder_ajax_fail->render());

        return $vars;

    }

    function handleMemberFieldsListPost()
    {

        $fieldIDs = ee()->input->post('selection');

        if ( ! is_array($fieldIDs))
        {
            $fieldIDs = array($selected);
        }

        $fields = ee('Model')->get('MemberField', $fieldIDs)->all();
        $field_names = $fields->pluck('field_label');
        $field_names = array_map(function($field_name)
        {
            return htmlentities($field_name, ENT_QUOTES, 'UTF-8');
        }, $field_names);

        $fields->delete();

        ee('CP/Alert')->makeInline('fields')
            ->asSuccess()
            ->withTitle(lang('success'))
            ->addToBody(lang('member_fields_removed_desc'))
            ->addToBody($field_names)
            ->defer();

    }

    function handleCreateMemberFieldForm($vars)
    {

        ee()->lang->loadfile('member');

        if ($vars['field_id'])
        {
            $field = ee('Model')->get('smart_members:SmMemberField', array($vars['field_id']))->first();
            $saveBtnText = sprintf(lang('btn_save'), lang('field'));
            $cpPageTitle = lang('edit_member_field');
            $baseURL = ee()->sm->url('create_member_field', array('id' => $vars['field_id']));

            $smSettings = @unserialize($field->m_sm_settings);
            if(isset($smSettings['field_type']) && $smSettings['field_type'] != "")
            {
                $field->field_type = $smSettings['field_type'];
            }
        }
        else
        {
            // Only auto-complete field short name for new fields
            ee()->cp->add_js_script('plugin', 'ee_url_title');
            ee()->javascript->output('
                $("input[name=m_field_label]").bind("keyup keydown", function() {
                    $(this).ee_url_title("input[name=m_field_name]");
                });
            ');

            $field = ee('Model')->make('smart_members:SmMemberField');
            $field->field_type = 'text';

            $saveBtnText = sprintf(lang('btn_save'), lang('field'));
            $cpPageTitle = lang('create_member_field');

            $baseURL = ee()->sm->url('create_member_field');
        }

        if ( ! $field)
        {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->lang->loadfile('admin_content');

        ee()->cp->add_js_script(array(
            'file' => array('cp/form_group'),
        ));

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'type',
                    'desc' => '',
                    'fields' => array(
                        'm_field_type' => array(
                            'type' => (version_compare(APP_VER, '4.0.0', '<')) ? 'select' : 'dropdown',
                            'choices' => array(
                                'date'          => lang('date'),
                                'text'          => lang('text_input'),
                                'textarea'      => lang('textarea'),
                                'select'        => lang('select_dropdown'),
                                'url'           => lang('mbr_url'),
                                'radio'         => lang('radio'),
                                'checkboxes'    => lang('checkboxes'),
                                'file'          => lang('file'),
                                'multi_select'  => lang('multi_select'),
                            ),
                            'group_toggle' => array(
                                'date'          => 'date',
                                'text'          => 'text',
                                'textarea'      => 'textarea',
                                'select'        => 'select',
                                'radio'         => 'radio', 
                                'checkboxes'    => 'checkboxes', 
                                'file'          => 'file', 
                                'multi_select'  => 'multi_select'
                            ),
                            'value' => $field->field_type
                        )
                    )
                ),
                array(
                    'title' => 'name',
                    'fields' => array(
                        'm_field_label' => array(
                            'type'      => 'text',
                            'value'     => $field->field_label,
                            'required'  => TRUE
                        )
                    )
                ),
                array(
                    'title' => 'short_name',
                    'desc' => 'alphadash_desc',
                    'fields' => array(
                        'm_field_name' => array(
                            'type'      => 'text',
                            'value'     => $field->field_name,
                            'required'  => TRUE
                        )
                    )
                ),
                array(
                    'title' => 'field_description',
                    'desc' => 'field_description_info',
                    'fields' => array(
                        'm_field_description' => array(
                            'type'  => 'textarea',
                            'value' => $field->field_description
                        )
                    )
                ),
                array(
                    'title' => 'require_field',
                    'desc' => 'cat_require_field_desc',
                    'fields' => array(
                        'm_field_required' => array(
                            'type'  => 'yes_no',
                            'value' => $field->field_required
                        )
                    )
                )
            ),
            'visibility' => array(
                array(
                    'title' => 'is_field_reg',
                    'desc' => 'is_field_reg_cont',
                    'fields' => array(
                        'm_field_reg' => array(
                            'type' => 'yes_no',
                            'value' => $field->field_reg
                        )
                    )
                ),
                array(
                    'title' => 'is_field_public',
                    'desc' => 'is_field_public_cont',
                    'fields' => array(
                        'm_field_public' => array(
                            'type' => 'yes_no',
                            'value' => $field->field_public
                        )
                    )
                )
            )
        );

        $vars['sections'] = array_merge($vars['sections'], $field->getSettingsForm());
        // These are currently the only fieldtypes we allow; get their settings forms
        foreach (array('date', 'text', 'textarea', 'select', 'radio', 'checkboxes', 'file', 'multi_select') as $fieldtype)
        {
            if ($field->field_type != $fieldtype)
            {
                $dummy_field = ee('Model')->make('smart_members:SmMemberField');
                $dummy_field->field_type = $fieldtype;
                $vars['sections'] = array_merge($vars['sections'], $dummy_field->getSettingsForm());
            }
        }

        if ( ! empty($_POST))
        {
            $field->set($_POST);
            // m_ prefix dance
            foreach ($_POST as $key => $value)
            {
                if ($field->hasProperty($key) OR $field->hasProperty('m_'.$key))
                {
                    $field->$key = $value;
                }
            }

            $result = $field->validate();

            if (AJAX_REQUEST)
            {
                $field = ee()->input->post('ee_fv_field');

                if ($result->hasErrors($field))
                {
                    ee()->output->send_ajax_response(array('error' => $result->renderError($field)));
                }
                else
                {
                    ee()->output->send_ajax_response('success');
                }
                exit;
            }

            if ($result->isValid())
            {

                $field->m_sm_settings = serialize(array('field_type' => $field->m_field_type));
                /*if($field->m_field_type == "checkboxes" || $field->m_field_type == "multi_select")
                {
                    $field->m_field_type = "textarea";
                }
                elseif($field->m_field_type == "file" || $field->m_field_type == "radio")
                {
                    $field->m_field_type = "text";
                }*/ // commetn temporary check those field how working

                $field->save();
                ee()->session->set_flashdata('field_id', $field->field_id);

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('member_field_saved'))
                    ->addToBody(lang('member_field_saved_desc'))
                    ->defer();

                ee()->functions->redirect(ee()->sm->url('member_fields'));
            }
            else
            {
                ee()->load->library('form_validation');
                ee()->form_validation->_error_array = $result->renderErrors();
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('member_field_not_saved'))
                    ->addToBody(lang('member_field_not_saved_desc'))
                    ->now();
            }
        }

        $vars['ajax_validate'] = TRUE;
        $vars['save_btn_text_working'] = 'btn_saving';
        
        ee()->javascript->output('$(document).ready(function () {
            EE.cp.fieldToggleDisable(null, "m_field_type");
        });');

        $vars += array(
            'base_url'              => $baseURL,
            'cp_page_title'         => $cpPageTitle,
            'save_btn_text'         => $saveBtnText,
            'save_btn_text_working' => 'btn_saving'
        );

        return $vars;

    }

    //ee5 or ee6
    function  renderFiltersComman($filters){
        $ret = $filters->render($this->base_url);
        $this->params = $filters->values();
        $this->perpage = $this->params['perpage'];
        $this->page = ((int) ee()->input->get('page')) ?: 1;
        $this->offset = ($this->page - 1) * $this->perpage;

        $this->base_url->addQueryStringVariables($this->params);
        return $ret;
    }

    //ee5
    function renderFilters(FilterFactory $filters)
    {
        return $this->renderFiltersComman($filters);
    }

    //ee6
    function renderFiltersEE6(FilterFactoryEE6 $filters)
    {
        return $this->renderFiltersComman($filters);
    }
    
    /*Update custom member file fields*/
    function update_custom_file_fields($post_data)
    {

        /*Get data of files*/
        $data = $_FILES;
        $ret = array();

        /*Unset static file fields to ignore*/
        unset($data['avatar_filename']);
        unset($data['photo_filename']);
        unset($data['sig_img_filename']);

        if(count($data) == 0)
        {
            $ret['post_data'] = $post_data;
            return $ret;
        }

        /*Load need ful libraries*/
        ee()->load->library('filemanager');

        /*Get basic dependancy data*/
        $member_fields = ee()->mfModel->getMemberFields(true);
        $dir           = ee()->mfModel->getAllowedDirectory(true);

        foreach ($data as $key => $value)
        {

            /* Return If no file attached*/
            if($data[$key]['name'] == "")
            {
                continue;
            }

            if(isset($member_fields[$key]['m_sm_settings']['field_type']) && $member_fields[$key]['m_sm_settings']['field_type'] == "file")
            {

                $allowedDirectory = json_decode($member_fields[$key]['m_field_settings'], true);
                if(isset($allowedDirectory['allowed_directories']) && isset($dir[$allowedDirectory['allowed_directories']]))
                {

                    $image_only = false;
                    if($allowedDirectory['field_content_type'] == "image")
                    {
                        $image_only = true;
                    }

                    $res = ee()->filemanager->upload_file($allowedDirectory['allowed_directories'], $key, $image_only);
                    if(isset($res['error']))
                    {
                        $ret['error']['error:'.$key] = $res['error'];
                    }
                    else
                    {
                        $post_data[$key] = "{filedir_".$res['directory']."}".$res['file_name'];
                    }

                }

            }

        }

        /*Set postdata with uploaded file name and return*/
        $ret['post_data'] = $post_data;
        
        return $ret;

    }

    /*Handle checkbox and multi select submit*/
    function handle_checkboxes_submit($data)
    {

        /*Covert arry to \n seperated string*/
        if(is_array($data))
        {
            $temp = "";
            for ($i=0; $i < count($data); $i++)
            {
                
                $temp .= $data[$i];
                
                if($i != (count($data) - 1))
                {
                    $temp .= "\n";
                }

            }

            return $temp;

        }
        else
        {
            return $data;
        }

    }

    /*Parse the directory by EE codes*/
    function parseDirectory()
    {

        $field_dir = ee()->mfModel->getAllowedDirectory(true);

        if($field_dir === false)
        {
            return false;
        }

        $temp = array();
        foreach ($field_dir as $key => $value)
        {
            $temp['filedir_'.$field_dir[$key]['id']] = $field_dir[$key]['url'];
        }

        return $temp;

    }

    /*Initialize all basic needful fileds for backend custom member field forms*/
    function field_initialize()
    {

        $main_fields = array('m_field_name', 'm_field_label', 'm_field_description', 'm_field_order', 'm_field_width', 
            'm_field_type','m_field_search','m_field_show_fmt','field_text_direction', 'm_field_list_items', 'm_field_maxl', 
            'm_field_ta_rows', 'm_field_fmt','m_field_required','m_field_public','m_field_reg', 'm_field_cp_reg', 
            'sm_allowed_file_directory');

        return $main_fields;

    }

    /*Set array with ID, name and label*/
    function setArray($data)
    {

        $temp = array();
        foreach ($data as $key => $value)
        {
            $temp[$value]['field'] = $value;
            $temp[$value]['label'] = lang($value);
            $temp[$value]['rules'] = "";
            $temp[$value]['value'] = "";
        }

        asort($temp);
        return $temp;

    }

}
