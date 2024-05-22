<?php

namespace CartThrob\Forms\Settings;

use CartThrob\Forms\AbstractForm;

class TemplateVariables extends AbstractForm
{
    public function generate(): array
    {
        $form = [
            [
                'title' => 'template_var_store_name',
                'desc' => '',
                'fields' => [
                    'store_name' => [
                        'name' => 'store_name',
                        'type' => 'text',
                        'required' => true,
                        'value' => $this->get('store_name'),
                    ],
                ],
            ],
            [
                'title' => 'template_var_store_address1',
                'desc' => '',
                'fields' => [
                    'store_address1' => [
                        'name' => 'store_address1',
                        'type' => 'text',
                        'value' => $this->get('store_address1'),
                    ],
                ],
            ],
            [
                'title' => 'template_var_store_city',
                'desc' => '',
                'fields' => [
                    'store_city' => [
                        'name' => 'store_city',
                        'type' => 'text',
                        'value' => $this->get('store_city'),
                    ],
                ],
            ],
            [
                'title' => 'template_var_store_state',
                'desc' => '',
                'fields' => [
                    'store_state' => [
                        'name' => 'store_state',
                        'type' => 'text',
                        'value' => $this->get('store_state'),
                    ],
                ],
            ],
            [
                'title' => 'template_var_store_zip',
                'desc' => '',
                'fields' => [
                    'store_zip' => [
                        'name' => 'store_zip',
                        'type' => 'text',
                        'value' => $this->get('store_zip'),
                    ],
                ],
            ],
            [
                'title' => 'template_var_store_country',
                'desc' => '',
                'fields' => [
                    'store_country' => [
                        'name' => 'store_country',
                        'type' => 'text',
                        'value' => $this->get('store_country'),
                    ],
                ],
            ],
            [
                'title' => 'template_var_store_phone',
                'desc' => '',
                'fields' => [
                    'store_phone' => [
                        'name' => 'store_phone',
                        'type' => 'text',
                        'value' => $this->get('store_phone'),
                    ],
                ],
            ],
            [
                'title' => 'template_var_store_description',
                'desc' => '',
                'fields' => [
                    'store_description' => [
                        'name' => 'store_description',
                        'type' => 'text',
                        'value' => $this->get('store_description'),
                    ],
                ],
            ],
            [
                'title' => 'template_var_store_shipping_estimate',
                'desc' => '',
                'fields' => [
                    'store_shipping_estimate' => [
                        'name' => 'store_shipping_estimate',
                        'type' => 'text',
                        'value' => $this->get('store_shipping_estimate'),
                    ],
                ],
            ],
            [
                'title' => 'template_var_store_about_us',
                'desc' => '',
                'fields' => [
                    'store_about_us' => [
                        'name' => 'store_about_us',
                        'type' => 'textarea',
                        'value' => $this->get('store_about_us'),
                    ],
                ],
            ],
            [
                'title' => 'template_var_store_google_code',
                'desc' => '',
                'fields' => [
                    'store_google_code' => [
                        'name' => 'store_google_code',
                        'type' => 'text',
                        'value' => $this->get('store_google_code'),
                    ],
                ],
            ],
        ];

        return [$form];
    }
}
