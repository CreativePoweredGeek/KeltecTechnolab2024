<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use CartThrob\Exceptions\CartThrobException;

class Cartthrob_members_model extends CI_Model
{
    public array $errors = [];

    public $oldest_superadmin = false;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('cartthrob_settings_model');
        $this->oldest_superadmin = $this->getOldestSuperAdmin();
    }

    /**
     * generate_random_member_data
     *
     * this creates a member with email address like sub_11519801580118@example.com
     * in the case where a user needs to be created, but no data is available to create a member
     *
     * @return array
     */
    public function generate_random_member_data()
    {
        $random = uniqid('sub_') . '_@example.com';

        return $this->validate(null, $random);
    }

    /**
     * @param bool $username
     * @param $email
     * @param bool $screen_name
     * @param bool $password
     * @param bool $password_confirm
     * @param int $group_id
     * @param bool $language
     * @return bool
     */
    public function validate(
        $username = false,
        $email = false,
        $screen_name = false,
        $password = false,
        $password_confirm = false,
        $group_id = 4,
        $language = false
    ) {
        if ($group_id < 4) {
            $this->errors = [$this->lang->line('validation_group_id_is_too_low')];

            return false;
        }
        $this->load->helper('security');

        $data['username'] = ($username) ? $username : $email;
        $data['email'] = $email;

        // GENERATING A PASSWORD IF NONE IS PROVIDED
        if (empty($password)) {
            $password = $this->functions->random('alpha');
            $password_confirm = $password;
        }
        // if it's NULL then it needs to be checked
        if ($password_confirm === false) {
            $password_confirm = $password;
        }
        $data['screen_name'] = (empty($screen_name)) ? $data['username'] : $screen_name;
        if ($language) {
            $data['language'] = $language;
        } else {
            $data['language'] = $this->config->item('deft_lang');
        }

        /* -------------------------------------
         * /**  Instantiate validation class
         * /** -------------------------------------*/
        if (!class_exists('EE_Validate')) {
            require APPPATH . 'libraries/Validate.php';
        }
        $VAL = new EE_Validate([
            'member_id' => '',
            'val_type' => 'new', // new or update
            'fetch_lang' => true,
            'require_cpw' => false,
            'enable_log' => false,
            'username' => $data['username'],
            'cur_username' => '',
            'screen_name' => $data['screen_name'],
            'cur_screen_name' => '',
            'password' => $password,
            'password_confirm' => $password_confirm,
            'cur_password' => '',
            'email' => $data['email'],
            'cur_email' => '',
        ]);

        // if the email doesn't validate, the rest are irrelevant
        $VAL->validate_email();
        if (count($VAL->errors) > 0) {
            // return the array of errors.
            $this->errors = $VAL->errors;

            return false;
        }
        $VAL->validate_username();
        $VAL->validate_screen_name();
        $VAL->validate_password();
        if (count($VAL->errors) > 0) {
            // return the array of errors.
            $this->errors = $VAL->errors;

            return false;
        }

        $data['password'] = $password; // this used to be sha1 encrypted. now that's handled elsewhere

        $data['group_id'] = $group_id;

        return $data;
    }

    /**
     * @param array $data must contain: username, email, screen_name, password (hashed!), group_id, language
     * @return int|false If successful will return member_id, if unsuccessful, will return FALSE
     */
    public function create($data)
    {
        $this->load->helper(['security', 'string', 'text']);
        $this->load->library('cartthrob_emails');
        $default_group_id = $this->config->item('default_role') ?? $this->config->item('default_member_group') ?? 4;

        // we always want this to be pending, unless explicitly set. We also don't want it to be any of the default member groups that have too much power or special status.
        if (!empty($data['group_id']) && $data['group_id'] < 4) {
            $data['group_id'] = $default_group_id;
        } else {
            $role = ee('Model')->get('Role')
                        ->filter('role_id', $data['group_id'])
                        ->first();

            $data['group_id'] = $role ? $data['group_id'] : $default_group_id;
        }

        if ($this->config->item('req_mbr_activation') === 'manual' || $this->config->item('req_mbr_activation') === 'email') {
            $data['group_id'] = 4;
        }

        if ($this->config->item('req_mbr_activation') === 'email') {
            $data['authcode'] = $this->functions->random('alnum', 10);
        }
        $this->load->library('auth');
        $hashed_password = $this->auth->hash_password($data['password']);

        // $data['username'] = $username;
        // $data['screen_name'] = $screenname;
        $data['password'] = $hashed_password['password'];
        $data['salt'] = $hashed_password['salt'];
        $data['unique_id'] = random_string('encrypt');
        $data['crypt_key'] = $this->functions->random('encrypt', 16);
        // $data['email'] = $email_address;
        $data['ip_address'] = $this->input->ip_address();
        $data['join_date'] = $this->localize->now;
        if (!isset($data['language'])) {
            $data['language'] = $this->config->item('deft_lang');
        }
        $data['timezone'] = ($this->config->item('default_site_timezone') && $this->config->item('default_site_timezone') != '') ? $this->config->item('default_site_timezone') : $this->config->item('default_site_timezone');
        $data['time_format'] = ($this->config->item('time_format') && $this->config->item('time_format') != '') ? $this->config->item('time_format') : 'us';

        if ($this->config->item('req_mbr_activation') == 'email') {
            $data['authcode'] = $this->functions->random('alnum', 10);
        }

        $member_create = ee('Model')->make('Member');
        $member_create->set($data)->save();
        $member_id = $member_create->member_id;

        /**************** admin notification emails ************/
        if ($this->config->item('new_member_notification') == 'y' && $this->config->item('mbr_notification_emails') != '') {
            $vars = [
                'name' => $data['screen_name'],
                'site_name' => stripslashes($this->config->item('site_name')),
                'control_panel_url' => $this->config->item('cp_url'),
                'username' => $data['username'],
                'email' => $data['email'],
            ];

            $template = $this->functions->fetch_email_template('admin_notify_reg');

            foreach ($vars as $key => $val) {
                $template['title'] = str_replace('{' . $key . '}', $val, $template['title']);
                $template['data'] = str_replace('{' . $key . '}', $val, $template['data']);
            }

            $email_to = reduce_multiples($this->config->item('mbr_notification_emails'), ',', true);

            $this->cartthrob_emails->sendEmail(
                $this->config->item('webmaster_email'),
                $this->config->item('webmaster_name'),
                $email_to,
                $template['title'],
                $template['data'],
                $plaintext = false
            );
        }

        // // NOTE this does not display any warning to the user when account activation is required
        /**************** send emails *****************************/
        if ($this->config->item('req_mbr_activation') == 'none') {
            $this->stats->update_member_stats();
        } elseif ($this->config->item('req_mbr_activation') == 'email') {
            $action_id = $this->functions->fetch_action_id('Member', 'activate_member');

            $vars = [
                'activation_url' => $this->functions->fetch_site_index(0,
                    0) . QUERY_MARKER . 'ACT=' . $action_id . '&id=' . $data['authcode'],
                'site_name' => stripslashes($this->config->item('site_name')),
                'site_url' => $this->config->item('site_url'),
                'username' => $data['username'],
                'email' => $data['email'],
            ];
            $template = $this->functions->fetch_email_template('mbr_activation_instructions');

            foreach ($vars as $key => $val) {
                $template['title'] = str_replace('{' . $key . '}', $val, $template['title']);
                $template['data'] = str_replace('{' . $key . '}', $val, $template['data']);
            }

            // plaintext was changed from False to TRUE because as far as I can tell, activation instructions are always sent plain text by the system.
            $this->cartthrob_emails->sendEmail(
                $this->config->item('webmaster_email'),
                $this->config->item('webmaster_name'),
                $data['email'],
                $template['title'],
                $template['data'],
                $plaintext = true
            );
        }
        /**************** end send emails *****************************/

        // -------------------------------------------
        // 'cartthrob_create_member' hook.
        //  - Developers, if you want to modify the $this object remember
        //	to use a reference on function call.
        //
        if ($this->extensions->active_hook('cartthrob_create_member') === true) {
            $edata = $this->extensions->call('cartthrob_create_member',
                array_merge($data, ['member_id' => $member_id]), $this);
            if ($this->extensions->end_script === true) {
                return;
            }
        }

        return $member_id;
    }

    /**
     * update
     *
     * @param string $member_id member id where data needs to be saved to
     * @param array $customer_info
     * @param bool $manually_save_customer_info Normally this function looks to see if the configuration is set to allow the saving of customer information. If that configuration option is set to false, under normal operation it would not be possible to save customer information. This flag overrides that configuration option.
     */
    public function update($member_id = false, $customer_info = [], $manually_save_customer_info = false)
    {
        // should not be NULL, 0, FALSE, ""
        if (!$member_id) {
            return $customer_info;
        }

        $member = [];
        $member_data = [];

        $this->load->model([
            'customer_model',
            'cartthrob_members_model',
            'member_model',
            'cartthrob_field_model',
            'cartthrob_entries_model',
        ]);
        $this->load->helper('array');

        foreach (array_keys($this->cartthrob->cart->customer_info()) as $field) {
            // setting an alternate variable because we may be changing where the data's going in a second.
            $orig_field = $field;

            if (bool_string($this->cartthrob->cart->customer_info('use_billing_info')) && strpos($field,
                'shipping_') !== false) {
                // we're going to get the data from the billing field
                $field = str_replace('shipping_', '', $field);
            }

            // saving the data.
            if (($this->cartthrob->store->config('save_member_data') || $manually_save_customer_info) && $field_id = $this->cartthrob->store->config('member_' . $orig_field . '_field')) {
                if (is_numeric($field_id)) {
                    $member_data['m_field_id_' . $field_id] = element($field, $customer_info, null);
                } else {
                    if ($field == 'email_address') {
                        if ($email_address = element($field, $customer_info, null)) {
                            if ($this->validateEmailAddress($email_address, $member_id)) {
                                $member[$field_id] = element($field, $customer_info, null);
                            }
                        }
                    } else {
                        $member[$field_id] = element($field, $customer_info, null);
                    }
                }
            }
        }

        // /////////////////////////////////////////////////////////
        // incorporating custom data into the newly created member
        // /////////////////////////////////////////////////////////
        // going to convert the custom data array to a local array so we can unset each... potentially cutting down on loop time.
        $custom_data = $this->cartthrob->cart->custom_data();

        // custom data for custom member fields
        if ($custom_data) {
            // get the custom member fields
            $custom_m_fields = ee('Model')->get('MemberField')
                ->fields('m_field_name', 'm_field_id')
                ->all();

            foreach ($custom_m_fields as $custom_m_field) {
                if (array_key_exists($custom_m_field->m_field_name, $custom_data)) {
                    $custom_m_id = $custom_m_field->m_field_id;
                    $member_data['m_field_id_' . $custom_m_id] = $custom_data[$custom_m_field->m_field_name];
                    unset($custom_data[$custom_m_field->m_field_name]);
                }
            }
        }

        if (!empty($member_data)) {
            $update_fields = ee('Model')->get('Member', $member_id)->first();
            $update_fields->set($member_data);
            $update_fields->save();
        }

        if (!empty($member)) {
            $update_member_d = ee('Model')->get('Member', $member_id)->first();
            $update_member_d->set($member);
            $update_member_d->save();
        }
    }

    /**
     * @param $email_address
     * @param $member_id
     * @return bool
     */
    public function validateEmailAddress($email_address, $member_id)
    {
        $query = $this->db->select('username, screen_name, email, member_id')
            ->where('email', $email_address)
            ->get('members');
        if ($query->result() && $query->num_rows()) {
            foreach ($query->result() as $row) {
                // someone with that email address already exists don't update the email address
                if ($row->member_id != $member_id) {
                    return false;
                }
            }
            $query->free_result();
        } else {
            // nobody with that email address exists.
            return true;
        }

        return true;
    }

    /**
     * get_member_id
     *
     * Returns the member id of the current user
     * If logged out, it will return the member id of the oldest superadmin
     *
     * @return int
     */
    public function get_member_id()
    {
        // get cached created member id if newly created member
        // or if creating an order on behalf of another member
        if (isset($this->session->cache['cartthrob']['member_id'])) {
            return $this->session->cache['cartthrob']['member_id'];
        }

        // get logged in member id if logged in
        if ($this->session->userdata('member_id')) {
            return $this->session->userdata('member_id');
        }

        // get the default logged out member id if set in the settings and valid
        if ($this->config->item('cartthrob:default_member_id') && (ctype_digit($this->config->item('cartthrob:default_member_id')) || is_int($this->cartthrob->store->config('default_member_id')))) {
            return $this->config->item('cartthrob:default_member_id');
        }

        return $this->getOldestSuperAdmin();
    }

    /**
     * Retrieves the oldest Super Admin user ID
     *
     * There are cases where EE does not populate the Roles table as expected on upgraded sites.
     * For that reason, we check both the Roles table and if necessary, the members table.
     *
     * @return int
     * @throws CartThrobException
     */
    public function getOldestSuperAdmin()
    {
        if (!$this->oldest_superadmin) {
            $this->oldest_superadmin = ee('Model')
                ->get('Member')
                ->with('Roles')
                ->filter('Roles.role_id', 1)
                ->order('member_id', 'asc')
                ->first()
                ->member_id;
        }

        if (!$this->oldest_superadmin) {
            $this->oldest_superadmin = $this->db
                ->select('member_id')
                ->from('members')
                ->where('role_id', 1)
                ->order_by('member_id', 'asc')
                ->limit(1)
                ->get()->row('member_id');
        }

        if (!$this->oldest_superadmin) {
            throw new CartThrobException('Unable to find the oldest Super Admin.');
        }

        return $this->oldest_superadmin;
    }

    /**
     * @param $member_id
     * @param $group_id
     */
    public function set_member_group($member_id, $group_id)
    {
        $update_member_d = ee('Model')->get('Member', $member_id)->first();
        $update_member_d->PrimaryRole = ee('Model')->get('Role', $group_id)->first();
        $update_member_d->save();
    }

    /**
     * @param $member_id
     * @param null $group_id
     */
    public function activate_member($member_id, $group_id = null)
    {
        $admin = in_array($this->session->userdata('group_id'), $this->config->item('cartthrob:admin_checkout_groups'));
        if ($this->config->item('req_mbr_activation') !== 'manual' && $this->config->item('req_mbr_activation') !== 'email') {
            if ($group_id) {
                $update_member_d = ee('Model')->get('Member', $member_id)->first();
                $update_member_d->PrimaryRole = ee('Model')->get('Role', $group_id)->first();
                $update_member_d->save();
            }
        }
        if ($this->cartthrob->store->config('checkout_registration_options') === 'auto-login' ||
            ($this->config->item('req_mbr_activation') !== 'manual' && $this->config->item('req_mbr_activation') !== 'email')) {
            $this->login_member($member_id);
        }
    }

    /**
     * login_member
     *
     * @param string $member_id
     * @param string $username
     * @param string $password
     * @param string $unique_id
     */
    public function login_member($member_id)
    {
        $query = $this->db->from('members')
            ->select('password, unique_id')
            ->where('member_id', $member_id)
            ->get();
        if ($query->num_rows() === 0) {
            $this->errors[] = $this->lang->line('unauthorized_access');

            return false;
        }
        $this->lang->loadfile('login');

        if ($this->config->item('user_session_type') != 's') {
            // $this->input->set_cookie($this->session->c_expire, time(), 0);
            // $this->input->set_cookie($this->session->c_anon, 1, 0);
        }

        $this->session->create_new_session($member_id, true);
        $this->session->delete_password_lockout();

        // we have to do this because the CSRF_TOKEN hash was already cleared by generating a new session with the new member id and needs to be restored,
        // CSRF_TOKEN should have already kicked in, in the case of a new member registration, so we should be good arbitrarily setting it here to get around secure forms.
        if ($this->config->item('secure_forms') === 'y' && $this->input->post('csrf_token')) {
            $this->db->insert('security_hashes', [
                'date' => time() - 60,
                'session_id' => $this->session->userdata('session_id'),
                'hash' => $this->input->post('csrf_token'),
            ]);
        }
    }

    /**
     * @param $member_id
     * @param $callback
     * @param null $args
     * @return mixed
     */
    public function simulate_member($member_id, $callback, $args = null)
    {
        $members = ee('Model')->get('Member')
                    ->with('PrimaryRole')
                    ->filter('member_id', $member_id)
                    ->fields('member_id', 'email', 'PrimaryRole.role_id')
                    ->all();

        $cache = [];

        foreach ($member as $key => $member) {
            $cache[$key] = $this->session->userdata[$key];
            $this->session->userdata[$key] = $member;
        }

        if ($args) {
            $return = call_user_func_array($callback, $args);
        } else {
            $return = call_user_func($callback);
        }

        foreach ($cache as $key => $value) {
            $this->session->userdata[$key] = $member;
        }

        return $return;
    }
}
// END CLASS
