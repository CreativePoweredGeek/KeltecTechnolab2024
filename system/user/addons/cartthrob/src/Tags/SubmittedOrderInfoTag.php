<?php

namespace CartThrob\Tags;

use CartThrob\Dependency\Illuminate\Support\Arr;
use EE_Session;

class SubmittedOrderInfoTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->model(['cartthrob_entries_model', 'order_model']);
    }

    public function process()
    {
        $data = ee()->cartthrob->cart->order();

        if (!$data) {
            return $this->parseVariables([]);
        }

        foreach ($data as $i => $row) {
            // what's happening here:
            // not all of the data from cart->order() is suitable to be passed to parse_variables
            // particularly arrays of data that don't contain arrays
            // remove them.
            if (is_array($row) && count($row) > 0 && !is_array(current($row))) {
                if ($i === 'custom_data') {
                    foreach ($row as $key => $value) {
                        $data['custom_data:' . $key] = $value;
                    }
                }

                unset($data[$i]);
            }
        }

        $data = array_merge($data, array_key_prefix($data, 'cart_'));

        if (!empty($data['order_id'])) {
            if ($order = ee()->order_model->getOrder($data['order_id'])) {
                $status = ee()->order_model->getOrderStatus($data['order_id']);

                if (ee()->has('coilpack')) {
                    $data['authorized'] = $data['declined'] = $data['failed'] = $data['processing'] = false;
                }

                switch ($status) {
                    case 'authorized':
                    case 'completed':
                        $data['authorized'] = true;
                        break;
                    case 'declined':
                        $data['declined'] = true;
                        break;
                    case 'failed':
                    case 'refunded':
                    case 'expired':
                    case 'reversed':
                    case 'canceled':
                    case 'voided':
                        $data['failed'] = true;
                        break;
                    default:
                        $data['processing'] = true;
                }

                $data['transaction_id'] = Arr::get($data, 'auth.transaction_id', ee()->order_model->getOrderTransactionId($data['order_id']));
                $data['error_message'] = Arr::get($data, 'auth.error_message', ee()->order_model->getOrderErrorMessage($data['order_id']));
                $data = array_merge(ee()->cartthrob_entries_model->entry_vars($order), $data);
            }
        }

        // Add total items in order
        $data['order_items:total_results'] = count($data['items']);

        // this needs to remain just before variable parsing so that any scripts above are not affected by removing data keys
        foreach ($data as $i => $row) {
            // what's happening here:
            // not all of the data from cart->order() is suitable to be passed to parse_variables
            // particularly arrays of data that don't contain arrays
            // remove them.
            if (is_array($row) && count($row) > 0 && !is_array(current($row))) {
                if ($i === 'custom_data') {
                    foreach ($row as $key => $value) {
                        $data['custom_data:' . $key] = $value;
                    }
                }

                unset($data[$i]);
            }
        }

        return $this->parseVariablesRow($data);
    }
}
