<?php

use CartThrob\Dependency\Omnipay\Common\CreditCard;
use CartThrob\Traits\OrderTrait;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @property mixed config
 */
class Order_model extends CI_Model
{
    use OrderTrait;

    public function __construct()
    {
        $this->load->model('cartthrob_field_model');
        $this->load->model('cartthrob_entries_model');
        $this->load->model('cartthrob_settings_model');
    }

    /**
     * @param $id
     * @param $data
     * @return bool
     */
    public function updateOrder($id, $data)
    {
        if (!$this->config->item('cartthrob:orders_channel')) {
            return false;
        }

        return $this->cartthrob_entries_model->update($id, $this->convertOrderData($data));
    }

    /**
     * @param $order
     * @return mixed
     */
    private function convertOrderData($order)
    {
        $this->load->library('locales');

        $customData = $this->cartthrob->cart->custom_data();
        $fields = $this->cartthrob_field_model->get_fields_by_channel($this->config->item('cartthrob:orders_channel'));

        foreach ($fields as $field) {
            if ($this->input->post($field['field_name']) !== false) {
                $order['field_id_' . $field['field_id']] = $this->input->post($field['field_name'], true);
            }

            if (isset($customData[$field['field_name']])) {
                $order['field_id_' . $field['field_id']] = $customData[$field['field_name']];
            }

            if (isset($order[$field['field_id']])) {
                $order['field_id_' . $field['field_id']] = $order[$field['field_id']];
            }
        }

        if ($this->config->item('cartthrob:orders_subtotal_field') && isset($order['subtotal'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_subtotal_field')] = $order['subtotal'];
        }
        if ($this->config->item('cartthrob:orders_subtotal_plus_tax_field') && isset($order['subtotal_plus_tax'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_subtotal_plus_tax_field')] = $order['subtotal_plus_tax'];
        }
        if ($this->config->item('cartthrob:orders_tax_field') && isset($order['tax'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_tax_field')] = $order['tax'];
        }
        if ($this->config->item('cartthrob:orders_shipping_field') && isset($order['shipping'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_shipping_field')] = $order['shipping'];
        }
        if ($this->config->item('cartthrob:orders_shipping_plus_tax_field') && isset($order['shipping_plus_tax'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_shipping_plus_tax_field')] = $order['shipping_plus_tax'];
        }
        if ($this->config->item('cartthrob:orders_total_field') && isset($order['total'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_total_field')] = $order['total'];
        }
        if ($this->config->item('cartthrob:orders_discount_field') && isset($order['discount'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_discount_field')] = $order['discount'];
        }
        if ($this->config->item('cartthrob:orders_coupon_codes') && isset($order['coupon_codes'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_coupon_codes')] = $order['coupon_codes'];
        }
        if ($this->config->item('cartthrob:orders_last_four_digits') && isset($order['last_four_digits'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_last_four_digits')] = $order['last_four_digits'];
        }
        if ($this->config->item('cartthrob:orders_transaction_id') && isset($order['transaction_id'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_transaction_id')] = $order['transaction_id'];
        }
        if ($this->config->item('cartthrob:orders_customer_name') && isset($order['customer_name'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_customer_name')] = $order['customer_name'];
        }
        if ($this->config->item('cartthrob:orders_customer_email') && isset($order['customer_email'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_customer_email')] = $order['customer_email'];
        }
        if ($this->config->item('cartthrob:orders_customer_ip_address') && isset($order['customer_ip_address'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_customer_ip_address')] = $order['customer_ip_address'];
        }
        if ($this->config->item('cartthrob:orders_customer_phone') && isset($order['customer_phone'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_customer_phone')] = $order['customer_phone'];
        }
        if ($this->config->item('cartthrob:orders_full_billing_address') && isset($order['full_billing_address'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_full_billing_address')] = $order['full_billing_address'];
        }
        if ($this->config->item('cartthrob:orders_billing_first_name') && isset($order['billing_first_name'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_billing_first_name')] = $order['billing_first_name'];
        }
        if ($this->config->item('cartthrob:orders_billing_last_name') && isset($order['billing_last_name'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_billing_last_name')] = $order['billing_last_name'];
        }
        if ($this->config->item('cartthrob:orders_billing_company') && isset($order['billing_company'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_billing_company')] = $order['billing_company'];
        }
        if ($this->config->item('cartthrob:orders_billing_address') && isset($order['billing_address'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_billing_address')] = $order['billing_address'];
        }
        if ($this->config->item('cartthrob:orders_billing_address2') && isset($order['billing_address2'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_billing_address2')] = $order['billing_address2'];
        }
        if ($this->config->item('cartthrob:orders_billing_city') && isset($order['billing_city'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_billing_city')] = $order['billing_city'];
        }
        if ($this->config->item('cartthrob:orders_billing_state') && isset($order['billing_state'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_billing_state')] = $order['billing_state'];
        }
        if ($this->config->item('cartthrob:orders_billing_zip') && isset($order['billing_zip'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_billing_zip')] = $order['billing_zip'];
        }
        if ($this->config->item('cartthrob:orders_billing_country')) {
            if ($this->config->item('cartthrob:orders_convert_country_code')) {
                if (isset($order['billing_country_code'])) {
                    $order['billing_country'] = $this->locales->country_from_country_code($order['billing_country_code']);
                }
            }

            if (isset($order['billing_country'])) {
                $order['field_id_' . $this->config->item('cartthrob:orders_billing_country')] = $order['billing_country'];
            }
        }
        if ($this->config->item('cartthrob:orders_country_code') && isset($order['country_code'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_country_code')] = $order['country_code'];
        }
        if ($this->config->item('cartthrob:orders_full_shipping_address') && isset($order['full_shipping_address'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_full_shipping_address')] = $order['full_shipping_address'];
        }
        if ($this->config->item('cartthrob:orders_shipping_first_name') && isset($order['shipping_first_name'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_shipping_first_name')] = $order['shipping_first_name'];
        }
        if ($this->config->item('cartthrob:orders_shipping_last_name') && isset($order['shipping_last_name'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_shipping_last_name')] = $order['shipping_last_name'];
        }
        if ($this->config->item('cartthrob:orders_shipping_phone') && isset($order['shipping_phone'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_shipping_phone')] = $order['shipping_phone'];
        }
        if ($this->config->item('cartthrob:orders_shipping_company') && isset($order['shipping_company'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_shipping_company')] = $order['shipping_company'];
        }
        if ($this->config->item('cartthrob:orders_shipping_address') && isset($order['shipping_address'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_shipping_address')] = $order['shipping_address'];
        }
        if ($this->config->item('cartthrob:orders_shipping_address2') && isset($order['shipping_address2'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_shipping_address2')] = $order['shipping_address2'];
        }
        if ($this->config->item('cartthrob:orders_shipping_city') && isset($order['shipping_city'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_shipping_city')] = $order['shipping_city'];
        }
        if ($this->config->item('cartthrob:orders_shipping_state') && isset($order['shipping_state'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_shipping_state')] = $order['shipping_state'];
        }
        if ($this->config->item('cartthrob:orders_shipping_zip') && isset($order['shipping_zip'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_shipping_zip')] = $order['shipping_zip'];
        }
        if ($this->config->item('cartthrob:orders_shipping_country')) {
            if ($this->config->item('cartthrob:orders_convert_country_code')) {
                if (isset($order['shipping_country_code'])) {
                    $order['shipping_country'] = $this->locales->country_from_country_code($order['shipping_country_code']);
                }
            }

            if (isset($order['shipping_country'])) {
                $order['field_id_' . $this->config->item('cartthrob:orders_shipping_country')] = $order['shipping_country'];
            }
        }
        if ($this->config->item('cartthrob:orders_shipping_country_code') && isset($order['shipping_country_code'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_shipping_country_code')] = $order['shipping_country_code'];
        }
        if ($this->config->item('cartthrob:orders_shipping_option') && isset($order['shipping_option'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_shipping_option')] = $order['shipping_option'];
        }
        if ($this->config->item('cartthrob:orders_error_message_field') && isset($order['error_message'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_error_message_field')] = $order['error_message'];
        }
        if ($this->config->item('cartthrob:orders_language_field')) {
            $order['field_id_' . $this->config->item('cartthrob:orders_language_field')] = ($this->input->cookie('language')) ? $this->input->cookie('language',
                true) : $this->session->userdata('language');
        }
        if ($this->config->item('cartthrob:orders_payment_gateway') && isset($order['payment_gateway'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_payment_gateway')] = $order['payment_gateway'];
        }
        if ($this->config->item('cartthrob:orders_site_id') && isset($order['site_id'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_site_id')] = $order['site_id'];
        }
        if ($this->config->item('cartthrob:orders_subscription_id') && isset($order['subscription_id'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_subscription_id')] = $order['subscription_id'];
        }
        if ($this->config->item('cartthrob:orders_vault_id') && isset($order['vault_id'])) {
            $order['field_id_' . $this->config->item('cartthrob:orders_vault_id')] = $order['vault_id'];
        }

        return $order;
    }

    /**
     * @param $entry_ids
     */
    public function delete_order_items($entry_ids)
    {
        if (!is_array($entry_ids)) {
            $entry_ids = [$entry_ids];
        }

        $order_items = $this->getOrderItems($entry_ids);

        foreach ($order_items as $row) {
            if ($this->config->item('cartthrob:update_inventory_when_editing_order')) {
                $new_row = $row;

                $new_row['quantity'] = 0;

                $this->update_product_inventory($new_row, $row);
            }

            $this->delete_order_item($row['row_id']);
        }
    }

    /**
     * @param $order_ids
     * @param array $entry_ids
     * @param array $member_ids
     * @param bool $keep_extra
     * @return mixed
     */
    public function getOrderItems($order_ids, $entry_ids = [], $member_ids = [], $keep_extra = false)
    {
        if ($order_ids) {
            if (!is_array($order_ids)) {
                $this->db->where('order_id', $order_ids);
            } else {
                $this->db->where_in('order_id', $order_ids);
            }
        }

        if ($entry_ids) {
            if (!is_array($entry_ids)) {
                $this->db->where('cartthrob_order_items.entry_id', $entry_ids);
            } else {
                $this->db->where_in('cartthrob_order_items.entry_id', $entry_ids);
            }
        }

        if ($member_ids) {
            $this->db->select('cartthrob_order_items.*')->join('channel_titles',
                'channel_titles.entry_id = cartthrob_order_items.order_id');

            if (!is_array($member_ids)) {
                $this->db->where('channel_titles.author_id', $member_ids);
            } else {
                $this->db->where_in('channel_titles.author_id', $member_ids);
            }
        }

        $query = $this->db->order_by('order_id, row_order', 'asc')->get('cartthrob_order_items');

        $order_items = $query->result_array();

        $query->free_result();

        foreach ($order_items as &$row) {
            $extra = _unserialize($row['extra'], true);

            if ($keep_extra) {
                $row['extra'] = $extra;
            } else {
                foreach ($extra as $key => $value) {
                    if (!isset($row[$key])) {
                        $row[$key] = $value;
                    }
                }

                unset($row['extra']);
            }
        }

        return $order_items;
    }

    /**
     * @param $row
     * @param $original_row
     * @param bool $new_item
     */
    protected function update_product_inventory($row, $original_row, $new_item = false)
    {
        if (empty($row['entry_id'])) {
            return;
        }

        $difference = $row['quantity'] - $original_row['quantity'];

        if ($new_item) {
            $difference = $row['quantity'];
            if (!empty($original_row['extra'])) {
                $opts = _unserialize($original_row['extra']);
                $original_row = array_merge($row, $opts);
            }
        }
        if ($difference === 0) {
            return;
        }

        $default_keys = [
            'row_id',
            'row_order',
            'order_id',
            'entry_id',
            'title',
            'quantity',
            'price',
            'price_plus_tax',
            'weight',
            'shipping',
            'no_tax',
            'no_shipping',
            'site_id',
        ];

        $item_options = array_diff_key($original_row, array_flip($default_keys));

        $this->load->model('product_model');

        $this->product_model->reduce_inventory($row['entry_id'], $difference, $item_options);
    }

    /**
     * @param $row_id
     */
    public function delete_order_item($row_id)
    {
        $this->db->delete('cartthrob_order_items', ['row_id' => $row_id]);
    }

    /**
     * @param $id
     */
    public function get_sub_id($id)
    {
        $query = $this->db->select('sub_id')->from('cartthrob_subscriptions')->where('id', $id)->limit(1)->get();

        if ($query->num_rows()) {
            return $query->row('sub_id');
        }

        return null;
    }

    /**
     * @param $entry_id
     * @return mixed
     */
    public function getOrderCartId($entry_id)
    {
        $statusModel = ee('Model')->get('cartthrob:CartthrobStatus')
            ->fields('cart_id')
            ->filter('entry_id', $entry_id)
            ->first();

        return $statusModel->cart_id;
    }

    /**
     * @param $session_id
     */
    public function getOrderIdFromSession($session_id)
    {
        $statusModel = ee('Model')->get('cartthrob:CartthrobStatus')
            ->fields('cart_id')
            ->filter('session_id', $session_id)
            ->first();

        return $statusModel->entry_id;
    }

    /**
     * @param $entry_id
     * @return bool
     */
    public function canUpdateOrder($entry_id)
    {
        $order = $this->getOrder($entry_id);

        if ($order === false) {
            return false;
        }

        if (in_array($this->session->userdata('group_id'), $this->config->item('cartthrob:admin_checkout_groups'))) {
            return true;
        }

        return $order['author_id'] == $this->session->userdata('member_id');
    }

    /**
     * @param $entry_id
     * @return mixed
     */
    public function getOrder($entry_id)
    {
        return $this->cartthrob_entries_model->entry($entry_id);
    }

    /**
     * This always needs to be direct from the database.
     * @param $entry_id
     * @return bool
     */
    public function getOrderTransactionId($entry_id)
    {
        $statusModel = ee('Model')->get('cartthrob:CartthrobStatus')
            ->fields('transaction_id')
            ->filter('entry_id', $entry_id)
            ->first();

        if ($statusModel && !empty($statusModel->transaction_id)) {
            return $statusModel->transaction_id;
        }

        return $this->get_status($entry_id, 'transaction_id', null);
    }

    /**
     * @param $entry_id
     * @param bool $key
     * @param bool $default
     * @return bool
     */
    public function get_status($entry_id, $key = false, $default = false)
    {
        if (!isset($this->session->cache['cartthrob'][__CLASS__][__FUNCTION__][$entry_id])) {
            $query = $this->db->where('entry_id', $entry_id)->limit(1)->get('cartthrob_status');

            if ($query->num_rows() === 0) {
                return false;
            }

            $this->session->cache['cartthrob'][__CLASS__][__FUNCTION__][$entry_id] = $query->row_array();

            $query->free_result();
        }

        $cache = &$this->session->cache['cartthrob'][__CLASS__][__FUNCTION__][$entry_id];

        if ($key !== false) {
            return (isset($cache[$key])) ? $cache[$key] : $default;
        }

        return $cache ? $cache : $default;
    }

    /**
     * @param $entry_id
     * @return bool
     */
    public function getOrderErrorMessage($entry_id)
    {
        return $this->get_status($entry_id, 'error_message', null);
    }

    /**
     * @param $entry_id
     * @param string $status
     * @return string
     */
    public function setOrderStatus($entry_id, $status = Cartthrob_payments::STATUS_PROCESSING)
    {
        $statuses = [
            Cartthrob_payments::STATUS_AUTHORIZED,
            Cartthrob_payments::STATUS_COMPLETED,
            Cartthrob_payments::STATUS_PROCESSING,
            Cartthrob_payments::STATUS_REVERSED,
            Cartthrob_payments::STATUS_REFUNDED,
            Cartthrob_payments::STATUS_VOIDED,
            Cartthrob_payments::STATUS_EXPIRED,
            Cartthrob_payments::STATUS_CANCELED,
            Cartthrob_payments::STATUS_FAILED,
            Cartthrob_payments::STATUS_DECLINED,
            Cartthrob_payments::STATUS_OFFSITE,
        ];

        if (!in_array($status, $statuses)) {
            $status = Cartthrob_payments::STATUS_PROCESSING;
        }

        $this->setStatus($entry_id, [
            'status' => $status,
        ]);

        return $status;
    }

    /**
     * @param $entry_id
     * @return bool
     */
    public function getOrderStatus($entry_id)
    {
        // this always needs to be direct from the database.
        // getting cached data... man, it really screws us up when the get_status function is used more than once to check, then set, then check again somewhere else.
        // this always needs to be direct from the database.
        $statusModel = ee('Model')->get('cartthrob:CartthrobStatus')
            ->fields('status')
            ->filter('entry_id', $entry_id)
            ->first();

        if ($statusModel && !empty($statusModel->status)) {
            return $statusModel->status;
        }

        return $this->get_status($entry_id, 'status', null);
    }

    /**
     * @param $entry_id
     * @param null $transaction_id
     */
    public function setOrderTransactionId($entry_id, $transaction_id = null)
    {
        if (!$transaction_id) {
            return;
        }

        $statusModel = $this->setStatus($entry_id, [
            'transaction_id' => $transaction_id,
        ]);

        return $statusModel->transaction_id;
    }

    /**
     * @param $entry_id
     * @param $error_message
     * @return null
     */
    public function setOrderErrorMessage($entry_id, $error_message = null)
    {
        if (!$error_message) {
            return null;
        }

        $statusModel = $this->setStatus($entry_id, [
            'error_message' => $error_message,
        ]);

        return $statusModel->error_message;
    }

    /**
     * @param $entry_id
     * @return array|null
     */
    public function getCartFromOrder($entry_id)
    {
        // this always needs to be direct from the database.
        $statusModel = ee('Model')->get('cartthrob:CartthrobStatus')
            ->fields('cart')
            ->filter('entry_id', $entry_id)
            ->first();

        if ($statusModel && !empty($statusModel->cart)) {
            return _unserialize(ee('Encrypt')->decode($statusModel->cart));
        }

        return null;
    }

    /**
     * @param $entry_id
     * @param bool $inventory_processed
     * @param bool $discounts_processed
     * @param null $cart
     * @param null $cart_id
     * @param null $session_id
     */
    public function saveCartSnapshot(
        $entry_id,
        $inventory_processed = false,
        $discounts_processed = false,
        $cart = null,
        $cart_id = null,
        $session_id = null
    ) {
        $data = [];

        if ($inventory_processed !== false) {
            $data['inventory_processed'] = $inventory_processed;
        }

        if ($discounts_processed !== false) {
            $data['discounts_processed'] = $discounts_processed;
        }

        if ($cart) {
            $data['cart'] = ee('Encrypt')->encode(serialize($cart));
        }

        if ($cart_id) {
            $data['cart_id'] = $cart_id;
        }

        if ($session_id) {
            $data['session_id'] = $session_id;
        }

        $this->setStatus($entry_id, $data);
    }

    /**
     * @param $member_id
     * @return mixed
     */
    public function getMemberLastOrder($member_id): mixed
    {
        return current($this->getMemberOrders($member_id));
    }

    /**
     * @param int $member_id
     * @param array $where
     * @return mixed
     */
    public function getMemberOrders(int $member_id, array $where = []): mixed
    {
        $where['author_id'] = $member_id;

        return $this->get_orders($where);
    }

    /**
     * @param $where
     * @return mixed
     */
    public function get_orders($where)
    {
        $where['channel_titles.channel_id'] = $this->config->item('cartthrob:orders_channel');

        return $this->cartthrob_entries_model->find_entries($where);
    }

    /**
     * Get a CartThrob compatible order array from a saved order
     *
     * @param int $entry_id the entry id of the order
     *
     * @return array use in conjunction with ee()->cartthrob->cart->set_order($data);
     */
    public function get_order_from_entry($entry_id)
    {
        $this->load->helper('array');
        $this->load->model('purchased_items_model');

        $entry = $this->getOrder($entry_id);

        $order_data = [
            'title' => element('title', $entry),
            'invoice_number' => element('title', $entry),
            'items' => [],
            'transaction_id' => element('field_id_' . $this->config->item('cartthrob:orders_transaction_id'), $entry),
            'card_type' => element('field_id_' . $this->config->item('cartthrob:orders_card_type'), $entry),
            'shipping' => element('field_id_' . $this->config->item('cartthrob:orders_shipping_field'), $entry),
            'shipping_plus_tax' => element('field_id_' . $this->config->item('cartthrob:orders_shipping_plus_tax_field'), $entry),
            'tax' => element('field_id_' . $this->config->item('cartthrob:orders_tax_field'), $entry),
            'subtotal' => element('field_id_' . $this->config->item('cartthrob:orders_subtotal_field'), $entry),
            'subtotal_plus_tax' => element('field_id_' . $this->config->item('cartthrob:orders_subtotal_plus_tax_field'), $entry),
            'discount' => element('field_id_' . $this->config->item('cartthrob:orders_discount_field'), $entry),
            'total' => element('field_id_' . $this->config->item('cartthrob:orders_total_field'), $entry),
            'customer_name' => element('field_id_' . $this->config->item('cartthrob:orders_customer_name'), $entry),
            'email_address' => element('field_id_' . $this->config->item('cartthrob:orders_customer_email'), $entry),
            'customer_email' => element('field_id_' . $this->config->item('cartthrob:orders_customer_email'), $entry),
            'customer_ip_address' => element('field_id_' . $this->config->item('cartthrob:orders_customer_ip_address'), $entry),
            'ip_address' => element('field_id_' . $this->config->item('cartthrob:orders_customer_ip_address'), $entry),
            'customer_phone' => element('field_id_' . $this->config->item('cartthrob:orders_customer_phone'), $entry),
            'coupon_codes' => element('field_id_' . $this->config->item('cartthrob:orders_coupon_codes'), $entry),
            'coupon_codes_array' => !empty($entry['field_id_' . $this->config->item('cartthrob:orders_coupon_codes')]) ? explode(',', $entry['field_id_' . $this->config->item('cartthrob:orders_coupon_codes')]) : [],
            'last_four_digits' => element('field_id_' . $this->config->item('cartthrob:orders_last_four_digits'), $entry),
            'full_billing_address' => element('field_id_' . $this->config->item('cartthrob:orders_full_billing_address'), $entry),
            'full_shipping_address' => element('field_id_' . $this->config->item('cartthrob:orders_full_shipping_address'), $entry),
            'billing_first_name' => element('field_id_' . $this->config->item('cartthrob:orders_billing_first_name'), $entry),
            'billing_last_name' => element('field_id_' . $this->config->item('cartthrob:orders_billing_last_name'), $entry),
            'billing_company' => element('field_id_' . $this->config->item('cartthrob:orders_billing_company'), $entry),
            'billing_address' => element('field_id_' . $this->config->item('cartthrob:orders_billing_address'), $entry),
            'billing_address2' => element('field_id_' . $this->config->item('cartthrob:orders_billing_address2'), $entry),
            'billing_city' => element('field_id_' . $this->config->item('cartthrob:orders_billing_city'), $entry),
            'billing_state' => element('field_id_' . $this->config->item('cartthrob:orders_billing_state'), $entry),
            'billing_zip' => element('field_id_' . $this->config->item('cartthrob:orders_billing_zip'), $entry),
            'billing_country' => element('field_id_' . $this->config->item('cartthrob:orders_billing_country'), $entry),
            'billing_country_code' => element('field_id_' . $this->config->item('cartthrob:orders_country_code'), $entry),
            'shipping_first_name' => element('field_id_' . $this->config->item('cartthrob:orders_shipping_first_name'), $entry),
            'shipping_last_name' => element('field_id_' . $this->config->item('cartthrob:orders_shipping_last_name'), $entry),
            'shipping_phone' => element('field_id_' . $this->config->item('cartthrob:orders_shipping_phone'), $entry),
            'shipping_company' => element('field_id_' . $this->config->item('cartthrob:orders_shipping_company'), $entry),
            'shipping_address' => element('field_id_' . $this->config->item('cartthrob:orders_shipping_address'), $entry),
            'shipping_address2' => element('field_id_' . $this->config->item('cartthrob:orders_shipping_address2'), $entry),
            'shipping_city' => element('field_id_' . $this->config->item('cartthrob:orders_shipping_city'), $entry),
            'shipping_state' => element('field_id_' . $this->config->item('cartthrob:orders_shipping_state'), $entry),
            'shipping_zip' => element('field_id_' . $this->config->item('cartthrob:orders_shipping_zip'), $entry),
            'shipping_country' => element('field_id_' . $this->config->item('cartthrob:orders_shipping_country'), $entry),
            'shipping_country_code' => element('field_id_' . $this->config->item('cartthrob:orders_shipping_country_code'), $entry),
            'first_name' => element('field_id_' . $this->config->item('cartthrob:orders_billing_first_name'), $entry),
            'last_name' => element('field_id_' . $this->config->item('cartthrob:orders_billing_last_name'), $entry),
            'company' => element('field_id_' . $this->config->item('cartthrob:orders_billing_company'), $entry),
            'address' => element('field_id_' . $this->config->item('cartthrob:orders_billing_address'), $entry),
            'address2' => element('field_id_' . $this->config->item('cartthrob:orders_billing_address2'), $entry),
            'city' => element('field_id_' . $this->config->item('cartthrob:orders_billing_city'), $entry),
            'state' => element('field_id_' . $this->config->item('cartthrob:orders_billing_state'), $entry),
            'zip' => element('field_id_' . $this->config->item('cartthrob:orders_billing_zip'), $entry),
            'country' => element('field_id_' . $this->config->item('cartthrob:orders_billing_country'), $entry),
            'country_code' => element('field_id_' . $this->config->item('cartthrob:orders_country_code'), $entry),
            'entry_id' => $entry_id,
            'order_id' => $entry_id,
            'total_cart' => element('field_id_' . $this->config->item('cartthrob:orders_total_field'), $entry),
            'auth' => [
                'authorized' => element('status', $entry) === $this->config->item('cartthrob:orders_default_status'),
                'failed' => element('status', $entry) === $this->config->item('cartthrob:orders_failed_status'),
                'declined' => element('status', $entry) === $this->config->item('cartthrob:orders_declined_status'),
                'processing' => element('status', $entry) === $this->config->item('cartthrob:orders_processing_status'),
                'error_message' => element('field_id_' . $this->config->item('cartthrob:orders_error_message_field'), $entry),
                'transaction_id' => element('field_id_' . $this->config->item('cartthrob:orders_transaction_id'), $entry),
            ],
            'purchased_items' => $this->purchased_items_model->get_purchased_items($entry_id),
            'create_user' => null,
            'member_id' => element('author_id', $entry),
            'group_id' => null,
            'authorized_redirect' => '',
            'failed_redirect' => '',
            'declined_redirect' => '',
            'processing_redirect' => '',
            'return' => $this->functions->fetch_site_index(1),
            'site_name' => $this->config->item('site_name'),
            'custom_data' => [],
            'subscription' => '',
            'subscription_options' => null,
            'payment_gateway' => element('field_id_' .
                $this->config->item('cartthrob:orders_payment_gateway'), $entry),
        ];

        $fields = [];

        foreach ($this->cartthrob_settings_model->get_settings() as $key => $value) {
            if (strncmp('orders_', $key, 7) === 0) {
                $fields[] = $value;
            }
        }

        foreach ($this->getOrderItems($entry_id, [], [], true) as $row) {
            unset($row['exrta']['row_id']);
            $row['product_id'] = $row['entry_id'];
            $row['item_options'] = $row['extra'];
            unset($row['extra'], $row['row_order'], $row['order_id']);
            $order_data['items'][$row['row_id']] = $row;
        }

        foreach ($this->cartthrob_field_model->get_fields_by_channel($this->config->item('cartthrob:orders_channel')) as $field) {
            if (!in_array($field['field_id'], $fields)) {
                $order_data['custom_data'][$field['field_name']] = element($field['field_name'], $entry);
            }
        }

        return $order_data;
    }

    /**
     * @param $member_id
     * @return mixed
     */
    public function getMemberFirstOrder($member_id): mixed
    {
        return current(reset($this->getMemberOrders($member_id)));
    }

    /**
     * @param bool $where
     * @param bool $status
     * @param bool $just_total
     * @return array|float|int
     */
    public function order_totals($where = false, $status = false, $just_total = false)
    {
        $defaults = ['total' => 0, 'subtotal' => 0, 'tax' => 0, 'shipping' => 0, 'discount' => 0, 'orders' => 0];

        $dat = [];
        $dat['total'] = 0;
        $dat['average_total'] = 0;
        $dat['subtotal'] = 0;
        $dat['subtotal_plus_tax'] = 0;
        $dat['tax'] = 0;
        $dat['shipping'] = 0;
        $dat['shipping_plus_tax'] = 0;
        $dat['discount'] = 0;
        $dat['orders'] = 0;
        $data_avg_tot[] = 0;
        // $where['entry_start_date'] = isset($where['entry_start_date']) ? $where['entry_start_date'] : mktime(0, 0, 0);
        // $where['entry_end_date'] = isset($where['entry_end_date']) ? $where['entry_end_date'] : mktime(0, 0, 0);
        if (!$this->config->item('cartthrob:orders_total_field') || !$this->config->item('cartthrob:orders_channel')) {
            return ($just_total) ? 0 : $defaults;
        }

        $query = ee('Model')->get('ChannelEntry');
        $query->filter('channel_id', $this->config->item('cartthrob:orders_channel'));
        if ($status) {
            $query->filter('status', 'NOT IN', $status);
        }

        if (is_array($where)) {
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    $query->filter($key, 'IN', $value);
                } elseif (isset($where['entry_start_date'])) {
                    $query->filter('entry_date', '>=', $where['entry_start_date']);
                    $query->filter('entry_date', '<', $where['entry_end_date']);
                } else {
                    if ($value == 'IS NOT NULL') {
                        $query->filter($key . " <> ''", null, false);
                        $query->filter($key . ' IS NOT NULL', null, false);
                    } else {
                        $query->filter($key, $value);
                    }
                }
            }
        }
        $entries = $query->all();

        $orders_total_field = 'field_id_' . $this->config->item('cartthrob:orders_total_field');
        $orders_subtotal_field = 'field_id_' . $this->config->item('cartthrob:orders_subtotal_field');
        $orders_subtotal_plus_tax_field = 'field_id_' . $this->config->item('cartthrob:orders_subtotal_plus_tax_field');
        $orders_tax_field = 'field_id_' . $this->config->item('cartthrob:orders_tax_field');
        $orders_shipping_field = 'field_id_' . $this->config->item('cartthrob:orders_shipping_field');
        $orders_shipping_plus_tax_field = 'field_id_' . $this->config->item('cartthrob:orders_shipping_plus_tax_field');
        $orders_discount_field = 'field_id_' . $this->config->item('cartthrob:orders_discount_field');

        foreach ($entries as $queries) {
            $dat['total'] += (float)$queries->$orders_total_field;
            $dat['subtotal'] += (float)$queries->$orders_subtotal_field;
            $dat['subtotal_plus_tax'] += (float)$queries->$orders_subtotal_plus_tax_field;
            $dat['tax'] += (float)$queries->$orders_tax_field;
            $dat['shipping'] += (float)$queries->$orders_shipping_field;
            $dat['shipping_plus_tax'] += (float)$queries->$orders_shipping_plus_tax_field;
            $dat['discount'] += (float)$queries->$orders_discount_field;
            $data_avg_tot[] = (float)$queries->$orders_total_field;
        }
        $dat['average_total'] = array_sum($data_avg_tot) / count($data_avg_tot);
        if (isset($where['entry_start_date'])) {
            $this->db->select('COUNT(*) AS orders');
            $this->db->from('channel_titles')
                ->where('channel_id', $this->config->item('cartthrob:orders_channel'))
                ->where('entry_date >=', $where['entry_start_date'])
                ->where('entry_date <', $where['entry_end_date']);
        } else {
            $this->db->select('COUNT(*) AS orders');
            $this->db->from('channel_titles')
                ->where('channel_id', $this->config->item('cartthrob:orders_channel'));
        }

        $data = $this->db->get();

        if ($data->result() && $data->num_rows() > 0) {
            foreach ($data->result_array() as $row) {
                $dat['orders'] = $row['orders'];
            }
        }

        if (array_key_exists('shipping_plus_tax', $dat)) {
            $dat['shipping:plus_tax'] = $dat['shipping_plus_tax'];
        }
        if (array_key_exists('subtotal_plus_tax', $dat)) {
            $dat['subtotal:plus_tax'] = $dat['subtotal_plus_tax'];
        }

        foreach ($defaults as $key => $value) {
            if (empty($dat[$key])) {
                $dat[$key] = $value;
            }
        }

        if ($just_total) {
            return $dat['total'];
        }

        return $dat;
    }

    /**
     * Creates an order from a sub_id
     *
     * This'll kill an existing cart "session", so only use in a cron where there is no session
     * // @TODO but this won't happen in the transaction-object branch
     *
     * @param int|string $sub_id
     *
     * @return
     */
    public function create_order_from_subscription($sub_id)
    {
        $query = $this->db->where('sub_id', $sub_id)->get('cartthrob_permissions');

        if ($query->num_rows() === 0) {
            return false;
        }

        $item = _unserialize($query->row('serialized_item'));

        // add some stuff to EE session
        $member_query = $this->db
            ->select('member_id, group_id, email')
            ->where('member_id', $query->row('member_id'))
            ->get('members');

        $cache = [];

        foreach ($member_query->row_array() as $key => $value) {
            $cache[$key] = $this->session->userdata[$key];

            $this->session->userdata[$key] = $value;
        }

        $this->load->model('customer_model');

        $customer_info = $this->customer_model->get_customer_info(null, $query->row('member_id'));

        // relaunch the cart
        $this->cartthrob = Cartthrob_core::instance('ee', ['cart' => ['items' => [$item], 'customer_info' => $customer_info]]);

        $return = $this->createOrder($this->order_data_array());

        foreach ($cache as $key => $value) {
            $this->session->userdata[$key] = $value;
        }

        return $return;
    }

    /**
     * @return array|bool
     */
    public function create_async_order()
    {
        if (!$this->config->item('cartthrob:orders_channel')) {
            return false;
        }

        $channelId = $this->config->item('cartthrob:orders_channel');
        $entryData = [
            'site_id' => $this->db->select('site_id')->where('channel_id', $channelId)->get('channels')->row('site_id'),
            'channel_id' => $channelId,
            'author_id' => $this->cartthrob_members_model->get_member_id(),
            'status' => ($this->config->item('cartthrob:orders_processing_status')) ? $this->config->item('cartthrob:orders_processing_status') : 'closed',
        ];

        $entryId = $this->createOrderChannelEntry($entryData);

        return $entryData + ['order_id' => $entryId, 'entry_id' => $entryId];
    }

    /**
     * @param $orderData
     * @return array|bool
     */
    public function createOrder($orderData)
    {
        $this->load->model('cartthrob_members_model');

        if (!$this->config->item('cartthrob:orders_channel')) {
            return false;
        }

        $channelId = $this->config->item('cartthrob:orders_channel');
        $entryData = $this->convertOrderData($orderData) + [
                'site_id' => $this->db->select('site_id')->where('channel_id', $channelId)->get('channels')->row('site_id'),
                'channel_id' => $channelId,
                'author_id' => (!empty($orderData['member_id']) ? $orderData['member_id'] : $this->cartthrob_members_model->get_member_id()),
                'status' => ($this->config->item('cartthrob:orders_processing_status')) ? $this->config->item('cartthrob:orders_processing_status') : 'closed',
            ];

        if (!empty($orderData['expiration_date'])) {
            $entryData['expiration_date'] = $this->localize->now + ($orderData['expiration_date'] * 24 * 60 * 60);
        }

        // Remove reserved key if it exists
        unset($entryData['items']);

        $entryId = $this->createOrderChannelEntry($entryData);

        $this->addItemsToOrderItemsTable($entryId, $this->cartthrob->cart->items());

        return array_merge($entryData, ['order_id' => $entryId, 'entry_id' => $entryId]);
    }

    /**
     * @param Cartthrob_item $item
     * @return array
     */
    protected function create_order_item_row(Cartthrob_item $item)
    {
        $row = [
            'entry_id' => $item->product_id(),
            'title' => $item->title(),
            'site_id' => $item->site_id(),
            'quantity' => (float)$item->quantity(),
            'price' => (float)$item->price(),
            'price_plus_tax' => (float)$item->taxed_price(),
            'weight' => (float)$item->weight(),
            'shipping' => (float)$item->shipping(),
            'discount' => $item->discount(),
            'no_tax' => !$item->is_taxable(),
            'no_shipping' => !$item->is_shippable(),
            'entry_date' => $this->localize->now,
        ];

        if (is_array($item->item_options())) {
            $row = array_merge($row, $item->item_options());
        }

        if ($item->sub_items()) {
            foreach ($item->sub_items() as $i => $sub_item) {
                $sub_row = [
                    'entry_id' => $sub_item->product_id(),
                    'title' => $sub_item->title(),
                    'site_id' => $sub_item->site_id(),
                    'quantity' => (float)$sub_item->quantity(),
                    'price' => (float)$sub_item->price(),
                    'price_plus_tax' => (float)$sub_item->taxed_price(),
                    'weight' => (float)$sub_item->weight(),
                    'shipping' => (float)$sub_item->shipping(),
                    'discount' => $sub_item->discount(),
                    'no_tax' => !$sub_item->is_taxable(),
                    'no_shipping' => !$sub_item->is_shippable(),
                ];

                if (is_array($sub_item->item_options())) {
                    $sub_row = array_merge($sub_row, $sub_item->item_options());
                }

                $row['sub_items'][$i] = $this->create_order_item_row($sub_item);
            }
        }

        return $row;
    }

    /**
     * @param $entry_id
     * @param $data
     */
    public function updateOrderItems($entry_id, $data)
    {
        $original_data = [];

        foreach ($this->getOrderItems($entry_id) as $_row) {
            $original_data[$_row['row_id']] = $_row;
        }

        $rows_to_keep = [];

        $default_keys = [
            'entry_id',
            'title',
            'quantity',
            'price',
            'price_plus_tax',
            'weight',
            'shipping',
            'no_tax',
            'no_shipping',
            'site_id',
            'entry_date',
        ];

        $special_keys = ['row_id', 'order_id', 'row_order'];

        foreach ($data as $row_order => $row) {
            $insert = ['order_id' => $entry_id, 'row_order' => $row_order];

            // get array values that are not default order item columns
            $extra = array_diff_key($row, array_flip(array_merge($default_keys, $special_keys)));

            foreach ($default_keys as $key) {
                $insert[$key] = (isset($row[$key])) ? $row[$key] : null;
            }

            $insert['extra'] = (count($extra) > 0) ? base64_encode(serialize($extra)) : null;

            if (!empty($row['row_id'])) {
                if ($this->config->item('cartthrob:update_inventory_when_editing_order') && isset($original_data[$row['row_id']])) {
                    $this->update_product_inventory($row, $original_data[$row['row_id']]);
                }

                $this->db->update('cartthrob_order_items', $insert, ['row_id' => $row['row_id']]);

                $rows_to_keep[] = $row['row_id'];
            } else {
                $this->db->insert('cartthrob_order_items', $insert);

                $id = $this->db->insert_id();
                $rows_to_keep[] = $id;

                /*
                 * shouldn't update the inventory here. it's a new item. the system should process inventory elsewhere since this is new.
                 * if we ever create orders outside of the regular flow... commenting this out is going to be a problem.
                 * we're not really "updating inventory" in this case.
                 * if we just adjust inventory here, which kind of makes sense, the problem is that there's some meta iventory admustments
                 * that happen elsewhere and emails that are sent for inventory modifications that aren't sent here.
                 * might want to create an inventory model or something
                 *
                 * if ($this->config->item('cartthrob:update_inventory_when_editing_order'))
                 * {
                 *     $this->db->where('row_id', $id);
                 *     $this->db->limit('1');
                 *     $query = $this->db->get('cartthrob_order_items');

                 *     if ($query->result() and $query->num_rows() > 0)
                 *     {
                 *         $item = $query->row_array();
                 *         $this->update_product_inventory($row, $item, $new_item = TRUE);
                 *     }
                 *     $query->free_result();
                 *
                 * }
                 */
            }
        }

        foreach ($original_data as $row_id => $row) {
            if (!in_array($row_id, $rows_to_keep)) {
                if ($this->config->item('cartthrob:update_inventory_when_editing_order')) {
                    $new_row = $row;

                    $new_row['quantity'] = 0;

                    $this->update_product_inventory($new_row, $row);
                }

                $this->delete_order_item($row_id);
            }
        }
    }

    /**
     * order_data_array
     *
     * formats post data and merges it with customer session data
     *
     * @param array $vars
     * @return array
     */
    public function order_data_array($vars = [])
    {
        $shipping = null;
        $shipping_plus_tax = null;
        $tax = null;
        $subtotal = null;
        $subtotal_plus_tax = null;
        $discount = null;
        $total = null;
        $credit_card_number = null;
        $create_member_id = null;
        $group_id = null;
        $subscription = [];
        $subscription_options = [];
        $payment_gateway = null;
        $create_user = false;
        $subscription_id = null;
        $payment_gateway_method = null;

        extract($vars, EXTR_IF_EXISTS);

        $this->cartthrob->cart->set_calculation_caching(false);

        $total = $total ?? $this->cartthrob->cart->total();
        $tax = $tax ?? $this->cartthrob->cart->tax();
        $discount = $discount ?? $this->cartthrob->cart->discount();
        $shipping_plus_tax = $shipping_plus_tax ?? $this->cartthrob->cart->shipping_plus_tax();
        $shipping = $shipping ?? $this->cartthrob->cart->shipping();
        $subtotal = $subtotal ?? $this->cartthrob->cart->subtotal();
        $subtotal_plus_tax = $subtotal_plus_tax ?? $this->cartthrob->cart->subtotal_with_tax();

        $this->load->library('api/api_cartthrob_tax_plugins');

        $use_billing_info = bool_string($this->input->post('use_billing_info')) ? true : bool_string($this->cartthrob->cart->customer_info('use_billing_info'));
        // all of this extra mess is here to deal with admin checkouts where the data hasn't necessarily been saved.
        $first_name = ($this->input->post('first_name') ? $this->input->post('first_name') : $this->cartthrob->cart->customer_info('first_name'));
        $last_name = ($this->input->post('last_name') ? $this->input->post('last_name') : $this->cartthrob->cart->customer_info('last_name'));
        $company = ($this->input->post('company') ? $this->input->post('company') : $this->cartthrob->cart->customer_info('company'));

        $shipping_first_name = ($this->input->post('shipping_first_name') ? $this->input->post('shipping_first_name') : $this->cartthrob->cart->customer_info('shipping_first_name'));
        $shipping_last_name = ($this->input->post('shipping_last_name') ? $this->input->post('shipping_last_name') : $this->cartthrob->cart->customer_info('shipping_last_name'));
        $shipping_phone = ($this->input->post('shipping_phone') ? $this->input->post('shipping_phone') : $this->cartthrob->cart->customer_info('shipping_phone'));
        $shipping_company = ($this->input->post('shipping_company') ? $this->input->post('shipping_company') : $this->cartthrob->cart->customer_info('shipping_company'));

        $address = ($this->input->post('address') ? $this->input->post('address') : $this->cartthrob->cart->customer_info('address'));
        $address2 = ($this->input->post('address') ? $this->input->post('address2') : $this->cartthrob->cart->customer_info('address2'));
        $city = ($this->input->post('city') ? $this->input->post('city') : $this->cartthrob->cart->customer_info('city'));
        $state = ($this->input->post('state') ? $this->input->post('state') : $this->cartthrob->cart->customer_info('state'));
        $zip = ($this->input->post('zip') ? $this->input->post('zip') : $this->cartthrob->cart->customer_info('zip'));
        $country = $this->cartthrob->cart->customer_info('country');
        $country_code = ($this->input->post('country_code') ? $this->input->post('country_code') : $this->cartthrob->cart->customer_info('country_code'));

        $shipping_address = ($this->input->post('shipping_address') ? $this->input->post('shipping_address') : $this->cartthrob->cart->customer_info('shipping_address'));
        $shipping_address2 = ($this->input->post('shipping_address') ? $this->input->post('shipping_address2') : $this->cartthrob->cart->customer_info('shipping_address2'));
        $shipping_city = ($this->input->post('shipping_city') ? $this->input->post('shipping_city') : $this->cartthrob->cart->customer_info('shipping_city'));
        $shipping_state = ($this->input->post('shipping_state') ? $this->input->post('shipping_state') : $this->cartthrob->cart->customer_info('shipping_state'));
        $shipping_zip = ($this->input->post('shipping_zip') ? $this->input->post('shipping_zip') : $this->cartthrob->cart->customer_info('shipping_zip'));
        $shipping_country = $this->cartthrob->cart->customer_info('shipping_country');
        $shipping_country_code = ($this->input->post('shipping_country_code') ? $this->input->post('shipping_country_code') : $this->cartthrob->cart->customer_info('shipping_country_code'));

        $email_address = ($this->input->post('email_address') ? $this->input->post('email_address') : $this->cartthrob->cart->customer_info('email_address'));
        $currency_code = ($this->input->post('currency_code') ? $this->input->post('currency_code') : $this->cartthrob->cart->customer_info('currency_code'));

        $expiration_month = ($this->input->post('expiration_month') ? $this->input->post('expiration_month') : $this->cartthrob->cart->customer_info('expiration_month'));
        $expiration_year = ($this->input->post('expiration_year') ? $this->input->post('expiration_year') : $this->cartthrob->cart->customer_info('expiration_year'));

        $coupon_codes = $this->cartthrob->cart->coupon_codes() ? implode(',',
            $this->cartthrob->cart->coupon_codes()) : '';
        $CVV2 = ($this->input->post('CVV2') ? $this->input->post('CVV2') : $this->cartthrob->cart->customer_info('CVV2'));

        $RET = ($this->input->post('RET') ? $this->input->post('RET', true) : $this->functions->fetch_site_index(1));

        $return = ($this->input->post('return')) ? $this->input->post('return', true) : $RET;

        if (is_object($payment_gateway)) {
            $payment_gateway = get_class($payment_gateway);
        }

        // Determine card type (brand)
        if ($this->input->post('card_type')) {
            $cardType = $this->input->post('card_type', true);
        } else {
            $cardType = (new CreditCard(['number' => $credit_card_number]))->getBrand() ?? 'Unknown Card Type';
        }

        $customer_phone = $this->input->post('phone') ? $this->input->post('phone') : $this->cartthrob->cart->customer_info('phone');

        $order_data = [
            'CVV2' => $CVV2,
            'expiration_month' => $expiration_month,
            'expiration_year' => $expiration_year,
            'items' => [],
            'transaction_id' => '',
            'card_type' => $cardType,
            'shipping' => $this->cartthrob->round($shipping),
            'shipping_plus_tax' => $this->cartthrob->round($shipping_plus_tax),
            'tax' => $this->cartthrob->round($tax),
            'subtotal' => $this->cartthrob->round($subtotal),
            'subtotal_plus_tax' => $this->cartthrob->round($subtotal_plus_tax),
            'discount' => $this->cartthrob->round($discount),
            'total' => $this->cartthrob->round($total),
            'customer_name' => $first_name . ' ' . $last_name,
            'customer_email' => $email_address,
            // what the hell is the distinction between customer_email and email_address
            'email_address' => $email_address,
            // what the hell is the distinction between customer_email and email_address
            'customer_ip_address' => $this->input->ip_address(),
            'ip_address' => $this->input->ip_address(),
            'customer_phone' => $customer_phone,
            'coupon_codes' => $coupon_codes,
            'coupon_codes_array' => $this->cartthrob->cart->coupon_codes(),
            'last_four_digits' => substr($credit_card_number, -4, 4),
            'full_billing_address' => $address . "\r\n" . ($address2 ? $address2 . "\r\n" : '') . $city . ', ' . $state .
                ' ' . $zip,
            'full_shipping_address' => ($use_billing_info) ? $address . "\r\n" . ($address2 ? $address2 . "\r\n" : '') .
                $city . ', ' . $state . ' ' . $zip : $shipping_address . "\r\n" . ($shipping_address2 ? $shipping_address2 . "\r\n" : '') . $shipping_city . ', ' .
                $shipping_state . ' ' . $shipping_zip,
            'billing_first_name' => $first_name,
            'billing_last_name' => $last_name,
            'billing_company' => $company,
            'billing_address' => $address,
            'billing_address2' => $address2,
            'billing_city' => $city,
            'billing_state' => $state,
            'billing_zip' => $zip,
            'billing_country' => $country,
            'billing_country_code' => $country_code,

            'first_name' => $first_name,
            'last_name' => $last_name,
            'company' => $company,
            'address' => $address,
            'address2' => $address2,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'country' => $country,
            'country_code' => $country_code,

            'shipping_first_name' => ($use_billing_info) ? $first_name : $shipping_first_name,
            'shipping_last_name' => ($use_billing_info) ? $last_name : $shipping_last_name,
            'shipping_phone' => ($use_billing_info) ? $customer_phone : $shipping_phone,
            'shipping_company' => ($use_billing_info) ? $company : $shipping_company,
            'shipping_address' => ($use_billing_info) ? $address : $shipping_address,
            'shipping_address2' => ($use_billing_info) ? $address2 : $shipping_address2,
            'shipping_city' => ($use_billing_info) ? $city : $shipping_city,
            'shipping_state' => ($use_billing_info) ? $state : $shipping_state,
            'shipping_zip' => ($use_billing_info) ? $zip : $shipping_zip,
            'shipping_country' => ($use_billing_info) ? $country : $shipping_country,
            'shipping_country_code' => ($use_billing_info) ? $country_code : $shipping_country_code,

            'currency_code' => $currency_code,
            'entry_id' => '',
            'order_id' => '',
            'total_cart' => $this->cartthrob->round($total),
            'auth' => [],
            'purchased_items' => [],
            'create_user' => (!empty($create_user)) ? $create_user : false,
            'member_id' => (!empty($create_member_id)) ? $create_member_id : $this->session->userdata('member_id'),
            'group_id' => (!empty($group_id)) ? $group_id : $this->session->userdata('group_id'),
            'return' => $return,
            'site_name' => $this->config->item('site_name'),
            'custom_data' => $this->cartthrob->cart->custom_data(),
            'subscription' => $subscription,
            'subscription_options' => $subscription_options,
            'payment_gateway' => (strncmp($payment_gateway, 'Cartthrob_', 10) === 0) ? substr($payment_gateway, 10) : $payment_gateway,
            'payment_gateway_method' => $payment_gateway_method,
            'subscription_id' => $subscription_id,
            'site_id' => $this->config->item('site_id'),
        ];

        $order_data['authorized_redirect'] = ($this->input->post('authorized_redirect')) ? $this->input->post('authorized_redirect', true) : $order_data['return'];
        $order_data['failed_redirect'] = ($this->input->post('failed_redirect')) ? $this->input->post('failed_redirect', true) : $order_data['return'];
        $order_data['declined_redirect'] = ($this->input->post('declined_redirect')) ? $this->input->post('declined_redirect', true) : $order_data['return'];
        $order_data['processing_redirect'] = ($this->input->post('processing_redirect')) ? $this->input->post('processing_redirect', true) : $order_data['return'];

        // overwriting the default member data here, because otherwise it's not accessible when coming back from a payment gateway
        // when using create_user.
        // save_customer_info uses POST data. If it's coming back from offsite, there's no post data to work with
        // so it defaults to customer data.
        foreach ($order_data as $key => $value) {
            if (strpos($key, 'billing_') === 0) {
                $new_key = str_replace('billing_', '', $key);
                $order_data[$new_key] = $value;
            }
        }

        foreach ($this->cartthrob->cart->items() as $row_id => $item) {
            /** @var Cartthrob_item $item */
            $row = $item->toArray();
            $row['price'] = $item->price();
            $row['price_plus_tax'] = $item->taxed_price();
            $row['weight'] = $item->weight();
            $row['shipping'] = $item->shipping();
            $row['title'] = $item->title();
            $row['discount'] = $item->discount();

            $order_data['items'][$row_id] = $row;
        }

        return array_merge($this->cartthrob->cart->customer_info(), $order_data);
    }

    /**
     * Add items to the order items
     *
     * @param $entryId
     * @param $cartItems
     */
    public function addItemsToOrderItemsTable($entryId, $cartItems)
    {
        if (!$this->config->item('cartthrob:orders_items_field')) {
            return;
        }

        $items = [];

        foreach ($cartItems as $item) {
            $items[] = $this->create_order_item_row($item);
        }

        $this->updateOrderItems($entryId, $items);

        $fieldType = $this->cartthrob_field_model->get_field_type($this->config->item('cartthrob:orders_items_field'));

        if ($fieldType === 'cartthrob_order_items' && ee('Model')->get('ChannelField', $this->config->item('cartthrob:orders_items_field'))->count()) {
            $this->cartthrob_entries_model->update(
                $entryId,
                ['field_id_' . $this->config->item('cartthrob:orders_items_field') => 1]
            );
        }
    }

    /**
     * @param $data
     * @return string
     */
    protected function createOrderChannelEntry(&$data)
    {
        ee()->load->helper('data_formatting');
        if (bool_string($this->config->item('cartthrob:orders_sequential_order_numbers'))) {
            $orderNumber = $this->generateOrderNumber($data['site_id']);
            $data = array_merge($data, $this->generateOrderTitle($orderNumber));

            $entryId = $this->cartthrob_entries_model->create_entry($data);
        } else {
            $data = array_merge($data, $this->generateOrderTitle());

            if ($entryId = $this->cartthrob_entries_model->create_entry($data)) {
                $data = $this->generateOrderTitle($entryId);

                $this->cartthrob_entries_model->update($entryId, $data);
            }
        }

        return $entryId;
    }

    /**
     * @param $entry_id
     * @param $data
     * @return mixed
     */
    protected function setStatus(int $entry_id, $data)
    {
        if ($this->get_status($entry_id)) {
            $statusModel = ee('Model')->get('cartthrob:CartthrobStatus')
                ->filter('entry_id', $entry_id)
                ->first();
        } else {
            $statusModel = ee('Model')->make('cartthrob:CartthrobStatus');
        }

        $data = array_merge($data, ['entry_id' => (int)$entry_id]);

        $statusModel->set($data)->save();

        return $statusModel;
    }
}
