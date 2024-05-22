<?php

namespace CartThrob\Tags;

use EE_Session;

class GetCartthrobLogoTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->helper(['html', 'url']);
    }

    public function process()
    {
        return anchor(
            'https://cartthrob.com',
            img([
                'src' => 'https://cartthrob.com/images/powered_by_logos/powered_by_cartthrob.png',
                'alt' => ee()->lang->line('powered_by_title'),
            ]),
            [
                'title' => ee()->lang->line('powered_by_title'),
                'onclick' => "javascript:window.open('http://cartthrob.com','cartthrob');return false;",
            ]
        );
    }
}
