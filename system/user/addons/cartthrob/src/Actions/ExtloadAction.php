<?php

namespace CartThrob\Actions;

class ExtloadAction extends Action
{
    public function process()
    {
        $gateway = ee()->input->get_post('gateway');

        ee()->load->library('cartthrob_payments');
        ee()->cartthrob_payments->setGateway($gateway);
        if (!method_exists(ee()->cartthrob_payments->gateway(), 'extload')) {
            exit(sprintf('Response method for %s gateway does not exist', $gateway));
        }

        $data = array_merge($_POST, $_GET);
        ee()->cartthrob_payments->gateway()->extload($data);
        exit;
    }
}
