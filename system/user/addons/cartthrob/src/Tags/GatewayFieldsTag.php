<?php

namespace CartThrob\Tags;

class GatewayFieldsTag extends Tag
{
    public function process()
    {
        return ee()->api_cartthrob_payment_gateways->gateway_fields();
    }
}
