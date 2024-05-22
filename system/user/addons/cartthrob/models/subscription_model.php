<?php

use CartThrob\Dependency\Illuminate\Support\Arr;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Subscription_model extends CI_Model
{
    // if it's an array it will be interpreted as a callback
    public $columns = [
        'name' => '',
        'description' => '',
        'status' => 'closed',
        'end_date' => 0,
        'start_date' => null,
        'last_bill_date' => null,
        'serialized_item' => '',
        'vault_id' => '',
        'sub_id' => '',
        'order_id' => '',
        'member_id' => '',
        'error_message' => '',
        'trial_price' => 0,
        'price' => 0,
        'allow_modification' => 0,
        'interval_units' => 'months',
        'interval_length' => 1,
        'trial_interval_units' => '',
        'trial_interval_length' => '',
        'total_occurrences' => 0,
        'used_total_occurrences' => 0,
        'trial_occurrences' => 0,
        'used_trial_occurrences' => 0,
        'rebill_attempts' => 0,
        'plan_id' => '',
    ];
    public $errors = [];
    protected $encoded_form_data = [
        'name' => 'SUN',
        'description' => 'SUD',
        'start_date' => 'SSD', // @TODO protect this one?
        'end_date' => 'SED', // @TODO protect this one?
        'interval_units' => 'SIU', // @TODO protect this one?
        'interval_length' => 'SI', // @TODO protect this one?
        'trial_interval_length' => 'STIL',
        'trial_interval_units' => 'STIU',
        // 'status' => 'closed',
        // 'last_bill_date' => NULL,
        // 'serialized_item' => '',
        // 'vault_id' => '',
        // 'sub_id' => '',
        // 'order_id' => '',
        // 'member_id' => '',
        // 'error_message' => '',
        // 'price' => 0,
        // 'used_total_occurrences' => 0,
        // 'used_trial_occurrences' => 0,
    ];
    protected $encoded_bools = [
        'allow_modification' => 'SM',
        'subscription' => 'SUB',
    ];
    protected $encoded_numbers = [
        'price' => 'SR',
        'trial_price' => 'ST',
        'interval_length' => 'SI',
        'total_occurrences' => 'SO',
        'trial_occurrences' => 'SP',
        'plan_id' => 'PI',
        'trial_interval_length' => 'STIL',
    ];
    protected $valid_statuses = [
        'open',
        'closed',
        'hold',
        'pending',
    ];

    /**
     * Subscription_model constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('cartthrob_field_model');
        $this->load->model('cartthrob_entries_model');

        $this->columns['start_date'] = $this->localize->now;
        $this->columns['last_bill_date'] = $this->localize->now;
    }

    /**
     * @param $data
     * @return bool
     */
    public function validate(&$data)
    {
        $this->errors = [];

        $validated_data = [];

        // @TODO validation

        $id = Arr::get($data, 'id');

        if ($id) {
            $existing_data = $this->get_subscription($id);

            $validated_data = [];

            if ($existing_data === false) {
                $this->errors[] = lang('invalid_subscription_id');
            } else {
                if (!$existing_data['allow_modification']) {
                    $this->errors[] = lang('modification_not_allowed');
                } else {
                    // @TODO someday allow changing of vaults/vault_id
                    $editable = [
                        'name',
                        'description',
                        'status',
                        'end_date',
                        'start_date',
                        'interval_units',
                        'interval_length',
                        'plan_id',
                    ];

                    $editable_columns = array_intersect_key($this->columns, array_flip($editable));

                    foreach ($editable_columns as $key => $default) {
                        if (isset($data[$key]) && $this->_validate_key($key, $data[$key])) {
                            $validated_data[$key] = $data[$key];
                        }
                    }

                    $validated_data['id'] = $id;
                }
            }
        } else {
            foreach ($this->columns as $key => $default) {
                // if this key is not in the data and this is a new sub, set the default
                if (!isset($data[$key]) || !$this->_validate_key($key, $data[$key])) {
                    $validated_data[$key] = $default;
                }
            }
        }

        $data = $validated_data;

        return count($this->errors) === 0;
    }

    /**
     * get subscription joined with vault by subscription id
     *
     * @param string|int $id the subscription id
     *
     * @return array|false
     */
    public function get_subscription($id)
    {
        $query = $this->db->select('cartthrob_subscriptions.*, cartthrob_vault.id AS vault_id, cartthrob_vault.token as token, cartthrob_vault.customer_id as customer_id, cartthrob_vault.gateway as gateway, cartthrob_subscriptions.id AS id')
            ->where('cartthrob_subscriptions.id', $id)
            ->join('cartthrob_vault', 'cartthrob_subscriptions.vault_id = cartthrob_vault.id', 'right')
            ->limit(1)
            ->get('cartthrob_subscriptions');

        $result = $query->num_rows() > 0 ? $query->row_array() : false;

        $query->free_result();

        if (!$result) {
            /* @NOTE if there is no corresponding vault, we will have a problem. Let's try to get what we can */

            $this->db->where('id', $id);
            $this->db->limit(1);

            $query = $this->db->get('cartthrob_subscriptions');

            $result = $query->num_rows() > 0 ? $query->row_array() : false;
            $query->free_result();

            $result['token'] = '';
            $result['customer_id'] = '';
            $result['gateway'] = '';
            // going to clear out the vault id, because it's bad
            $result['vault_id'] = '';
        }

        return $result;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    private function _validate_key($key, &$value)
    {
        switch ($key) {
            case 'status':
                if (!in_array($value, ['closed', 'open'])) {
                    $this->errors['subscription_' . $key] = lang('subscription_invalid_status');

                    return false;
                }
                break;
            case 'end_date':
            case 'start_date':
                // check if unixtime, fallback to converting via strtotime, false if not valid time string
                if (!is_numeric($value)) {
                    if (!($value = strtotime($value))) {
                        $this->errors['subscription_' . $key] = lang('subscription_invalid_' . $key);

                        return false;
                    }
                }
                break;
            case 'interval_units':
                if (!in_array($value, ['days', 'weeks', 'months', 'years'])) {
                    $this->errors['subscription_' . $key] = lang('subscription_invalid_interval_units');

                    return false;
                }
                break;
            case 'interval_length':
                if (!is_numeric($value)) {
                    $this->errors['subscription_' . $key] = lang('subscription_invalid_interval_length');

                    return false;
                }
                break;
            case 'price':
            case 'trial_price':
                if (!is_numeric($value)) {
                    $this->errors['subscription_' . $key] = lang('subscription_invalid_' . $key);

                    return false;
                }
                break;
            case 'total_occurrences':
            case 'trial_occurrences':
                if (!is_numeric($value)) {
                    $this->errors['subscription_' . $key] = lang('subscription_invalid_' . $key);

                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * @param $id
     * @param string $status
     */
    public function update_status($id, $status = 'closed')
    {
        if (in_array($status, $this->valid_statuses)) {
            $this->db->update('cartthrob_subscriptions', ['status' => $status], ['id' => $id]);
        }
    }

    /**
     * @return array
     */
    public function encoded_form_data()
    {
        static $encoded_form_data;

        if (is_null($encoded_form_data)) {
            $encoded_form_data = array_key_prefix($this->encoded_form_data, 'subscription_');
        }

        return $encoded_form_data;
    }

    /**
     * @return array
     */
    public function encoded_numbers()
    {
        static $encoded_numbers;

        if (is_null($encoded_numbers)) {
            $encoded_numbers = array_key_prefix($this->encoded_numbers, 'subscription_');
        }

        return $encoded_numbers;
    }

    /**
     * @return mixed
     */
    public function encoded_bools()
    {
        static $encoded_bools;

        if (is_null($encoded_bools)) {
            foreach ($this->encoded_bools as $key => $value) {
                if ($key !== 'subscription') {
                    $key = 'subscription_' . $key;
                }

                $encoded_bools[$key] = $value;
            }
        }

        return $encoded_bools;
    }

    /**
     * @return array|null
     */
    public function option_keys()
    {
        static $option_keys;

        if (is_null($option_keys)) {
            $option_keys = array_merge($this->encoded_form_data, $this->encoded_numbers, $this->encoded_bools);

            unset($option_keys['subscription']);

            $option_keys = array_flip($option_keys);
        }

        return $option_keys;
    }

    /**
     * @param $data
     * @param null $id
     * @return mixed|null
     */
    public function update($data, $id = null)
    {
        $fields = $this->db_map('cartthrob_subscriptions');
        $update_data = [];

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $fields)) {
                $update_data[$key] = $value;
            }
        }
        $data = $update_data;
        // only these following are available currently.
        if (isset($data['status']) && !in_array($data['status'], $this->valid_statuses)) {
            $data['status'] = $this->columns['status'];
        }
        // this was added so when a subscription is closed it sets the end date according to last bill date and interval
        // CAREFUL though. It has potential to be abused if you allow users to have access with a closed subscription until the end date
        // setting a long rebill cycle and then closing could get you free access for a long time.
        if ($data['status'] == 'closed') {
            if (isset($data['id'])) {
                $updatable_subscription = $this->get_subscription($data['id']);
                $date_string = @date('Y-m-d',
                    $updatable_subscription['last_bill_date']) . '+  ' . $updatable_subscription['interval_length'] . '  ' . $updatable_subscription['interval_units'];
                $data['end_date'] = strtotime($date_string);
            }
        }
        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
        }

        if (!isset($data['modified'])) {
            $data['modified'] = $this->localize->now;
        }

        // we can't have empty end_date if SQL is in strict mode so we default here
        if (isset($data['end_date']) && $data['end_date'] == '') {
            $data['end_date'] = '0';
        }

        // update
        if ($id) {
            $this->db->update('cartthrob_subscriptions', $data, ['id' => $id]);
        } // create
        else {
            $id = $this->create($data);
        }

        return $id;
    }

    /**
     * _db_map
     *
     * returns an array of fields in the DB.
     * @return array
     **/
    final public function db_map($table_name)
    {
        $fields = $this->db->list_fields($table_name);
        $data = [];
        // Initialize map array
        foreach ($fields as $field) {
            $data[$field] = null;
        }

        return $data;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function create($data)
    {
        $data = array_merge($this->columns, $data);

        if ($data['trial_occurrences'] > 0) {
            $data['used_trial_occurrences'] = 1;
        } else {
            $data['used_total_occurrences'] = 1;
        }

        $this->db->insert('cartthrob_subscriptions', $data);

        return $this->db->insert_id();
    }

    /**
     * @param null $id
     * @param null $order_id
     * @param null $member_id
     * @param null $sub_id
     */
    public function delete($id = null, $order_id = null, $member_id = null, $sub_id = null)
    {
        if (is_array($id)) {
            $params = $id;

            $id = null;

            extract($params);
        }

        if ($sub_id) {
            $this->db->delete('cartthrob_subscriptions', ['sub_id' => $sub_id]);
        } else {
            if ($order_id) {
                $this->db->delete('cartthrob_subscriptions', ['order_id' => $order_id]);
            } else {
                if ($member_id) {
                    $this->db->delete('cartthrob_subscriptions', ['member_id' => $member_id]);
                } else {
                    if ($id) {
                        $this->db->delete('cartthrob_subscriptions', ['id' => $id]);
                    }
                }
            }
        }
        // @TODO error
    }

    /**
     * @param $member_id
     * @param null $limit
     * @param int $offset
     * @return mixed
     */
    public function get_member_subscriptions($member_id, $limit = null, $offset = 0)
    {
        return $this->get_subscriptions(['member_id' => $member_id], $limit, $offset);
    }

    /**
     * @param array $params
     * @param null $limit
     * @param int $offset
     * @return mixed
     */
    public function get_subscriptions($params = [], $limit = null, $offset = 0)
    {
        $this->subscription_params($params, $limit, $offset);

        $query = $this->db->get('cartthrob_subscriptions');

        $subscriptions = $query->result_array();

        $query->free_result();

        return $subscriptions;
    }

    /**
     * @param array $params
     * @param null $limit
     * @param int $offset
     * @param null $db_table_prefix
     */
    public function subscription_params($params = [], $limit = null, $offset = 0, $db_table_prefix = null)
    {
        $this->load->helper('array');

        if ($db_table_prefix) {
            $db_table_prefix .= '.';
        }
        if ($id = element('id', $params)) {
            if (!is_array($id)) {
                $this->db->where($db_table_prefix . 'id', $id);
            } else {
                $this->db->where_in($db_table_prefix . 'id', $id);
            }
        }

        if ($sub_id = element('sub_id', $params)) {
            if (!is_array($sub_id)) {
                $this->db->where($db_table_prefix . 'sub_id', $sub_id);
            } else {
                $this->db->where_in($db_table_prefix . 'sub_id', $sub_id);
            }
        }

        if ($order_id = element($db_table_prefix . 'order_id', $params)) {
            if (!is_array($order_id)) {
                $this->db->where($db_table_prefix . 'order_id', $order_id);
            } else {
                $this->db->where_in($db_table_prefix . 'order_id', $order_id);
            }
        }

        if ($member_id = element($db_table_prefix . 'member_id', $params)) {
            if (!is_array($member_id)) {
                $this->db->where($db_table_prefix . 'member_id', $member_id);
            } else {
                $this->db->where_in($db_table_prefix . 'member_id', $member_id);
            }
        }

        // should have just done this from the jump.
        // looking through all existing database fields for matching to parameters
        foreach ($params as $key => $param) {
            $array_of_used_params = ['member_id', 'order_id', 'sub_id', 'id'];
            $available_fields = $this->db_map('cartthrob_subscriptions');

            if (!in_array($key, $array_of_used_params) && array_key_exists($key, $available_fields)) {
                if (is_array($param) || strpos($param, '|') !== false) {
                    if (strpos($param, '|') !== false) {
                        $param = explode('|', $param);
                    }
                    $this->db->where_in($db_table_prefix . $key, $param);
                } else {
                    $this->db->where($db_table_prefix . $key, $param);
                }
            }
        }

        if ($limit) {
            $this->db->limit((int)$limit, (int)$offset);
        }

        if (isset($params['order_by'])) {
            if (is_array($params['order_by'])) {
                foreach ($params['order_by'] as $key => $order_by) {
                    $sort = 'asc';
                    if (!empty($params['sort'][$key])) {
                        $sort = $params['sort'][$key];
                    }
                    $this->db->order_by($db_table_prefix . $order_by, $sort);
                }
            } else {
                $sort = element('sort', $params, 'asc');
                $order_by = element('order_by', $params, 'id');
                $this->db->order_by($db_table_prefix . $order_by, $sort);
            }
        } else {
            $this->db->order_by($db_table_prefix . 'member_id', 'asc');
            $this->db->order_by($db_table_prefix . 'order_id', 'desc');
            $this->db->order_by($db_table_prefix . 'id', 'desc');
        }
        // commented this out, because it was returning 1 subscription, even if the order has multiple subscriptions
        // $this->db->group_by("order_id");
    }

    /**
     * @return bool
     */
    public function get_subscriptions_without_vaults()
    {
        $query = $this->db->select('cartthrob_subscriptions.*')
            ->join('cartthrob_vault', 'cartthrob_subscriptions.vault_id = cartthrob_vault.id', 'left')
            ->where('cartthrob_vault.id IS NULL')
            ->get('cartthrob_subscriptions');

        $result = $query->num_rows() > 0 ? $query->row_array() : false;

        $query->free_result();

        return $result;
    }

    /**
     * @return mixed
     */
    public function get_subscriptions_count()
    {
        return $this->db->count_all_results('cartthrob_subscriptions');
    }

    /**
     * @return mixed
     */
    public function get_pending_subscriptions_count()
    {
        $this->pending_subscription_filter();

        return $this->db->count_all_results();
    }

    private function pending_subscription_filter()
    {
        $now = $this->localize->now;

        $this->db->from('cartthrob_subscriptions')
            ->join('cartthrob_vault', 'cartthrob_vault.id = cartthrob_subscriptions.vault_id', 'left')
            ->where('status !=', 'closed')
            ->where('status !=', 'pending')
            ->where('start_date <=', $now)
            ->where('(total_occurrences > used_total_occurrences OR total_occurrences = 0)')
            ->where('((end_date > 0 AND end_date < ' . $now . ')', null, false)// expired
            ->or_where('(interval_units = \'days\' AND (DATE_ADD(FROM_UNIXTIME(last_bill_date), INTERVAL interval_length DAY) < FROM_UNIXTIME(' . $now . ')))',
                null, false)// days due
            ->or_where('(interval_units = \'weeks\' AND (DATE_ADD(FROM_UNIXTIME(last_bill_date), INTERVAL interval_length WEEK) < FROM_UNIXTIME(' . $now . ')))',
                null, false)// weeks due
            ->or_where('(interval_units = \'months\' AND (DATE_ADD(FROM_UNIXTIME(last_bill_date), INTERVAL interval_length MONTH) < FROM_UNIXTIME(' . $now . ')))',
                null, false)// months due
            ->or_where('(interval_units = \'years\' AND (DATE_ADD(FROM_UNIXTIME(last_bill_date), INTERVAL interval_length YEAR) < FROM_UNIXTIME(' . $now . '))))',
                null, false); // years due
    }

    /**
     * @return array|bool
     */
    public function get_next_pending_subscription()
    {
        $query = $this->get_pending_subscriptions([], 1);

        $result = ($query->num_rows() > 0) ? $query->row_array() : false;

        $query->free_result();

        return $result;
    }

    // @TODO how to make this skip one that was just set to hold

    /**
     * Get subscriptions (and associated vaults) that are expired or due for billing
     *
     * @return CI_DB_result
     */
    public function get_pending_subscriptions($params = [], $limit = null, $offset = 0)
    {
        if (empty($params['order_by'])) {
            $params['order_by'] = 'modified';
            $params['sort'] = 'asc';
        }
        $this->subscription_params($params, $limit, $offset, 'cartthrob_subscriptions');

        $this->pending_subscription_filter();
        // / keep cartthrob_vault first, so that order_id are overwritten by ct_subscriptions table data..
        $this->db->select('cartthrob_vault.*, cartthrob_subscriptions.*, cartthrob_vault.id AS vault_id, cartthrob_subscriptions.id AS id',
            'cartthrob_subscriptions.order_id AS order_id');

        return $this->db->get();
    }

    /**
     * @param $id
     */
    public function get_sub_id($id)
    {
        $query = $this->db->select('sub_id')
            ->from('cartthrob_subscriptions')
            ->where('id', $id)
            ->limit(1)
            ->get();

        $sub_id = ($query->num_rows() > 0) ? $query->row('sub_id') : null;

        $query->free_result();

        return $sub_id;
    }

    /**
     * get plan
     *
     * @param string|int $id the plan id
     *
     * @return array|false
     */
    public function get_plan($plan_id)
    {
        $query = $this->db->where('id', $plan_id)
            ->get('cartthrob_subscription_plans');

        $result = $query->num_rows() > 0 ? $query->row_array() : false;

        $query->free_result();

        return $result;
    }

    /**
     * @param array $params
     */
    public function get_plan_total($params = [])
    {
        // @TODO add sql query to get total AMOUNT and total number sold for each plan.
        // will have to look in the subscriptions Database
    }

    public function get_subscriber_totals()
    {
        // @TODO get total count of subscribers.... and then what?
    }

    /**
     * @param array $params
     * @param null $limit
     * @param int $offset
     * @return mixed
     */
    public function get_plans($params = [], $limit = null, $offset = 0)
    {
        $this->load->helper('array');

        if ($id = element('id', $params)) {
            if (!is_array($id)) {
                $this->db->where('id', $id);
            } else {
                $this->db->where_in('id', $id);
            }
        }

        if ($limit) {
            $this->db->limit((int)$limit, (int)$offset);
        }

        if (isset($params['order_by'])) {
            if (is_array($params['order_by'])) {
                foreach ($params['order_by'] as $key => $order_by) {
                    $sort = 'asc';
                    if (!empty($params['sort'][$key])) {
                        $sort = $params['sort'][$key];
                    }
                    $this->db->order_by($order_by, $sort);
                }
            } else {
                $sort = element('sort', $params, 'asc');
                $order_by = element('id', $params);
                $this->db->order_by($order_by, $sort);
            }
        } else {
            $sort = (isset($params['sort'])) ? $params['sort'] : 'desc';
            $this->db->order_by('id', $sort);
        }

        $query = $this->db->get('cartthrob_subscription_plans');

        $subscriptions = $query->result_array();

        $query->free_result();

        return $subscriptions;
    }

    /**
     * @param $data
     * @param null $id
     * @return mixed|null
     */
    public function update_plan($data, $id = null)
    {
        $fields = $this->db_map('cartthrob_subscription_plans');
        $update_data = [];
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $fields)) {
                if ($value == '') {
                    switch ($key) {
                        case 'trial_occurrences':
                        case 'total_occurrences':
                            $value = null;
                    }
                }
                if (is_array($value)) {
                    $update_data[$key] = base64_encode(serialize($value));
                } else {
                    $update_data[$key] = $value;
                }
            }
        }

        // only these following are available currently.
        if (isset($update_data['status']) && !in_array($update_data['status'], $this->valid_statuses)) {
            $update_data['status'] = $this->columns['status'];
        }

        if (isset($update_data['id'])) {
            $id = $update_data['id'];
            unset($update_data['id']);
        }

        // update
        if ($id) {
            $this->db->update('cartthrob_subscription_plans', $update_data, ['id' => $id]);
        } // create
        else {
            $id = $this->create_plan($update_data);
        }

        return $id;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function create_plan($data)
    {
        $fields = $this->db_map('cartthrob_subscription_plans');
        $update_data = [];
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $fields)) {
                if (is_array($value)) {
                    $update_data[$key] = base64_encode(serialize($value));
                } else {
                    $update_data[$key] = $value;
                }
            }
        }

        if ($update_data['trial_occurrences'] > 0) {
            $update_data['used_trial_occurrences'] = 1;
        } else {
            $update_data['used_total_occurrences'] = 1;
        }

        $this->db->insert('cartthrob_subscription_plans', $update_data);

        return $this->db->insert_id();
    }

    /**
     * @param null $id
     */
    public function delete_plan($id = null)
    {
        if (is_array($id)) {
            $params = $id;

            $id = null;

            extract($params);
        }

        if ($id) {
            $this->db->delete('cartthrob_subscription_plans', ['id' => $id]);
        }
        // @TODO error
    }
}
