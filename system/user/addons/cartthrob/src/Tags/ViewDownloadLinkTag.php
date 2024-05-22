<?php

namespace CartThrob\Tags;

use EE_Session;
use ExpressionEngine\Service\Encrypt\Encrypt;

class ViewDownloadLinkTag extends Tag
{
    /** @var Encrypt */
    private $encrypt;

    public function __construct(EE_Session $session, Encrypt $encrypt)
    {
        parent::__construct($session);

        $this->encrypt = $encrypt;
    }

    public function process()
    {
        $link = $this->param('template');

        if (!$this->param('file')) {
            return show_error(ee()->lang->line('download_url_not_specified'));
        } else {
            $link .= base64_encode(rawurlencode($this->encrypt->encode($this->param('file'))));
        }

        if ($member_id = $this->param('member_id')) {
            if (in_array($member_id, ['{logged_in_member_id}', '{member_id}', 'CURRENT_USER'])) {
                $member_id = $this->getMemberId();
            }

            $link .= '/' . base64_encode(rawurlencode($this->encrypt->encode($member_id)));
        }

        return $link;
    }
}
