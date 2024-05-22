<?php

namespace CartThrob\Actions;

use CartThrob\Request\Request;
use EE_Session;

class AddVaultAction extends CheckoutAction
{
    public function __construct(EE_Session $session, Request $request)
    {
        parent::__construct($session, $request);

        ee()->load->library(['form_builder', 'languages', 'api/api_cartthrob_payment_gateways']);
    }

    public function process()
    {
        $vaultOptions = $this->marshalCheckoutOptions();
        $this->processCustomerInfo($vaultOptions);

        ee()->languages->set_language($this->request->input('language'));
        ee()->cartthrob_payments->setGateway($vaultOptions['gateway']);
        ee()->cartthrob_payments->setGatewayMethod($vaultOptions['gateway_method']);
        $this->setGlobalValues();

        ee()->form_builder
            ->set_show_errors(true)
            ->set_captcha(false)
            ->set_success_callback([ee()->cartthrob, 'action_complete'])
            ->set_error_callback([ee()->cartthrob, 'action_complete']);

        $not_required = explode('|', $this->request->decode('NRQ', ''));
        $required = array_diff(ee()->cartthrob_payments->requiredGatewayFields(), $not_required);
        if (!ee()->form_builder->set_required($required)->validate()) {
            return ee()->form_builder->action_complete();
        }

        $_POST['gateway'] = $vaultOptions['gateway'];
        ee()->cartthrob->cart->set_order($_POST); // to force some gateway params
        $token = ee('cartthrob:VaultService')->createToken($_POST);
        if ($token->error_message() != '') {
            return ee()->form_builder
                ->add_error($token->error_message())
                ->action_complete();
        }

        return ee()->form_builder->action_complete();
    }
}
