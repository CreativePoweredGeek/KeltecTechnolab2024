<?php

namespace CartThrob\Forms\Settings;

use CartThrob\Forms\AbstractForm;

class Taxes extends AbstractForm
{
    public function generate(): array
    {
        $form = [
            [
                'title' => 'tax_use_shipping_address',
                'desc' => '',
                'fields' => [
                    'tax_use_shipping_address' => [
                        'name' => 'tax_use_shipping_address',
                        'type' => 'select',
                        'value' => $this->get('tax_use_shipping_address'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'tax_rounding_options',
                'desc' => 'tax_rounding_options_note',
                'fields' => [
                    'round_tax_only_on_subtotal' => [
                        'name' => 'round_tax_only_on_subtotal',
                        'type' => 'select',
                        'value' => $this->get('round_tax_only_on_subtotal'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
        ];

        $form = [$form];

        return $form;
    }
}
