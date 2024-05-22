<?php

namespace CartThrob\Tags;

class SetGatewayTag extends Tag
{
    public function process()
    {
        $gateway = ee('Encrypt')->decode(ee()->input->get('gateway', true));
        $gateway = str_replace('Cartthrob_', '', $gateway);
        $return = $this->param('return');
        if (ee()->cartthrob->store->config('allow_gateway_selection')) {
            ee()->cartthrob->cart->set_customer_info('gateway', trim($gateway));
            ee()->cartthrob->cart->save();
        }

        ee()->functions->redirect(ee()->functions->create_url($return));
    }
}
