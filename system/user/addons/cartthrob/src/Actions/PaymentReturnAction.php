<?php

namespace CartThrob\Actions;

use CartThrob\Request\Request;
use CartThrob\Transactions\TransactionState;
use EE_Session;

class PaymentReturnAction extends Action
{
    public function __construct(EE_Session $session, Request $request)
    {
        parent::__construct($session, $request);

        ee()->load->library('cartthrob_payments');
    }

    /**
     * Handles information from PayPal's IPN, offsite gateways, or other payment notification systems.
     */
    public function process()
    {
        $gateway = $this->processInput('gateway');

        if (!$gateway) {
            $gateway = $this->processInput('G');
        }

        // When offsite payments are returned, they're expected to have a method
        // set to handle processing the payments.
        $method = false;
        if ($this->request->has('method')) {
            $method = $this->processInput('method');
        } elseif ($this->request->has('M')) {
            $method = $this->processInput('M');
        }

        ee()->load->library('cartthrob_payments');

        $state = new TransactionState();

        if (!ee()->cartthrob_payments->setGateway($gateway)->gateway()) {
            $state->setFailed(ee()->lang->line('invalid_payment_gateway'));
        } elseif ($method && method_exists(ee()->cartthrob_payments->gateway(), $method)) {
            $data = ee('Security/XSS')->clean($_POST);

            $data['gateway'] = $gateway;
            $data['method'] = $method;
            $data['orderId'] = $this->processInput('orderId');

            // handling get variables.
            if ($_SERVER['QUERY_STRING']) {
                // the following was added to convert the query string manually into an array
                // because something like &company=abercrombie&fitch&name=joe+jones was causing the return
                // data to get hosed.
                $_SERVER['QUERY_STRING'] = preg_replace('/&(?=[^=]*&)/', '%26', $_SERVER['QUERY_STRING']);

                $get = [];
                parse_str($_SERVER['QUERY_STRING'], $get);

                foreach ($get as $key => $value) {
                    if (!isset($data[$key])) {
                        $data[$key] = ee('Security/XSS')->clean($value);
                    }
                }
            }

            foreach ($data as $key => $item) {
                ee()->cartthrob->log($key . ' - ' . $item);
            }

            $state = ee()->cartthrob_payments->gateway()->$method($data);
        } else {
            $state->setFailed(ee()->lang->line('gateway_function_does_not_exist'));
        }

        ee()->cartthrob_payments->checkoutComplete($state);
    }

    /**
     * @param string $key
     * @return mixed
     * @todo Get rid of uses of this method
     */
    protected function processInput($key)
    {
        if (!ee()->input->get($key, true)) {
            return false;
        }

        return ee('Security/XSS')->clean(
            ee('Encrypt')->decode(
                str_replace(' ', '+', base64_decode(ee()->input->get($key, true)))
            )
        );
    }
}
