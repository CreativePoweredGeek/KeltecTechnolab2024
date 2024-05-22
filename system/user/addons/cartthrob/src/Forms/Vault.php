<?php

namespace CartThrob\Forms;

class Vault extends AbstractForm
{
    /**
     * @return array
     */
    protected function getGatewayOptions()
    {
        ee()->load->library('api/api_cartthrob_payment_gateways');
        ee()->load->library('cartthrob_payments');
        $sub_gateways = ee()->api_cartthrob_payment_gateways->subscription_gateways();
        $gateways = [];
        foreach ($sub_gateways as $g) {
            ee()->cartthrob_payments->loadLang(strtolower($g['classname']));
            if (isset($g['title']) && isset($g['classname'])) {
                $gateways[$g['classname']] = lang($g['title']);
            }
        }

        return $gateways;
    }

    /**
     * Generates the Form array for EE use
     * @return \array[][]
     */
    public function generate(): array
    {
        return [
            [
                [
                    'title' => 'ct.vault.member_id',
                    'desc' => 'ct.vault.member_id.note',
                    'fields' => [
                        'member_id' => [
                            'name' => 'member_id',
                            'type' => 'text',
                            'value' => $this->get('member_id'),
                            'required' => true,
                        ],
                    ],
                ],
                [
                    'title' => 'ct.vault.order_id',
                    'desc' => 'ct.vault.order_id.note',
                    'fields' => [
                        'order_id' => [
                            'name' => 'order_id',
                            'type' => 'text',
                            'value' => $this->get('order_id'),
                        ],
                    ],
                ],
                [
                    'title' => 'ct.vault.token',
                    'desc' => 'ct.vault.token.note',
                    'fields' => [
                        'token' => [
                            'name' => 'token',
                            'type' => 'text',
                            'value' => $this->get('token'),
                            'required' => true,
                        ],
                    ],
                ],
                [
                    'title' => 'ct.vault.gateway',
                    'desc' => 'ct.vault.gateway.note',
                    'fields' => [
                        'gateway' => [
                            'name' => 'gateway',
                            'type' => 'select',
                            'value' => $this->get('gateway', ''),
                            'choices' => $this->getGatewayOptions(),
                            'required' => true,
                        ],
                    ],
                ],
                [
                    'title' => 'ct.vault.customer_id',
                    'desc' => 'ct.vault.customer_id.note',
                    'fields' => [
                        'customer_id' => [
                            'name' => 'customer_id',
                            'type' => 'text',
                            'value' => $this->get('customer_id'),
                        ],
                    ],
                ],
                [
                    'title' => 'ct.vault.last_four',
                    'desc' => 'ct.vault.last_four.note',
                    'fields' => [
                        'last_four' => [
                            'name' => 'last_four',
                            'type' => 'text',
                            'value' => $this->get('last_four'),
                        ],
                    ],
                ],
            ],
        ];
    }
}
