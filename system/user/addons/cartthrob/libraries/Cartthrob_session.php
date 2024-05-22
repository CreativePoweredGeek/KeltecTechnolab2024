<?php

use CartThrob\Dependency\Illuminate\Support\Arr;

/**
 * The CartThrob Session class
 *
 * This is designed to allow members and guests to be associated with a cart in the database, with persistence
 */
class Cartthrob_session
{
    /**
     * @var string|false The user's unique fingerprint, based on the session_fingerprint_method config, FALSE when fingerprinting turned off
     */
    protected $fingerprint;

    /**
     * @var int see Cartthrob_session::generate_fingerprint()
     */
    protected $fingerprint_method = 0;

    /**
     * @var string The user's unique session ID
     */
    protected $session_id;

    /**
     * @var int The cart associated with this session
     */
    protected $cart_id;

    /**
     * @var int The percent chance that garbage collection will occur
     */
    protected $garbage_collection_probability = 5;

    /**
     * @var int The length of time of the session cookie
     */
    protected int $expires = 7200;

    /**
     * Constructor
     *
     * @param array $params core (required), use_fingerprint, use_regenerate_id,
     */
    public function __construct($params = [])
    {
        $this->fingerprint_method = ee()->config->item('cartthrob:session_fingerprint_method');
        $this->fingerprint = ee()->config->item('cartthrob:session_use_fingerprint') ? $this->generate_fingerprint() : false;
        $this->expires = $this->getSessionExpiry();

        $this->session_id = Arr::get($params, 'session_id', ee()->input->cookie('cartthrob_session_id'));

        if ($this->session_id) {
            // if they're logged in we can just pull up the session by member id, since EE has already verified this user
            if (ee()->session->userdata('member_id')) {
                ee()->db
                    ->where('member_id', ee()->session->userdata('member_id'))
                    ->or_where('session_id', $this->session_id);
            } // otherwise we just pull from the session id and fingerprint if fingerprinting is active
            else {
                ee()->db->where('session_id', $this->session_id);

                if ($this->fingerprint !== false) {
                    ee()->db->where('fingerprint', $this->fingerprint);
                }
            }

            $query = ee()->db
                ->order_by('expires', 'desc')
                ->limit(1)
                ->get('cartthrob_sessions');

            if ($query->num_rows() === 0 || $query->row('expires') < @time()) {
                $this->generate_session();
            } else {
                $this->cart_id = $query->row('cart_id');

                // this is a roundabout way to make true the default
                if (!isset($params['use_regenerate_id']) || $params['use_regenerate_id']) {
                    $this->regenerate_session_id();
                }
            }
        } elseif (ee()->session->userdata('member_id')) {
            // let's see if there's a member based session for this user

            ee()->db->where('member_id', ee()->session->userdata('member_id'));

            $query = ee()->db->limit(1)->get('cartthrob_sessions');

            if ($query->num_rows() === 0 || $query->row('expires') < @time()) {
                $this->generate_session();
            } else {
                $this->cart_id = $query->row('cart_id');
                $this->session_id = $query->row('session_id');

                $allowedToRegenerateSession = Arr::get($params, 'use_regenerate_id', true);
                if ($allowedToRegenerateSession && !ee()->input->is_ajax_request()) {
                    $this->regenerate_session_id();
                }
            }
        } else {
            $this->generate_session();
        }

        if (!ee()->config->item('cartthrob:garbage_collection_cron') && rand(1, 100) <= $this->garbage_collection_probability) {
            $this->garbage_collection();
        }
    }

    public function setup_sticky_cart()
    {
        ee()->load->model('cart_model');
        $cart_id = $this->cart_id;
        $member_id = ee()->session->userdata('member_id');
        if ($cart_id && $member_id) {
            $cart_data = ee()->cart_model->fetch($cart_id);
            if ($cart_data) {
                ee('cartthrob:CartService')->mergeMemberCarts($member_id, $cart_data);
            }
        }
    }

    /**
     * Generate a unique fingerprint for the user
     *
     * @return string
     */
    public function generate_fingerprint()
    {
        switch ($this->fingerprint_method) {
            // @TODO clear existing sessions when changing this setting
            case 1:
                $fingerprint = ee()->input->ip_address();
                break;
            case 2:
                $fingerprint = substr(ee()->input->user_agent(), 0, 120);
                break;
            case 3:
                $fingerprint = ee()->input->ip_address() . substr(ee()->input->user_agent(), 0, 120);
                break;
            case 4:
                // rackspace ip
                $fingerprint = ee()->input->server('HTTP_X_FORWARDED_FOR');
                break;
            case 5:
                // rackspace ip + useragent
                $fingerprint = ee()->input->server('HTTP_X_FORWARDED_FOR') . substr(ee()->input->user_agent(), 0, 120);
                break;
            case 0:
            default:
                $fingerprint = ee()->input->server('HTTP_ACCEPT_LANGUAGE') . ee()->input->server('HTTP_ACCEPT_CHARSET') . ee()->input->server('HTTP_ACCEPT_ENCODING');
        }

        return sha1(ee()->config->item('encryption_key') . $fingerprint);
    }

