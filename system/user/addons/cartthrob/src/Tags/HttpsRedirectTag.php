<?php

namespace CartThrob\Tags;

class HttpsRedirectTag extends Tag
{
    public function process()
    {
        force_https($this->param('domain'), ee()->config->item('send_headers') === 'y');

        if ($this->param('secure_site_url')) {
            ee()->config->config['site_url'] = str_replace('http://', 'https://', ee()->config->item('site_url'));
        }

        return $this->tagdata();
    }
}
