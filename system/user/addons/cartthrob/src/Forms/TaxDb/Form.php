<?php

namespace CartThrob\Forms\TaxDb;

use CartThrob\Forms\AbstractForm;

abstract class Form extends AbstractForm
{
    public function generate(): array
    {
        $form = [
            [
                'title' => 'tax_name',
                'desc' => '',
                'fields' => [
                    'tax_name' => [
                        'name' => 'tax_name',
                        'type' => 'text',
                        'value' => $this->get('tax_name'),
                    ],
                ],
            ],
            [
                'title' => 'tax_percent',
                'desc' => '',
                'fields' => [
                    'percent' => [
                        'name' => 'percent',
                        'type' => 'text',
                        'value' => $this->get('percent'),
                    ],
                ],
            ],
            [
                'title' => 'tax_country',
                'desc' => '',
                'fields' => [
                    'country' => [
                        'name' => 'country',
                        'type' => 'select',
                        'required' => true,
                        'choices' => $this->getCountryOptions(),
                        'value' => $this->get('country'),
                    ],
                ],
            ],
            [
                'title' => 'tax_state',
                'desc' => '',
                'fields' => [
                    'state' => [
                        'name' => 'state',
                        'type' => 'select',
                        'required' => true,
                        'choices' => $this->getStateOptions(),
                        'value' => $this->get('state'),
                    ],
                ],
            ],
            [
                'title' => 'tax_zip',
                'desc' => '',
                'fields' => [
                    'zip' => [
                        'name' => 'zip',
                        'type' => 'text',
                        'value' => $this->get('zip'),
                    ],
                ],
            ],
            [
                'title' => 'tax_shipping',
                'desc' => '',
                'fields' => [
                    'shipping_is_taxable' => [
                        'name' => 'shipping_is_taxable',
                        'type' => 'select',
                        'value' => $this->get('shipping_is_taxable'),
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