    /**
     * Create a new session in the database, and set the session id cookie
     *
     * @return Cartthrob_session
     */
    public function generate_session()
    {
        if (!ee('cartthrob:MsmService')->isMsmSite()) {
            return $this;
        }

        $this->session_id = $this->generate_session_id();

        ee()->load->model('cart_model');

        $this->cart_id = ee()->cart_model->create();

        ee()->db->insert('cartthrob_sessions', [
            'session_id' => $this->session_id,
            'member_id' => ee()->session->userdata('member_id'),
            'fingerprint' => $this->fingerprint,
            'cart_id' => $this->cart_id,
            'expires' => @time() + $this->expires,
        ]);

        ee()->input->set_cookie('cartthrob_session_id', $this->session_id, $this->expires);

        return $this;
    }

    /**
     * Generate a unique session id
     *
     * @return string
     */
    public function generate_session_id()
    {
        return md5(uniqid(rand(), true));
    }

    /**
     * Generate a new session id, or set one, for an existing session
     *
     * // @TODO this is wonky for some reason, something to do with the timing of the cookie setting, and kills logged out carts intermittently
     *
     * @param unknown $session_id Description
     *
     * @return Cartthrob_session
     */
    public function regenerate_session_id($session_id = null)
    {
        // @TODO
        return $this;

        if (is_null($session_id)) {
            $session_id = $this->generate_session_id();
        }

        $old_session_id = $this->session_id;

        $this->session_id = $session_id;

        $this->update(['session_id' => $this->session_id], $old_session_id);

        ee()->input->set_cookie('cartthrob_session_id', $this->session_id, $this->expires);

        return $this;
    }

    /**
     * Update session in database
     *
     * Automatically updates the expires field
     *
     * @param array $data fields to update in the database: cart_id, member_id, session_id
     * @param string|null $session_id The session id to update, if null, it will use the current session id
     *
     * @return Cartthrob_session
     */
    public function update(array $data, $session_id = null)
    {
        $valid = ['cart_id', 'member_id', 'session_id'];

        if (is_null($session_id)) {
            $session_id = $this->session_id;
        }

        foreach ($data as $key => $value) {
            if (!in_array($key, $valid)) {
                unset($data[$key]);
            }
        }

        $data['expires'] = @time() + $this->expires;

        ee()->db->update('cartthrob_sessions', $data, ['session_id' => $session_id]);

        return $this;
    }

    /**
     * Removes expired sessions from the database
     *
     * @return Cartthrob_session
     */
    public function garbage_collection()
    {
        ee()->db->where('expires <', @time())->delete('cartthrob_sessions');

        return $this;
    }

    /**
     * Destroy the session from database and destroys the session cookie
     *
     * @return Cartthrob_session
     */
    public function destroy()
    {
        if (bool_string(ee('cartthrob:SettingsService')->get('cartthrob', 'clear_cart_on_logout'))) {
            ee()->db->delete('cartthrob_sessions', ['session_id' => $this->session_id]);
            ee()->db->delete('cartthrob_cart', ['id' => $this->cart_id]);
        }

        ee()->input->set_cookie('cartthrob_session_id', '', -3600);

        return $this;
    }

    /**
     * Get the current users's session_id
     *
     * @return string
     */
    public function session_id()
    {
        return $this->session_id;
    }

    /**
     * Cart ID
     *
     * @return int
     */
    public function cart_id()
    {
        return $this->cart_id;
    }

    /**
     * Visitor's fingerprint
     *
     * USE FOR DEBUG ONLY
     *
     * @return string
     */
    public function fingerprint()
    {
        return $this->fingerprint;
    }

    /**
     * Expires
     *
     * Number of ms before session expires
     *
     * @return int
     */
    public function expires()
    {
        return $this->expires;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'session_id' => $this->session_id,
            'cart_id' => $this->cart_id,
            'fingerprint' => $this->fingerprint,
            'expires' => $this->expires,
        ];
    }

    /**
     * set cart id
     *
     * @param int $cart_id
     *
     * @return Cartthrob_session
     */
    public function set_cart_id($cart_id)
    {
        $this->cart_id = $cart_id;

        $this->update(['cart_id' => $this->cart_id]);

        return $this;
    }

    /**
     * set member ID
     *
     * Use this in a login hook, or when creating a member, to associate a guest cart with the member
     *
     * @return Cartthrob_session
     */
    public function set_member_id()
    {
        $this->update(['member_id' => ee()->session->userdata('member_id')]);

        return $this;
    }

    /**
     * @return int
     */
    private function getSessionExpiry(): int
    {
        if (ee()->config->item('cartthrob:session_expire') || ee()->config->item('cartthrob:session_expire') === '0') {
            return (int)ee()->config->item('cartthrob:session_expire');
        }

        if (ee()->config->item('sess_expiration')) {
            return (int)$this->expires = ee()->config->item('sess_expiration');
        }

        return (int)ini_get('session.cookie_lifetime');
    }
}
