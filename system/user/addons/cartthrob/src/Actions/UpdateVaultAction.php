<?php

namespace CartThrob\Actions;

use CartThrob\Model\Vault as VaultModel;
use CartThrob\Request\Request;
use EE_Session;

class UpdateVaultAction extends Action
{
    public function __construct(EE_Session $session, Request $request)
    {
        parent::__construct($session, $request);

        ee()->load->library(['form_builder', 'languages', 'api/api_cartthrob_payment_gateways']);
    }

    public function process()
    {
        $vault_id = ee()->input->post('vault_id');
        $vault = ee('cartthrob:VaultService')->getMemberVault($vault_id, ee()->session->userdata('member_id'));
        if (!$vault instanceof VaultModel) {
            return ee()->form_builder->action_complete();
        }

        ee()->languages->set_language($this->request->input('language'));
        ee()->cartthrob_payments->setGateway($vault->gateway);

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

        ee()->cartthrob->cart->set_order($_POST);
        $vault->set($_POST);
        if (!ee('cartthrob:VaultService')->updateVault($vault)) {
            return ee()->form_builder
                ->add_error('Failed Updating Vault!')
                ->action_complete();
        }

        return ee()->form_builder->action_complete();
    }
}
