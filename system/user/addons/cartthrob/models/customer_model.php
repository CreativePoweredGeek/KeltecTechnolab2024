<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Customer_model extends CI_Model
{
    public function __construct()
    {
        $this->load->model('cartthrob_settings_model');
    }

    /**
     * @param null $existing_customer_info
     * @param null $member_id
     */
    public function get_customer_info($existing_customer_info = null, $member_id = null)
    {
        if (is_array($this->config->item('cartthrob:default_location'))) {
            $customer_info_defaults = $this->config->item('cartthrob:customer_info_defaults');

            foreach ($this->config->item('cartthrob:default_location') as $key => $value) {
                $customer_info_defaults[$key] = $value;
            }

            $this->cartthrob_settings_model->set_item('customer_info_defaults', $customer_info_defaults);
        }

        if (is_null($existing_customer_info)) {
            $customer_info = $this->config->item('cartthrob:customer_info_defaults');
        } else {
            $customer_info = $existing_customer_info;
        }

        if (is_null($member_id)) {
            $member_id = $this->session->userdata('member_id');

            $userdata = $this->session->userdata;
        } else {
            $query = $this->db->select('username, screen_name, email')
                ->where('member_id', $member_id)
                ->get('members');

            $userdata = $query->row_array();

            $query->free_result();
        }

        // auto-set the customer ip address
        $customer_info['ip_address'] = $this->input->ip_address();

        if (empty($customer_info['currency_code'])) {
            $customer_info['currency_code'] = (string)$this->config->item('cartthrob:number_format_defaults_currency_code');
        }

        if ($member_id && $this->config->item('cartthrob:save_member_data')) {
            $member_data_loaded = false;

            if ($member_data_loaded === false) {
                $member_datas = ee('Model')->get('Member', $member_id)->first();
                $member_data = $member_datas->getValues();

                foreach ($this->config->item('cartthrob:customer_info_defaults') as $key => $value) {
                    if ($member_field = $this->config->item('cartthrob:member_' . $key . '_field')) {
                        if (isset($member_data['m_field_id_' . $member_field])) {
                            $customer_info[$key] = $member_data['m_field_id_' . $member_field];
                        } else {
                            if (!is_numeric($member_field) && isset($userdata[$member_field])) {
                                $customer_info[$key] = $userdata[$member_field];
                            }
                        }
                    }
                }
            }
        }

        foreach ($this->config->item('cartthrob:customer_info_defaults') as $key => $value) {
            if (!isset($customer_info[$key])) {
                $customer_info[$key] = $value;
            }
        }

        return $customer_info;
    }
}
