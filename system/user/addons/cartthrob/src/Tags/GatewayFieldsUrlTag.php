<?php

namespace CartThrob\Tags;

class GatewayFieldsUrlTag extends Tag
{
    /**
     * Outputs an action URL so that you can post requests for gateway fields to a URL instead of a template
     * this will use the change_gateway_fields action to get selected gateway fields with a CSRF_TOKEN hash
     *
     * @return string action url
     */
    public function process()
    {
        return ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . ee()->functions->insert_action_ids(ee()->functions->fetch_action_id('Cartthrob', 'change_gateway_fields_action'));
    }
}
