<?php

namespace CartThrob\Tags;

use EE_Session;

class OrderItemsTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('number');
        ee()->load->model(['order_model', 'product_model', 'cartthrob_entries_model']);
    }

    public function process()
    {
        $order_ids = $this->explodeParam('order_id');
        $entry_ids = $this->explodeParam('entry_id');
        $member_ids = $this->param('member_id') ?
            explode('|', str_replace(
                ['CURRENT_USER', '{logged_in_member_id}', '{member_id}'],
                $this->getMemberId(),
                $this->param('member_id')
            )) :
            false;

        $data = ee()->order_model->getOrderItems($order_ids, $entry_ids, $member_ids);

        if (!$data) {
            return ee()->TMPL->no_results();
        }

        ee()->load->library('api');

        ee()->legacy_api->instantiate('channel_fields');

        ee()->api_channel_fields->include_handler('cartthrob_order_items');

        if (!ee()->api_channel_fields->setup_handler('cartthrob_order_items')) {
            return '';
        }

        if ($this->hasParam('variable_prefix')) {
            ee()->api_channel_fields->field_types['cartthrob_order_items']->variable_prefix = $this->param('variable_prefix');
        }

        ee()->api_channel_fields->apply('pre_process', [$data]);

        $return_data = ee()->api_channel_fields->apply('replace_tag', [$data, $this->params(), $this->tagdata()]);

        loadCartThrobPath();

        return $return_data;
    }
}
