<?php

namespace CartThrob\Forms\Settings\PurchasedItems;

use CartThrob\Exceptions\Forms\AbstractFormExceptions;
use CartThrob\Forms\AbstractForm;

class SetChannel extends AbstractForm
{
    protected $rules = [
        'purchased_items_channel' => 'required',
    ];

    public function generate(): array
    {
        $form = [
            [
                'title' => 'ct.route.channel_name',
                'desc' => 'ct.route.the_channel_that_stores_purchased_item_details',
                'caution' => true,
                'fields' => [
                    'purchased_items_channel' => [
                        'name' => 'purchased_items_channel',
                        'type' => 'select',
                        'value' => $this->get('purchased_items_channel'),
                        'choices' => $this->getUsablePurchaseItemsChannels(),
                    ],
                ],
            ],
        ];

        $form = ['purchased_items_channel_form_description' => $form];

        return $form;
    }

    /**
     * We only want to use Channels that haven't been already assigned to other tasks
     * so we filter all that stuff out
     * @return array
     * @throws AbstractFormExceptions
     */
    protected function getUsablePurchaseItemsChannels()
    {
        $used = [];
        if ($this->settings()->get('cartthrob', 'product_channels')) {
            $used = array_merge($used, $this->settings()->get('cartthrob', 'product_channels'));
        }

        $check = ['orders_channel', 'discount_channel', 'coupon_code_channel', 'product_channel'];
        foreach ($check as $item) {
            if ($this->settings()->get('cartthrob', $item)) {
                $used[] = $this->settings()->get('cartthrob', $item);
            }
        }

        $this->settings()->get('cartthrob', 'purchased_items_channel');
        $channels = ee('Model')
            ->get('Channel')
            ->filter('channel_id', 'NOT IN', $used);

        $return = [];
        if ($channels->count() >= 1) {
            foreach ($channels->all() as $channel) {
                $return[$channel->channel_id] = $channel->channel_title;
            }
        }

        return $return;
    }
}
