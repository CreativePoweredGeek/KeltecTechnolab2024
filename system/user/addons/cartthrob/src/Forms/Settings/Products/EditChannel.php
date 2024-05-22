<?php

namespace CartThrob\Forms\Settings\Products;

use CartThrob\Forms\AbstractForm;

class EditChannel extends AbstractForm
{
    protected $rules = [
        'global_price' => 'isNatural',
    ];

    public function generate(): array
    {
        $form = [
            [
                'title' => 'ct.route.channel_name',
                'desc' => 'ct.route.the_channel_that_stores_product_details',
                'caution' => true,
                'fields' => [
                    'sub_id' => [
                        'type' => 'html',
                        'content' => '(' . $this->getChannelId() . ') ' . $this->getChannelTitle(),
                    ],
                ],
            ],
            [
                'title' => 'product_channel_price_field',
                'desc' => 'product_channel_price_field_description',
                'fields' => [
                    'price' => [
                        'name' => 'price',
                        'type' => 'select',
                        'value' => $this->get('price'),
                        'choices' => $this->getChannelFieldOptions(),
                    ],
                ],
            ],
            [
                'title' => 'product_channel_shipping_field',
                'desc' => 'product_channel_shipping_field_description',
                'fields' => [
                    'shipping' => [
                        'name' => 'shipping',
                        'type' => 'select',
                        'value' => $this->get('shipping'),
                        'choices' => $this->getChannelFieldOptions(),
                    ],
                ],
            ],
            [
                'title' => 'product_channel_weight_field',
                'desc' => 'product_channel_weight_field',
                'fields' => [
                    'weight' => [
                        'name' => 'weight',
                        'type' => 'select',
                        'value' => $this->get('weight'),
                        'choices' => $this->getChannelFieldOptions(),
                    ],
                ],
            ],
            [
                'title' => 'product_channel_inventory_field',
                'desc' => 'product_channel_inventory_field_description',
                'fields' => [
                    'inventory' => [
                        'name' => 'inventory',
                        'type' => 'select',
                        'value' => $this->get('inventory'),
                        'choices' => $this->getChannelFieldOptions(),
                    ],
                ],
            ],
            [
                'title' => 'product_channel_global_price',
                'desc' => 'product_channel_global_price_description',
                'fields' => [
                    'global_price' => [
                        'name' => 'global_price',
                        'type' => 'text',
                        'value' => $this->get('global_price'),
                    ],
                ],
            ],
        ];

        $form = ['product_channel_form_description' => $form];

        return $form;
    }
}
