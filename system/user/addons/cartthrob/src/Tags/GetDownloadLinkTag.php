<?php

namespace CartThrob\Tags;

use CartThrob\Math\Number;
use CartThrob\Services\EncryptionService;
use EE_Session;

class GetDownloadLinkTag extends Tag
{
    /** @var Encrypt */
    private $encrypt;

    public function __construct(EE_Session $session, EncryptionService $encrypt)
    {
        parent::__construct($session);

        $this->encrypt = $encrypt;

        ee()->load->library(['cartthrob_file', 'paths']);
        ee()->load->model(['cartthrob_field_model', 'cartthrob_entries_model', 'tools_model']);
        ee()->load->helper('array');
    }

    public function process()
    {
        $file = null;
        $path = null;

        if ($this->hasParam('field') && $this->hasParam('entry_id')) {
            $entry = ee()->cartthrob_entries_model->entry($this->param('entry_id'));

            // @NOTE if the developer has assigned an entry id and a field, but there's nothing IN the field,  then the path doesn't get set, and no debug information is output, because path, below would be set to NULL
            if ($path = element($this->param('field'), $entry)) {
                $path = ee()->paths->parse_file_server_paths($path);

                $this->setParam('file', $path);
                $this->setParam('free_file', $path);
            }
        }

        if ($this->param('debug') && $this->param('file')) {
            return ee()->cartthrob_file->fileDebug($this->param('file'));
        }

        foreach ($this->params() as $key => $value) {
            if ($value !== '' || $value !== false) {
                switch ($key) {
                    case 'member_id':
                        if (in_array($value, ['{logged_in_member_id}', '{member_id}', 'CURRENT_USER'])) {
                            $value = $this->getMemberId();
                        }

                        $member_id = $this->encrypt->encode(Number::sanitize($value));

                        if ($this->hasParam('free_file')) {
                            $this->clearParam('free_file');
                        }
                        break;
                    case 'group_id':
                        if (in_array($value, ['{logged_in_group_id}', '{group_id}'])) {
                            $value = $this->getGroupId();
                        }

                        $group_id = $this->encrypt->encode(Number::sanitize($value));

                        if ($this->hasParam('free_file')) {
                            $this->clearParam('free_file');
                        }
                        break;
                    case 'language':
                        $language = $value;
                        break;
                    case 'free_file':
                        $file = '&FI=' . $this->encrypt->encode('FI' . $value);
                        break;
                    case 'file':
                        $file = '&FP=' . $this->encrypt->encode('FP' . $value);
                        break;
                }
            }
        }

        if ($this->param('debug')) {
            ee()->cartthrob_file->fileDebug($file);
        }

        $downloadUrl = sprintf(
            '%s%sACT=%s%s',
            ee()->functions->fetch_site_index(0, 0),
            QUERY_MARKER,
            ee()->functions->insert_action_ids(ee()->functions->fetch_action_id('Cartthrob', 'download_file_action')),
            $file
        );

        if (isset($member_id)) {
            $downloadUrl .= '&MI=' . $member_id;
        }

        if (isset($group_id)) {
            $downloadUrl .= '&GI=' . $group_id;
        }

        if (isset($language)) {
            $downloadUrl .= '&L=' . $language;
        }

        return $downloadUrl;
    }
}
