<?php

namespace CartThrob\Actions;

use CartThrob\Request\Request;
use CartThrob\Services\EncryptionService;
use EE_Session;
use ExpressionEngine\Library\Security\XSS;

class DownloadFileAction extends Action
{
    /** @var Encrypt */
    private $encrypt;

    /** @var XSS */
    private $xss;

    public function __construct(EE_Session $session, Request $request, EncryptionService $encrypt, XSS $xss)
    {
        parent::__construct($session, $request);

        $this->encrypt = $encrypt;

        $this->xss = $xss;

        ee()->load->library(['cartthrob_file', 'curl', 'paths']);
        ee()->load->helper('string');
    }

    public function process()
    {
        // @TODO add in debug to output member and group id, and whether the file's protected or not

        // cartthrob_download_start hook
        if (ee()->extensions->active_hook('cartthrob_download_start') === true) {
            // @TODO work on hook parameters
            // $edata = $EXT->universal_call_extension('cartthrob_download_start');
            ee()->extensions->call('cartthrob_download_start');
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        ee()->form_builder->set_require_form_hash(false);
        ee()->form_builder->set_require_rules(false);
        ee()->form_builder->set_require_errors(false);

        $path = null;

        if (!$this->request->has('FP') && !$this->request->has('FI')) {
            if (!ee()->form_builder->validate()) {
                return ee()->form_builder->action_complete();
            }
        }

        ee()->cartthrob->save_customer_info();

        // Check member ID
        if ($this->request->has('MI')) {
            $member_id = $this->encrypt->decode($this->request->input('MI'));
        }

        // Check group id.
        if ($this->request->has('RI')) {
            $role_id = $this->encrypt->decode($this->request->input('RI'));
            $role_ids = explode('|', $role_id);
        }

        // standard file from form, or free_file from download link
        if ($this->request->has('FI')) {
            $path = $this->encrypt->decode($this->request->input('FI'));

            if (substr($path, 0, 2) !== 'FI') {
                ee()->form_builder->add_error(ee()->lang->line('download_file_not_authorized'));
            } else {
                $path = substr($path, 2);
            }
        } // protected file from the download link
        elseif ($this->request->has('FP')) {
            $path = $this->encrypt->decode($this->request->input('FP'));

            if (substr($path, 0, 2) !== 'FP') {
                ee()->form_builder->add_error(ee()->lang->line('download_file_not_authorized'));
            } else {
                $path = substr($path, 2);
            }

            if (empty($member_id) && empty($role_id)) {
                ee()->form_builder->add_error(ee()->lang->line('download_file_not_authorized'));
            }
        } else {
            ee()->form_builder->add_error(ee()->lang->line('download_url_not_specified'));
        }

        if (ee()->form_builder->errors()) {
            ee()->form_builder->action_complete();
        }

        // Check member id.
        if (!empty($member_id) && $member_id != $this->session->userdata('member_id')) {
            ee()->form_builder->add_error(ee()->lang->line('download_file_not_authorized_for_member'));
        }

        // Check group id
        // We now need to compare the chunk of roles, with the roles assigned to the member
        if (!empty($role_ids) && (!array_intersect($role_ids, $this->session->getMember()->getAllRoles()->pluck('role_id')))) {
            ee()->form_builder->add_error(ee()->lang->line('download_file_not_authorized_for_group'));
        }

        // cartthrob_download_end hook
        if (ee()->extensions->active_hook('cartthrob_download_end') === true) {
            $path = ee()->extensions->call('cartthrob_download_end', $path);
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        if (!ee()->form_builder->errors()) {
            ee()->cartthrob_file->force_download($path, $this->request->boolean('debug'));

            if (ee()->cartthrob_file->errors()) {
                ee()->form_builder->add_error(ee()->cartthrob_file->errors());
            }
        }

        ee()->form_builder->action_complete();
    }
}
