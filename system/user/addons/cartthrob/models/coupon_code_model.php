<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use CartThrob\Plugins\Discount\ValidateCartInterface;

class Coupon_code_model extends CI_Model
{
    private $coupon_code_data = [];

    /**
     * Coupon_code_model constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param $coupon_code
     * @return mixed
     */
    public function validate($coupon_code): mixed
    {
        $data = $this->get($coupon_code);

        if (!$data['metadata']['valid']) {
            $msg = $this->lang->line('coupon_default_error_msg');

            if (!empty($data['metadata']['invalid'])) {
                $msg = $this->lang->line('coupon_invalid_msg');
            } elseif (!empty($data['metadata']['expired'])) {
                $msg = $this->lang->line('coupon_expired_msg');
            } elseif (!empty($data['metadata']['user_limit'])) {
                $msg = $this->lang->line('coupon_user_limit_msg');
            } elseif (!empty($data['metadata']['discount_limit'])) {
                $msg = $this->lang->line('coupon_coupon_limit_msg');
            } elseif (!empty($data['metadata']['no_access'])) {
                $msg = $this->lang->line('coupon_no_access_msg');
            } elseif (!empty($data['metadata']['global_limit'])) {
                $msg = sprintf($this->lang->line('coupon_global_limit_msg'),
                    $this->cartthrob->store->config('global_coupon_limit'));
            } elseif (!empty($data['metadata']['inactive'])) {
                $msg = $this->lang->line('coupon_inactive_msg');
            } elseif (isset($data['metadata']['msg'])) {
                $msg = $data['metadata']['msg'];
            }

            $this->cartthrob->set_error($msg);
        }

        return $data['metadata']['valid'];
    }

    /**
     * @param $coupon_code
     * @return array|mixed
     */
    public function get($coupon_code)
    {
        if (isset($this->coupon_code_data[$coupon_code])) {
            return $this->coupon_code_data[$coupon_code];
        }

        $this->load->library('api/api_cartthrob_discount_plugins');

        $data = [
            'metadata' => [
                'valid' => false,
            ],
            'type' => '',
        ];

        // @TODO fix bug if you have a coupon channel, but haven't configured the type field, and then you create a coupon
        if ($this->cartthrob->store->config('coupon_code_channel') && $this->cartthrob->store->config('coupon_code_type')) {
            $coupon_field = 'title';

            if ($this->cartthrob->store->config('coupon_code_field') && $this->cartthrob->store->config('coupon_code_field') != 'title') {
                $coupon_field = 'field_id_' . $this->cartthrob->store->config('coupon_code_field');
            }

            $entryget = ee('Model')->get('ChannelEntry')
                ->filter('channel_id', $this->cartthrob->store->config('coupon_code_channel'))
                ->filter($coupon_field, $coupon_code)
                ->filter('status', '!=', 'closed')
                ->first();

            $data['metadata']['entry_id'] = '';
            $data['metadata']['entry_date'] = '';
            $data['metadata']['expiration_date'] = '';
            $data['metadata']['inactive'] = false;
            $data['metadata']['expired'] = false;
            $data['metadata']['user_limit'] = false;
            $data['metadata']['discount_limit'] = false;
            $data['metadata']['global_limit'] = false;
            $data['metadata']['invalid'] = false;

            if (!empty($entryget)) {
                $myarr = $entryget->getValues();
                $data = _unserialize($myarr['field_id_' . $this->cartthrob->store->config('coupon_code_type')], true);

                $data['metadata']['entry_id'] = $myarr['entry_id'];
                $data['metadata']['entry_date'] = $myarr['entry_date'];
                $data['metadata']['expiration_date'] = $myarr['expiration_date'];
                $data['metadata']['inactive'] = ($myarr['entry_date'] > $this->localize->now);
                $data['metadata']['expired'] = ($myarr['expiration_date'] && $myarr['expiration_date'] < $this->localize->now);
                $data['metadata']['user_limit'] = false;
                $data['metadata']['discount_limit'] = false;
                $data['metadata']['invalid'] = false;
                $data['metadata']['no_access'] = false;
                $data['metadata']['invalid'] = false;
                $data['metadata']['global_limit'] = ($this->cartthrob->store->config('global_coupon_limit') > 1 && count($this->cartthrob->cart->coupon_codes()) > $this->cartthrob->store->config('global_coupon_limit'));
                $data['metadata']['valid'] = true;

                $used_by = (!empty($data['used_by'])) ? array_count_values(preg_split('#\s*[,|]\s*#',
                    trim($data['used_by']))) : [];

                if (!empty($data['per_user_limit']) && isset($used_by[$this->session->userdata('member_id')]) && ($used_by[$this->session->userdata('member_id')] >= $data['per_user_limit'])) {
                    $data['metadata']['user_limit'] = true;
                }

                if (isset($data['discount_limit']) && $data['discount_limit'] !== '' && $data['discount_limit'] <= 0) {
                    $data['metadata']['discount_limit'] = true;
                }

                if (!empty($data['member_groups']) && !in_array($this->session->userdata('group_id'),
                    preg_split('#\s*[,|]\s*#', trim($data['member_groups'])))) {
                    $data['metadata']['no_access'] = true;
                }

                if (!empty($data['member_ids']) && !in_array($this->session->userdata('member_id'),
                    preg_split('#\s*[,|]\s*#', trim($data['member_ids'])))) {
                    $data['metadata']['no_access'] = true;
                }

                foreach ($data['metadata'] as $cond => $value) {
                    if (!in_array($cond,
                        ['entry_id', 'entry_date', 'expiration_date', 'valid']) && $value === true) {
                        $data['metadata']['valid'] = false;
                        break;
                    }
                }

                $plugin = $this->api_cartthrob_discount_plugins->set_plugin($data['type'])->plugin();

                if ($data['metadata']['valid'] && $plugin instanceof ValidateCartInterface) {
                    if (!$data['metadata']['valid'] = $plugin->set_plugin_settings($data)->validateCart()) {
                        $data['metadata']['msg'] = $plugin->error();
                    }
                }
            } else {
                $data['metadata']['invalid'] = true;
            }
        }

        $this->coupon_code_data[$coupon_code] = $data;

        return $data;
    }

    /**
     * @return $this
     */
    public function process()
    {
        if (!$this->cartthrob->cart->coupon_codes()) {
            return $this;
        }

        foreach ($this->cartthrob->cart->coupon_codes() as $coupon_code) {
            $data = $this->coupon_code_model->get($coupon_code);

            $entry_id = (isset($data['metadata']['entry_id'])) ? $data['metadata']['entry_id'] : false;

            if ($entry_id && $this->cartthrob->store->config('coupon_code_type')) {
                unset($data['metadata']);

                $data['used_by'] = (isset($data['used_by'])) ? $data['used_by'] . '|' . $this->session->userdata('member_id') : $this->session->userdata('member_id');

                $data['discount_limit'] = (isset($data['discount_limit']) && strlen($data['discount_limit']) > 0) ? $data['discount_limit'] - 1 : '';

                $coupon_channel_data = [
                    'field_id_' . $this->cartthrob->store->config('coupon_code_type') => $data,
                ];

                $entry = ee('Model')
                    ->get('ChannelEntry', $entry_id)
                    ->with('Channel')
                    ->first();

                $entry->set($coupon_channel_data);
                $entry->save();
                /* $this->db->update('channel_data_field_'.$this->cartthrob->store->config('coupon_code_type'), array('field_id_'.$this->cartthrob->store->config('coupon_code_type') => base64_encode(serialize($data))), array('entry_id' => $entry_id)); */
            }
        }

        return $this;
    }
}
