<?php

namespace CartThrob\Forms\Settings;

use CartThrob\Forms\AbstractForm;
use ExpressionEngine\Service\Validation\Validator;

class Payments extends AbstractForm
{
    /**
     * The collection of Payment Gateways available
     * @var array
     */
    protected array $gateways = [];

    /**
     * @var array
     */
    protected array $enabled_gateways = [];

    /**
     * The above $gateways array usable for input options
     * @var array
     */
    protected array $gateway_options = [];

    /**
     * @var string[]
     */
    protected array $gateways_format_options = [
        'bootstrap' => 'gateways_format_bootstrap',
        'default' => 'gateways_format_default',
    ];

    /**
     * @var array
     */
    protected $rules = [
        'allow_gateway_selection' => 'required',
        'available_gateways' => 'whenAllowGatewaySelectionIs[1]|required',
    ];

    public function __construct()
    {
        parent::__construct();
        foreach ($this->gateways_format_options as $key => $value) {
            $this->gateways_format_options[$key] = lang($value);
        }
    }

    /**
     * @return Validator
     */
    protected function getValidator(): Validator
    {
        $validator = ee('Validation')->make($this->rules);
        $data = $this->data;
        $validator->defineRule('whenAllowGatewaySelectionIs', function ($key, $value, $parameters, $rule) use ($data) {
            return ($data['allow_gateway_selection'] == $parameters[0]) ? true : $rule->skip();
        });

        return $validator;
    }

    /**
     * @return \array[][]
     */
    public function generate(): array
    {
        $form = [
            [
                'title' => 'ct.payments.gateway_selection',
                'desc' => 'ct.payments.gateway_description',
                'fields' => [
                    'payment_gateway' => [
                        'name' => 'payment_gateway',
                        'type' => 'select',
                        'value' => $this->get('payment_gateway'),
                        'required' => true,
                        'choices' => $this->getEnabledGateways(),
                    ],
                ],
            ],
            [
                'title' => 'security_settings_allow_gateway_selection',
                'desc' => 'security_settings_allow_gateway_selection_description',
                'fields' => [
                    'allow_gateway_selection' => [
                        'name' => 'allow_gateway_selection',
                        'type' => 'select',
                        'value' => $this->get('allow_gateway_selection'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'security_settings_selectable_gateways',
                'desc' => '',
                'fields' => [
                    'available_gateways' => [
                        'name' => 'available_gateways',
                        'type' => 'checkbox',
                        'value' => $this->getAvailableGateways($this->get('available_gateways')),
                        'choices' => $this->gateway_options,
                    ],
                ],
            ],
            [
                'title' => 'security_settings_cc_modulus_checking',
                'desc' => 'security_settings_cc_modulus_description',
                'fields' => [
                    'modulus_10_checking' => [
                        'name' => 'modulus_10_checking',
                        'type' => 'select',
                        'value' => $this->get('modulus_10_checking'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'gateways_format',
                'desc' => 'gateways_format_description',
                'fields' => [
                    'gateways_format' => [
                        'name' => 'gateways_format',
                        'type' => 'select',
                        'value' => $this->get('gateways_format'),
                        'required' => true,
                        'choices' => $this->gateways_format_options,
                    ],
                ],
            ],
        ];

        $form = [$form];

        return $form;
    }

    /**
     * Converts the CartThrob gateways array into a _shared/form
     *  compatible array
     * @param $gateways
     * @return array
     */
    public function getAvailableGateways($gateways): array
    {
        $return = [];
        if (!is_array($gateways) || !$gateways) {
            return $return;
        }

        foreach ($gateways as $key => $value) {
            $return[] = $key;
        }

        return $return;
    }

    /**
     * @param array $gateways
     * @return $this
     */
    public function setPaymentGateways(array $gateways): Payments
    {
        $this->gateways = $gateways;
        foreach ($gateways as $gateway) {
            $this->gateway_options[$gateway['classname']] = $gateway['title'];
        }

        return $this;
    }

    /**
     * @param array $gateways
     * @return $this
     */
    public function setEnabledGateways(array $gateways): Payments
    {
        foreach ($gateways as $gateway) {
            $this->enabled_gateways[$gateway] = lang($gateway);
            if (isset($this->gateway_options[$gateway])) {
                $this->enabled_gateways[$gateway] = $this->gateway_options[$gateway];
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getEnabledGateways(): array
    {
        return $this->enabled_gateways;
    }
}
