<?php

namespace CartThrob\Forms\Settings\Products;

use CartThrob\Forms\AbstractForm;

class RemoveChannel extends AbstractForm
{
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
                'title' => 'ct.route.total_products',
                'desc' => 'ct.route.total_products.note',
                'fields' => [
                    'plan_id' => [
                        'type' => 'html',
                        'content' => $this->getTotalProducts(),
                    ],
                ],
            ],
            [
                'title' => 'ct.route.confirm_removal',
                'desc' => 'ct.route.confirm_removal.note',
                'caution' => true,
                'fields' => [
                    'confirm' => [
                        'name' => 'confirm',
                        'short_name' => 'confirm',
                        'type' => 'yes_no',
                    ],
                ],
            ],
        ];

        $form = [$form];

        return $form;
    }

    protected function getTotalProducts()
    {
        $channel = ee('Model')
            ->get('Channel')
            ->filter('channel_id', $this->channel_id)
            ->first();

        return $channel->Entries->count();
    }
}
