<?php

namespace CartThrob\Tags;

use EE_Session;

class SetConfigTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->helper('array');
    }

    public function process()
    {
        $data = array_merge(
            ee()->cartthrob->cart->customer_info(),
            array_key_prefix(ee()->cartthrob->cart->customer_info(), 'customer_'),
            ee()->cartthrob->cart->info(),
            ee()->TMPL->segment_vars,
            ee()->config->_global_vars
        );

        $this->setTagdata(ee()->functions->prep_conditionals($this->tagdata(), $data));
        $this->setTagdata(ee()->TMPL->advanced_conditionals($this->tagdata()));

        $hash = md5($this->tagdata());

        if (ee()->cartthrob->cart->meta('set_config_hash') === $hash) {
            // maybe we shouldn't reset it? leaving it for now @TODO
            ee()->cartthrob->cart->set_meta('set_config_hash', false)->save();

            return '';
        }

        ee()->cartthrob->cart->set_meta('set_config_hash', $hash);

        $vars = ee('Variables/Parser')->extractVariables($this->tagdata());

        foreach ($vars['var_single'] as $var_single) {
            $params = ee('Variables/Parser')->parseTagParameters($var_single);
            $method = (preg_match('/^set_(config_)?([^\s]+)\s*.*$/', $var_single, $match)) ? 'set_config_' . $match[2] : false;

            if ($method && method_exists(ee()->cartthrob, $method)) {
                ee()->cartthrob->$method($params);
            } elseif (isset($params['value'])) {
                ee()->cartthrob->cart->set_config($match[2], $params['value']);
            }

            if ($method) {
                $this->setTagdata(ee()->TMPL->swap_var_single($var_single, '', $this->tagdata()));
            }
        }

        ee()->cartthrob->cart->save();

        ee()->functions->redirect(ee()->functions->create_url(ee()->uri->uri_string()));

        return $this->tagdata();
    }
}
