<?php

namespace CartThrob\Forms\Settings\Products;

use CartThrob\Exceptions\Forms\AbstractFormExceptions;
use CartThrob\Forms\AbstractForm;

class AddChannel extends AbstractForm
{
    protected $rules = [
        'product_channel' => 'required',
    ];

    public function generate(): array
    {
        $form = [
            [
                'title' => 'ct.route.channel_name',
                'desc' => 'ct.route.the_channel_that_stores_product_details',
                'caution' => true,
                'fields' => [
                    'product_channel' => [
                        'name' => 'product_channel',
                        'type' => 'select',
                        'value' => $this->get('product_channel'),
                        'choices' => $this->getUsableProductChannels(),
                        'no_results' => [
                            'text' => lang('ct.route.nothing_here'),
                            'link_href' => ee('CP/URL')->make('channels/create')->compile(),
                            'link_text' => lang('ct.route.add_channel'),
                        ],
                    ],
                ],
            ],
        ];

        $form = ['product_channel_form_description' => $form];

        return $form;
    }

    /**
     * We only want to use Channels that haven't been already assigned to other tasks
     * so we filter all that stuff out
     * @return array
     * @throws AbstractFormExceptions
     */
    protected function getUsableProductChannels()
    {
        $used = [];
        if ($this->settings()->get('cartthrob', 'product_channels')) {
            $used = array_merge($used, $this->settings()->get('cartthrob', 'product_channels'));
        }

        $check = ['orders_channel', 'discount_channel', 'coupon_code_channel', 'purchased_items_channel'];
        foreach ($check as $item) {
            if ($this->settings()->get('cartthrob', $item)) {
                $used[] = $this->settings()->get('cartthrob', $item);
            }
        }

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
