<?php

namespace CartThrob\Actions;

use CartThrob\Request\Request;
use Cartthrob_core;
use EE_Session;
use Exception;

class ConsumeAsyncJobAction extends Action
{
    public function __construct(EE_Session $session, Request $request)
    {
        parent::__construct($session, $request);

        ee()->load->model(['async_job_model', 'order_model']);
    }

    public function process()
    {
        ignore_user_abort(true);

        $limit = (int)$this->request->input('limit', 1);

        foreach (ee()->async_job_model->fetch($limit) as $job) {
            try {
                $_POST = $job['post'] ?? [];
                $order_id = $job['payload']['order']['order_id'];

                $items = array_map(function ($item) {
                    return Cartthrob_core::create_child(ee()->cartthrob, 'item_' . $item['class'], $item, ee()->cartthrob->item_defaults);
                }, $job['payload']['order']['items']);

                ee()->order_model->addItemsToOrderItemsTable($order_id, $items);
                ee()->order_model->updateOrder($order_id, $job['payload']['order']);
                $order = ee()->order_model->get_order_from_entry($order_id);

                ee()->load->model('Cart_model');
                ee()->cartthrob->cart->clear_items(); // remove any existing items
                ee()->cartthrob->cart->set_order($order);
                if (isset($order['items']) && is_array($order['items'])) {
                    foreach ($order['items'] as $item) {
                        $new_item = ee()->cartthrob->cart->add_item($item);
                        if ($new_item) {
                            if (element('license_number', $item)) {
                                $new_item->set_meta('license_number', true);
                            }
                        }
                    }
                }
                ee()->cartthrob->cart->save();

                ee()->load->library('cartthrob_payments');
                ee()->cartthrob_payments->checkoutComplete($job['state'], null, null, true);

                ee()->async_job_model->delete($job['id']);
            } catch (Exception $e) {
                ee()->async_job_model->update($job, $e->getMessage());
            }
        }
    }
}
