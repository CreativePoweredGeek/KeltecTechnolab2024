<?php

namespace CartThrob\Tags;

use EE_Session;
use ExpressionEngine\Library\Security\XSS;
use ExpressionEngine\Service\Encrypt\Encrypt;

class DownloadFileTag extends Tag
{
    /** @var Encrypt */
    private $encrypt;
    /** @var XSS */
    private $xss;

    public function __construct(EE_Session $session, Encrypt $encrypt, XSS $xss)
    {
        parent::__construct($session);

        $this->encrypt = $encrypt;

        ee()->load->library(['cartthrob_file', 'paths']);
        ee()->load->model(['cartthrob_field_model', 'cartthrob_entries_model', 'tools_model']);
        ee()->load->helper('array');
        $this->xss = $xss;
    }

    public function process()
    {
        if ($this->param('field') && $this->param('entry_id')) {
            $entry = ee()->cartthrob_entries_model->entry($this->param('entry_id'));

            if ($path = element($this->param('field'), $entry)) {
                $this->setParam('file', ee()->paths->parse_file_server_paths($path));
            }
        }

        if ($this->param('member_id') !== false) {
            if (!$this->param('member_id')) {
                return show_error(lang('download_file_not_authorized'));
            } elseif ($this->param('encrypted')) {
                $unencryptedMemberId = $this->xss->clean($this->encrypt->decode($this->param('member_id')));

                if ($unencryptedMemberId != $this->getMemberId()) {
                    return show_error(lang('download_file_not_authorized'));
                }
            } elseif ($this->param('member_id') != $this->getMemberId()) {
                return show_error(lang('download_file_not_authorized'));
            }
        }

        if (!$this->param('file')) {
            return show_error(ee()->lang->line('download_url_not_specified'));
        } else {
            $post_url = $this->param('file');
        }

        if ($this->param('encrypted')) {
            $post_url = $this->xss->clean($this->encrypt->decode($post_url));
        }

        ee()->cartthrob_file->force_download($post_url);

        if (ee()->cartthrob_file->errors()) {
            return show_error(ee()->cartthrob_file->errors());
        }
    }
}
