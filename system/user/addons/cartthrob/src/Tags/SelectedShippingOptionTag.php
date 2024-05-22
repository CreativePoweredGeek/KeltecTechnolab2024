<?php

namespace CartThrob\Tags;

use EE_Session;

class SelectedShippingOptionTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library(['api', 'api/api_cartthrob_shipping_plugins']);
    }

    /**
     * Outputs the description of the shipping item selected in the backend
     */
    public function process()
    {
        return (ee()->cartthrob->cart->shipping_info('shipping_option')) ?
            ee()->cartthrob->cart->shipping_info('shipping_option') :
            ee()->api_cartthrob_shipping_plugins->default_shipping_option();
    }
}
