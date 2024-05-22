<?php

namespace CartThrob\Forms\Settings\Orders;

use CartThrob\Exceptions\Forms\AbstractFormExceptions;
use CartThrob\Forms\AbstractForm;

class SetChannel extends AbstractForm
{
    protected $rules = [
        'orders_channel' => 'required',
    ];

    public function generate(): array
    {
        $form = [
            [
                'title' => 'ct.route.channel_name',
                'desc' => 'the_channel_that_stores_orders_details',
                'caution' => true,
                'fields' => [
                    'orders_channel' => [
                        'name' => 'orders_channel',
                        'type' => 'select',
                        'value' => $this->get('orders_channel'),
                        'choices' => $this->getUsableOrdersChannels(),
                        'no_results' => [
                            'text' => lang('ct.route.nothing_here'),
                            'link_href' => ee('CP/URL')->make('addons/settings/cartthrob/install')->compile(),
                            'link_text' => lang('ct.route.add_channel'),
                        ],
                    ],
                ],
            ],
        ];

        $form = ['orders_channel_form_description' => $form];

        return $form;
    }

    /**
     * We only want to use Channels that haven't been already assigned to other tasks
     * so we filter all that stuff out
     * @return array
     * @throws AbstractFormExceptions
     */
    protected function getUsableOrdersChannels()
    {
        $used = [];
        if ($this->settings()->get('cartthrob', 'product_channels')) {
            $used = array_merge($used, $this->settings()->get('cartthrob', 'product_channels'));
        }

        $check = ['discount_channel', 'purchased_items_channel', 'coupon_code_channel', 'product_channel'];
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
