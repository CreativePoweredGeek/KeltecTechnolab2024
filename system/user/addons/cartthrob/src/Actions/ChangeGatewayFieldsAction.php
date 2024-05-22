<?php

namespace CartThrob\Actions;

use CartThrob\Tags\SelectedGatewayFieldsTag;

class ChangeGatewayFieldsAction extends Action
{
    /**
     * Gets gateway fields of selected gateway
     */
    public function process()
    {
        if (!AJAX_REQUEST) {
            exit;
        }

        $tag = new SelectedGatewayFieldsTag(ee()->TMPL->tagdata, ee()->TMPL->tagparams);
        $data = $this->globalVariables(true);
        $html = ee()->template_helper->parse_template($tag->process(), $data);

        $this->session->set_flashdata([
            'success' => !(bool)ee()->cartthrob->errors(),
            'errors' => ee()->cartthrob->errors(),
            'gateway_fields' => $html,
            'csrf_token' => ee()->functions->add_form_security_hash('{csrf_token}'),
        ]);
    }
}
