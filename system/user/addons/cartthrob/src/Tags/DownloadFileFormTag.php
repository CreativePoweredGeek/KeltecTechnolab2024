<?php

namespace CartThrob\Tags;

use CartThrob\Math\Number;
use EE_Session;

class DownloadFileFormTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library(['cartthrob_file', 'form_builder', 'paths']);
        ee()->load->model(['cartthrob_field_model', 'cartthrob_entries_model', 'tools_model']);
        ee()->load->helper('array');
    }

    public function process()
    {
        if ($this->hasParam('member_id')) {
            if (in_array($this->param('member_id'), ['CURRENT_USER', '{logged_in_member_id}', '{member_id}'])) {
                $this->setParam('member_id', $this->getMemberId());
            } else {
                $this->setParam('member_id', Number::sanitize($this->param('member_id')));
            }
        }
        if ($this->hasParam('group_id')) {
            if (in_array($this->param('group_id'), ['{logged_in_group_id}', '{group_id}'])) {
                $this->setParam('group_id', $this->getGroupId());
            } else {
                $this->setParam('group_id', $this->param('group_id'));
            }
        }
        // Add in support for new role_id param - Should overwrite the older group_id stuff if this is an upgrade.
        if ($this->hasParam('role_id')) {
            if (in_array($this->param('role_id'), ['{{logged_in_primary_role_id}}', '{role_id}'])) {
                $this->setParam('group_id', $this->getGroupId());
            } else {
                $this->setParam('group_id', $this->param('role_id'));
            }
        }

        if ($this->hasParam('field') && $this->hasParam('entry_id')) {
            $entry = ee()->cartthrob_entries_model->entry($this->param('entry_id'));

            // @NOTE if the developer has assigned an entry id and a field, but there's nothing IN the field,  then the path doesn't get set, and no debug information is output, because path, below would be set to NULL
            if ($path = element($this->param('field'), $entry)) {
                $path = ee()->paths->parse_file_server_paths($path);

                $this->setParam('file', $path);
            }
        }

        if ($this->param('debug') && $this->param('file')) {
            $this->setTagdata($this->tagdata() . ee()->cartthrob_file->fileDebug($this->param('file')));
        }

        $data = $this->globalVariables(true);

        if (in_array($this->param('member_id'), ['CURRENT_USER', '{member_id}', '{logged_in_member_id}'])) {
            $this->setParam('member_id', $this->getMemberId());
        }

        if (in_array($this->param('group_id'), ['{group_id}', '{logged_in_group_id}'])) {
            $this->setParam('group_id', $this->getGroupId());
        }

        if ($this->hasParam('free_file')) {
            $this->setParam('free_file', 'FI' . $this->param('free_file'));
        } elseif ($this->hasParam('file') && (!$this->param('member_id') && !$this->param('group_id'))) {
            $this->setParam('free_file', 'FI' . $this->param('file'));
        } elseif ($this->hasParam('file')) {
            $this->setParam('file', 'FP' . $this->param('file'));
        }

        ee()->form_builder->initialize([
            'form_data' => [
                'secure_return',
                'language',
            ],
            'encoded_form_data' => [
                'file' => 'FP',
                'free_file' => 'FI',
                'group_id' => 'RI',
            ],
            'encoded_numbers' => [
                'member_id' => 'MI',
            ],
            'classname' => 'Cartthrob',
            'method' => 'download_file_action',
            'params' => $this->params(),
            'content' => $this->parseVariablesRow($data),
        ]);

        return ee()->form_builder->form();
    }
}
