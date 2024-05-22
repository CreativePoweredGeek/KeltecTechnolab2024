<?php

namespace CartThrob\Forms\Settings;

use CartThrob\Forms\AbstractForm;

class Products extends AbstractForm
{
    /**
     * The Validation rules that'll be ran
     * @var string[]
     */
    protected $rules = [
        'allow_products_more_than_once' => 'required',
        'product_split_items_by_quantity' => 'required',
        'send_inventory_email' => 'required',
        'low_stock_level' => 'required',
        'global_item_limit' => 'required|isNatural',
    ];

    public function generate(): array
    {
        $form = [
            [
                'title' => 'product_allow_duplicate_items',
                'desc' => 'product_allow_duplicate_instructions',
                'fields' => [
                    'allow_products_more_than_once' => [
                        'name' => 'allow_products_more_than_once',
                        'type' => 'select',
                        'value' => $this->get('allow_products_more_than_once'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'product_split_items_by_quantity',
                'desc' => 'product_split_items_by_quantity_instructions',
                'fields' => [
                    'product_split_items_by_quantity' => [
                        'name' => 'product_split_items_by_quantity',
                        'type' => 'select',
                        'value' => $this->get('product_split_items_by_quantity'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'email_low_stock_send_warning',
                'desc' => '',
                'fields' => [
                    'send_inventory_email' => [
                        'name' => 'send_inventory_email',
                        'type' => 'select',
                        'value' => $this->get('send_inventory_email'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'low_stock_value',
                'desc' => '',
                'fields' => [
                    'low_stock_level' => [
                        'name' => 'low_stock_level',
                        'type' => 'text',
                        'value' => $this->get('low_stock_level'),
                    ],
                ],
            ],
            [
                'title' => 'global_settings_quantity_limit',
                'desc' => 'global_settings_quantity_description',
                'fields' => [
                    'global_item_limit' => [
                        'name' => 'global_item_limit',
                        'type' => 'text',
                        'required' => true,
                        'value' => $this->get('global_item_limit'),
                    ],
                ],
            ],
            [
                'title' => 'global_settings_allow_fractional_quantities',
                'desc' => '',
                'fields' => [
                    'allow_fractional_quantities' => [
                        'name' => 'allow_fractional_quantities',
                        'type' => 'select',
                        'value' => $this->get('allow_fractional_quantities'),
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
