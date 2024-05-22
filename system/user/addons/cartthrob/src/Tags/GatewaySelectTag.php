<?php

namespace CartThrob\Tags;

use EE_Session;
use ExpressionEngine\Service\Encrypt\Encrypt;

class GatewaySelectTag extends Tag
{
    /** @var Encrypt */
    private $encrypt;

    public function __construct(EE_Session $session, Encrypt $encrypt)
    {
        parent::__construct($session);

        $this->encrypt = $encrypt;

        ee()->load->library(['api', 'api/api_cartthrob_payment_gateways']);
        ee()->load->helper('form');
    }

    public function process()
    {
        $attrs = [];

        if ($this->hasParam('encrypt') && $this->param('encrypt') === false) {
            $encrypt = false;
        } else {
            $encrypt = true;
        }

        if ($this->hasParam('id')) {
            $attrs['id'] = $this->param('id');
        }

        if ($this->hasParam('class')) {
            $attrs['class'] = $this->param('class');
        }

        if ($this->hasParam('onchange')) {
            $attrs['onchange'] = $this->param('onchange');
        }

        $extra = '';

        if ($attrs) {
            $extra .= _attributes_to_string($attrs);
        }

        if ($this->hasParam('extra')) {
            if (substr($this->param('extra'), 0, 1) != ' ') {
                $extra .= ' ';
            }

            $extra .= $this->param('extra');
        }

        $selectableGateways = ee()->cartthrob->store->config('available_gateways');
        $name = $this->param('name', 'gateway');
        $selected = $this->param('selected', ee()->cartthrob->store->config('payment_gateway'));
        $isSelectedEncoded = isBase64Encoded($selected);

        // get the gateways that the user wants to output
        if ($this->hasParam('gateways')) {
            $final_g = [];

            foreach ($this->explodeParam('gateways', []) as $my_gateways) {
                $final_g['Cartthrob_' . $my_gateways] = '1';
            }

            // Making it so that it's possible to add the default gateway in this parameter without it having been selected as a choosable gateway.
            // if its the default then it's choosable in my book.
            if (isset($final_g[ee()->cartthrob->store->config('payment_gateway')]) && !isset($selectableGateways[ee()->cartthrob->store->config('payment_gateway')])) {
                $selectableGateways[ee()->cartthrob->store->config('payment_gateway')] = 1;
            }

            $selectableGateways = array_intersect_key($final_g, $selectableGateways);
        }

        // if the users selected gateways is not an option, then we'll use the default
        if (!isset($selectableGateways[$selected]) && is_array($selectableGateways)) {
            if (isset($selectableGateways['Cartthrob_' . $selected])) {
                $selected = 'Cartthrob_' . $selected;
            } elseif ($isSelectedEncoded) {
                if (isset($selectableGateways['Cartthrob_' . $this->encrypt->decode($selected)])) {
                    $selected = 'Cartthrob_' . $this->encrypt->decode($selected);
                } elseif (!isset($selectableGateways[$this->encrypt->decode($selected)])) {
                    $selected = ee()->cartthrob->store->config('payment_gateway');
                    $selectableGateways = array_merge([ee()->cartthrob->store->config('payment_gateway') => '1'], (array)$selectableGateways);
                } else {
                    $selected = $this->encrypt->decode($selected);
                }
            }
        }

        if ($this->cartHasSubscription()) {
            $subscription_gateways = [];

            foreach (ee()->api_cartthrob_payment_gateways->subscription_gateways() as $plugin_data) {
                $subscription_gateways[] = $plugin_data['classname'];
            }

            $selectableGateways = array_intersect_key($selectableGateways, array_flip($subscription_gateways));
        }

        // if none have been selected, OR if you're not allowed to select, then the default is shown
        if (!ee()->cartthrob->store->config('allow_gateway_selection') || count($selectableGateways) == 0) {
            $selectableGateways = [ee()->cartthrob->store->config('payment_gateway') => '1'];
            $selected = ee()->cartthrob->store->config('payment_gateway');
        }

        $gateways = ee()->api_cartthrob_payment_gateways->gateways();

        $data = [];
        foreach ($gateways as $plugin_data) {
            if (isset($selectableGateways[$plugin_data['classname']])) {
                ee()->lang->loadfile(strtolower($plugin_data['classname']), 'cartthrob', false);

                if (isset($plugin_data['title'])) {
                    $title = lang($plugin_data['title']);
                } else {
                    $title = $plugin_data['classname'];
                }

                if ($encrypt) {
                    // have to create a variable here, because it'll be used in a spot
                    // where it needs to match. each time we encode, the values change.
                    $encoded = $this->encrypt->encode($plugin_data['classname']);
                    $data[$encoded] = $title;

                    if ($plugin_data['classname'] == $selected) {
                        $selected = $encoded;
                    }
                } else {
                    $data[$plugin_data['classname']] = $title;
                }
            }
        }

        asort($data);

        if ($this->param('add_blank')) {
            $data = array_merge(['' => '---'], $data);
        }

        return form_dropdown($name, $data, $selected, $extra);
    }

    /**
     * @return bool
     */
    private function cartHasSubscription()
    {
        foreach (ee()->cartthrob->cart->items() as $item) {
            if ($item->meta('subscription')) {
                return true;
            }
        }

        return false;
    }
}
