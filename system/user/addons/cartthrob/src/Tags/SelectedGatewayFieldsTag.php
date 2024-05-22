<?php

namespace CartThrob\Tags;

use CartThrob\Request\Request;
use EE_Session;
use ExpressionEngine\Service\Encrypt\Encrypt;

class SelectedGatewayFieldsTag extends Tag
{
    /** @var Request */
    private $request;

    /** @var Encrypt */
    private $encrypt;

    public function __construct(EE_Session $session, Request $request, Encrypt $encrypt)
    {
        parent::__construct($session);

        $this->encrypt = $encrypt;
        $this->request = $request;

        ee()->load->library(['api', 'api/api_cartthrob_payment_gateways']);
    }

    /**
     * Returns data from the 'html' field of the currently selected gateway
     */
    public function process()
    {
        $selectable_gateways = ee()->cartthrob->store->config('available_gateways');

        if ($this->request->has('gateway')) {
            $selected = $this->request->input('gateway');
        } else {
            $selected = $this->hasParam('gateway') ? $this->param('gateway') : ee()->cartthrob->store->config('payment_gateway');
        }

        if (!isset($selectable_gateways[$selected])) {
            if (isset($selectable_gateways['Cartthrob_' . $selected])) {
                $selected = 'Cartthrob_' . $selected;
            } elseif (isset($selectable_gateways['Cartthrob_' . $this->encrypt->decode($selected)])) {
                $selected = 'Cartthrob_' . $this->encrypt->decode($selected);
            } // make sure this isn't an encoded value.
            elseif (!isset($selectable_gateways[$this->encrypt->decode($selected)])) {
                $selected = ee()->cartthrob->store->config('payment_gateway');
                $selectable_gateways = array_merge([ee()->cartthrob->store->config('payment_gateway') => '1'], $selectable_gateways);
            } else {
                $selected = $this->encrypt->decode($selected);
            }
        }

        // if none have been selected, OR if you're not allowed to select, then the default is shown
        if (!ee()->cartthrob->store->config('allow_gateway_selection') || count($selectable_gateways) == 0) {
            $selectable_gateways = [ee()->cartthrob->store->config('payment_gateway') => '1'];
            $selected = ee()->cartthrob->store->config('payment_gateway');
        }

        ee()->api_cartthrob_payment_gateways->set_gateway($selected);

        if (ee()->api_cartthrob_payment_gateways->template()) {
            $return_data = '{embed="' . ee()->api_cartthrob_payment_gateways->template() . '"}';
        } else {
            $return_data = ee()->api_cartthrob_payment_gateways->gateway_fields();
        }

        ee()->api_cartthrob_payment_gateways->reset_gateway();

        return $return_data;
    }
}
