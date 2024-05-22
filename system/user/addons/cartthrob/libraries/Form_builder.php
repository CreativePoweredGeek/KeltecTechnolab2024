<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use CartThrob\Math\Number;

if (!class_exists('Form_builder')) {
    /**
     * Form Builder
     *
     * Quickly build EE forms and manage the corresponding action
     */
    class Form_builder
    {
        /** @var Encrypt */
        private $encrypt;

        protected $classname;
        protected $method;
        protected $action = '';
        protected $form_data = [
            'error_handling',
            'return',
            'secure_return',
        ];
        protected $values = [];
        protected $hidden = [];
        protected $attributes = ['id', 'class', 'name', 'onsubmit', 'enctype'];
        protected $encoded_bools = [// 'show_errors' => array('ERR', TRUE),
        ];
        protected $encoded_form_data = [// 'required' => 'REQ',
        ];
        protected $encoded_numbers = [];
        protected $content = '';
        protected $array_form_data = [];
        protected $encoded_array_form_data = [];
        protected $secure_action = false;

        protected $errors = [];
        protected $success_callback;
        protected $error_callback;
        protected $show_errors = true;
        protected $error_header = false;
        protected $return;
        protected $captcha = false;

        protected $required = [];
        protected $rules;
        protected $options = [];

        protected $require_rules = true;
        protected $require_form_hash = true;
        protected $require_errors = true;

        protected $global_errors;
        protected $global_form_variables;

        /** @var string[] Instance variables that should not be reset */
        protected $protectedFromReset = ['encrypt'];

        /**
         * Flag to determine if we process this form with idempotency
         * @var bool
         */
        protected $idempotent_first = true;

        /**
         * Keep this as the last property. Validation will fail otherwise.
         * @todo Make the ordering of properties in this class non-dependent on its functionality
         */
        protected $params = [];

        /**
         * Form_builder constructor.
         * @param array $params
         */
        public function __construct($params = [])
        {
            ee()->load->library('form_validation');

            $this->encrypt = ee('Encrypt');

            $this->reset($params);
        }

        /**
         * @param array $errors
         * @return $this
         */
        public function set_errors(array $errors)
        {
            if (ee()->input->post('FRM')) {
                $this->global_errors[ee()->input->post('FRM')] = $errors;
            }

            return $this;
        }

        /**
         * @param $key
         * @param bool $value
         * @return $this
         */
        public function add_form_variable($key, $value = false)
        {
            if ($hash = ee()->input->post('FRM')) {
                $variables = (is_array($key)) ? $key : [$key => $value];

                foreach ($variables as $key => $value) {
                    $this->global_form_variables[$hash][$key] = (string)$value;
                }
            }

            return $this;
        }

        /**
         * @return Form_builder
         */
        public function clear_errors()
        {
            return $this->set_errors([]);
        }

        /**
         * @param $callback
         * @return $this
         */
        public function set_success_callback($callback)
        {
            $this->success_callback = $callback;

            return $this;
        }

        /**
         * @param $callback
         * @return $this
         */
        public function set_error_callback($callback)
        {
            $this->error_callback = $callback;

            return $this;
        }

        /**
         * @param bool $require_rules
         * @return $this
         */
        public function set_require_rules($require_rules = true)
        {
            $this->require_rules = (bool)$require_rules;

            return $this;
        }

        /**
         * @param bool $require_form_hash
         * @return $this
         */
        public function set_require_form_hash($require_form_hash = true)
        {
            $this->require_form_hash = (bool)$require_form_hash;

            return $this;
        }

        /**
         * @param bool $require_errors
         * @return $this
         */
        public function set_require_errors($require_errors = true)
        {
            $this->require_errors = (bool)$require_errors;

            return $this;
        }

        /**
         * @param $required
         * @return $this
         */
        public function set_required($required)
        {
            if (is_array($required)) {
                $this->required = $required;
            }

            return $this;
        }

        /**
         * @param $return
         * @return $this
         */
        public function set_return($return)
        {
            $this->return = $return;

            return $this;
        }

        /**
         * @param bool $show_errors
         * @return $this
         */
        public function set_show_errors($show_errors = true)
        {
            $this->show_errors = $show_errors;

            return $this;
        }

        /**
         * @param $error_header
         * @return $this
         */
        public function set_error_header($error_header)
        {
            $this->error_header = $error_header;

            return $this;
        }

        /**
         * @param $value
         * @param null $key
         */
        protected function set_global_error($value, $key = null)
        {
            $hash = ee()->input->post('FRM');

            if (is_null($key)) {
                $this->global_errors[$hash][] = $value;
            } else {
                $this->global_errors[$hash][$key] = $value;
            }
        }

        /**
         * @param $key
         * @param null $value
         * @return $this
         */
        public function add_error($key, $value = null)
        {
            if (is_array($key)) {
                foreach ($key as $k => $v) {
                    if (is_numeric($k)) {
                        $this->set_global_error($v);
                    } else {
                        $this->set_global_error($v, $k);
                    }
                }
            } else {
                if ($value !== null) {
                    $this->set_global_error($value, $key);
                } else {
                    $this->set_global_error($key);
                }
            }

            return $this;
        }

        /**
         * @param $data
         * @return $this
         */
        public function set_form_data($data)
        {
            if (is_array($data)) {
                $this->form_data = array_merge($this->form_data, $data);
            } else {
                $this->form_data[] = $data;
            }

            return $this;
        }

        /**
         * @param $data
         * @return $this
         */
        public function set_value($data)
        {
            if (!is_array($data)) {
                $data = [$data];
            }

            foreach ($data as $key) {
                if (is_array(ee()->input->post($key))) {
                    foreach (ee()->input->post($key) as $k => $v) {
                        $_key = "{$key}[{$k}]";

                        if (!isset(ee()->form_validation->_field_data[$_key])) {
                            ee()->form_validation->set_rules($_key, '', '');
                        }

                        ee()->form_validation->_field_data[$_key]['postdata'] = $v;

                        $this->add_form_variable("$key:$k", ee()->form_validation->set_value($_key));
                    }
                } else {
                    if (!isset(ee()->form_validation->_field_data[$key])) {
                        ee()->form_validation->set_rules($key, '', '');
                    }

                    ee()->form_validation->_field_data[$key]['postdata'] = ee()->input->post($key);

                    $this->add_form_variable($key, ee()->form_validation->set_value($key));
                }
            }

            return $this;
        }

        /**
         * @param $data
         * @return $this
         */
        public function set_array_form_data($data)
        {
            if (is_array($data)) {
                $this->array_form_data = $data;
            } else {
                $this->array_form_data[] = $data;
            }

            return $this;
        }

        /**
         * @param $data
         * @return $this
         */
        public function set_encoded_array_form_data($data)
        {
            if (is_array($data)) {
                $this->encoded_array_form_data = $data;
            } else {
                $this->encoded_array_form_data[] = $data;
            }

            return $this;
        }

        /**
         * @param $key
         * @param bool $value
         * @return $this
         */
        public function set_encoded_form_data($key, $value = false)
        {
            if (is_array($key)) {
                $this->encoded_form_data = array_merge($this->encoded_form_data, $key);
            } else {
                $this->encoded_form_data[$key] = $value;
            }

            return $this;
        }

        /**
         * @param $key
         * @param bool $value
         * @return $this
         */
        public function set_encoded_bools($key, $value = false)
        {
            if (is_array($key)) {
                $this->encoded_bools = array_merge($this->encoded_bools, $key);
            } else {
                $this->encoded_bools[$key] = $value;
            }

            return $this;
        }

        /**
         * @param $key
         * @param bool $value
         * @return $this
         */
        public function set_encoded_numbers($key, $value = false)
        {
            if (is_array($key)) {
                $this->encoded_numbers = $key;
            } else {
                $this->encoded_numbers[$key] = $value;
            }

            return $this;
        }

        /**
         * @param $key
         * @param array $options
         * @return $this
         */
        public function set_options($key, $options = [])
        {
            $this->options[$key] = $options;

            return $this;
        }

        /**
         * @param $classname
         * @return $this
         */
        public function set_classname($classname)
        {
            if ($classname) {
                $this->classname = $classname;
            }

            return $this;
        }

        /**
         * @param $method
         * @return $this
         */
        public function set_method($method)
        {
            if ($method) {
                $this->method = $method;
            }

            return $this;
        }

        /**
         * @param $action
         * @return $this
         */
        public function set_action($action)
        {
            $this->action = $action;

            return $this;
        }

        /**
         * @param $key
         * @param null $value
         * @return $this
         */
        public function set_attributes($key, $value = null)
        {
            if (is_array($key)) {
                foreach ($key as $k => $v) {
                    $this->set_attributes($k, $v);
                }
            } else {
                $this->attributes[$key] = $value;
            }

            return $this;
        }

        /**
         * @param $key
         * @param null $value
         * @return $this
         */
        public function set_hidden($key, $value = null)
        {
            if (is_array($key)) {
                foreach ($key as $k => $v) {
                    $this->set_hidden($k, $v);
                }
            } else {
                $this->hidden[$key] = $value;
            }

            return $this;
        }

        /**
         * @param array $params
         * @return $this
         */
        public function initialize($params = [])
        {
            $this->reset();

            foreach (get_class_vars(__CLASS__) as $key => $value) {
                if (isset($params[$key])) {
                    if (method_exists($this, "set_$key")) {
                        $this->{"set_$key"}($params[$key]);
                    } else {
                        $this->{$key} = $params[$key];
                    }
                }
            }

            return $this;
        }

        /**
         * @param array $reset
         * @return $this
         */
        public function reset($reset = [])
        {
            if (empty($reset)) {
                $reset = array_keys(get_class_vars(__CLASS__));
            }

            foreach (get_class_vars(__CLASS__) as $key => $value) {
                if (substr($key, 0, 7) === 'global_' || !in_array($key, $reset) || in_array($key, $this->protectedFromReset)) {
                    continue;
                }

                $this->{$key} = $value;
            }

            return $this;
        }

        /**
         * @param bool $secure_action
         * @return $this
         */
        public function set_secure_action($secure_action = true)
        {
            $this->secure_action = $secure_action;

            return $this;
        }

        /**
         * @param $params
         * @return $this
         */
        public function set_params($params)
        {
            if (!is_array($params)) {
                $params = [];
            }

            // set ALL encoded bools
            foreach ($this->encoded_bools as $key => $value) {
                $default = false;

                if (is_array($value)) {
                    $default = (bool)@$value[1];
                }

                if (!isset($params[$key])) {
                    $params[$key] = $default;
                }
            }

            $form_params = [
                'required' => '',
                'rules' => [],
                'show_errors' => 'yes',
            ];

            foreach ($params as $param => $value) {
                switch ($param) {
                    case 'required':
                        $form_params['required'] = $value;
                        break;
                    case 'action':
                        $this->set_action($value);
                        break;
                    case 'show_errors':
                        $form_params['show_errors'] = $this->create_bool_string($this->bool_string($value));
                        break;
                    case strncmp($param, 'rules:', 6) === 0:
                        $form_params['rules'][substr($param, 6)] = $value;
                        break;
                    case in_array($param, $this->attributes):
                        $this->set_attributes($param, $value);
                        break;
                    case in_array($param, $this->form_data):
                        $this->set_hidden($param, $value);
                        break;
                    case array_key_exists($param, $this->encoded_form_data):
                        $this->set_hidden($this->encoded_form_data[$param], $this->encrypt->encode($value));
                        break;
                    case array_key_exists($param, $this->encoded_bools):
                        $key = is_array($this->encoded_bools[$param]) ? $this->encoded_bools[$param][0] : $this->encoded_bools[$param];
                        $this->set_hidden($key, $this->encrypt->encode($this->create_bool_string($this->bool_string($value))));
                        break;
                    case array_key_exists($param, $this->encoded_numbers):
                        $this->set_hidden($this->encoded_numbers[$param], $this->encrypt->encode(abs(Number::sanitize($value))));
                        break;
                    case strncmp($param, 'options:', 8) === 0:
                        $this->set_options(substr($param, 8), $this->param_string_to_array($value));
                        // no break
                    case strpos($param, ':') !== false:
                        foreach ($this->array_form_data as $name) {
                            if (preg_match("/^$name:(.+)$/", $param, $match)) {
                                $this->set_hidden($name . '[' . $match[1] . ']', $value);
                            }
                        }
                        foreach ($this->encoded_array_form_data as $k => $name) {
                            if (!isset($enc_arr)) {
                                $enc_arr = [];
                                $enc_name = $name;
                            }
                            if (preg_match("/^$k:(.+)$/", $param, $match)) {
                                $enc_arr[$match[1]] = $value;
                            }
                        }
                        if (isset($enc_arr) && isset($enc_name)) {
                            $this->set_hidden($enc_name, $this->encrypt->encode(serialize($enc_arr)));
                        }
                        break;
                    case 'secure_action':
                        $this->set_secure_action($this->bool_string($value));
                        break;
                }
            }

            // process required into rules
            if ($form_params['required']) {
                $this->required = array_merge($this->required, explode('|', $form_params['required']));

                foreach ($this->required as $key) {
                    if (isset($form_params['rules'][$key])) {
                        if (strpos($form_params['rules'][$key], 'required') === false) {
                            $form_params['rules'][$key] = 'required|' . $form_params['rules'][$key];
                        }
                    } else {
                        $form_params['rules'][$key] = 'required';
                    }
                }
            }

            $this->set_hidden('ERR', $this->encrypt->encode($form_params['show_errors']));
            $this->set_hidden('RLS', $this->encrypt->encode(serialize($form_params['rules'])));

            return $this;
        }

        /**
         * @return array
         */
        public function required_keys()
        {
            $required_keys = [];

            if ($this->require_rules) {
                $required_keys[] = 'RLS';
            }

            if ($this->require_form_hash) {
                $required_keys[] = 'FRM';
            }

            if ($this->require_errors) {
                $required_keys[] = 'ERR';
            }

            return $required_keys;
        }

        /**
         * @param $content
         * @return $this
         */
        public function set_content($content)
        {
            $this->content = $content;

            return $this;
        }

        /**
         * @param bool $captcha
         * @return $this
         */
        public function set_captcha($captcha = false)
        {
            $this->captcha = (bool)$captcha;

            return $this;
        }

        /**
         * @param null $hash
         * @return array
         */
        public function errors($hash = null)
        {
            if (is_null($hash)) {
                $hash = ee()->input->post('FRM');
            }

            // return $this->errors;
            return (isset($this->global_errors[$hash])) ? $this->global_errors[$hash] : [];
        }

        /**
         * @param null $hash
         * @return bool
         */
        public function has_errors($hash = null)
        {
            return count($this->errors($hash)) > 0;
        }

        /**
         * @return string
         */
        protected function build_form_hash()
        {
            return md5(preg_replace('/\{!-- ra:(\w+) --\}/', '', ee()->TMPL->tagproper));
        }

        /**
         * @return string
         */
        public function form()
        {
            /*
             * ex.
             *
             * function form_builder_form_start($module, $method)
             * {
             *    if ($module === 'cartthrob' && $method === 'add_to_cart_form')
             *    {
             *        ee()->form_builder->set_hidden('ABC', '123');
             *    }
             * }
             */
            if (ee()->extensions->active_hook('form_builder_form_start')) {
                $tagparts = ee()->TMPL->tagparts;

                $module = array_shift($tagparts);

                $method = array_shift($tagparts);

                ee()->extensions->call('form_builder_form_start', $module, $method);
            }

            if (!$this->action) {
                // .283 Changed from using config->site_url because it uses CI's base url, making
                // it impossible to change the site's url from the CP
                $this->action = ee()->functions->create_url(ee()->uri->uri_string());
            }

            ee()->load->helper('form');

            if ($this->is_secure()) {
                $this->secure_action = true;
            }

            if ($this->secure_action) {
                $this->action = $this->secure_url($this->action);
            }

            $data = $this->attributes;

            $data['action'] = $this->action;

            if (!empty($this->classname) && !empty($this->method)) {
                $data['hidden_fields']['ACT'] = ee()->functions->fetch_action_id($this->classname, $this->method);
            }

            $data['hidden_fields']['RET'] = ee()->functions->fetch_current_uri();
            $data['hidden_fields']['URI'] = ee()->uri->uri_string();
            $data['hidden_fields']['FRM'] = $this->build_form_hash();

            if (ee('cartthrob:SettingsService')->get('cartthrob', 'idempotency_enabled')) {
                $field_name = ee('cartthrob:SettingsService')->get('cartthrob', 'idempotency_field_name');
                $data['hidden_fields'][$field_name] = ee('cartthrob:IdempotencyService')->generateKey();
            }

            if (!isset($this->hidden['RLS'])) {
                $this->set_hidden('RLS', $this->encrypt->encode('a:0:{}'));
            }

            $data['hidden_fields'] = array_merge($data['hidden_fields'], $this->hidden);

            if (ee()->TMPL && ee()->has('coilpack')) {
                $variables = $this->form_variables();
                $variables['errors']['total_results'] = $variables['errors']['error:total_results'] ?? 0;
                $data['form_open'] = ee()->functions->form_declaration($data);
                $data['form_close'] = form_close();
                $data['global_errors_count'] = $variables['global_errors:count'];
                $data['field_errors_count'] = $variables['field_errors:count'];
                ee()->TMPL->set_data($data + $variables);
            }

            $return = ee()->functions->form_declaration($data) . $this->content . form_close();

            $this->reset();

            return $return;
        }

        /**
         * @param bool $validate
         * @param bool $secure_forms
         */
        public function action_complete($validate = false, $secure_forms = true)
        {
            $idempotency_field_name = ee('cartthrob:SettingsService')->get('cartthrob', 'idempotency_field_name');
            if ($this->idempotent_first) {
                ee('cartthrob:IdempotencyService')->saveStatus(ee()->input->post($idempotency_field_name));
            }

            ee()->load->library('javascript');

            if (!$this->return) {
                $this->return = (ee()->input->get_post('return')) ? ee()->input->get_post('return',
                    true) : ee()->uri->uri_string();
            }

            $url = $this->parsePath($this->return);

            if ($this->is_secure() || $this->bool_string(ee()->input->post('secure_return'))) {
                $url = $this->secure_url($url);
            }

            $flashdata = [
                'success' => !$this->errors(),
                'errors' => $this->errors(),
                'return' => $url,
                $idempotency_field_name => ee('cartthrob:IdempotencyService')->generateKey(),
            ];

            if (AJAX_REQUEST && ee()->config->item('secure_forms') === 'y') {
                $flashdata['CSRF_TOKEN'] = ee()->functions->add_form_security_hash('{csrf_token}');
            }

            // temp. store the current value of end_script, in case this call is nested inside another hook's call
            $end_script = ee()->extensions->end_script;

            foreach ($flashdata as $key => $value) {
                ee()->session->set_flashdata($key, $value);
            }

            if (ee()->input->post('ERR')) {
                $this->set_show_errors($this->bool_string($this->encrypt->decode(ee()->input->post('ERR')), true));
            }

            if ($this->errors()) {
                $this->callback($this->error_callback);

                if ($this->show_errors && !AJAX_REQUEST) {
                    if (ee()->input->post('error_handling') === 'inline') {
                        foreach ($this->values as $key) {
                            $value = ee()->input->post($key);

                            // custom_data[foo] => custom_data:foo
                            if (is_array($value)) {
                                foreach ($value as $k => $v) {
                                    $this->add_form_variable($key . ':' . $k, $v);
                                }
                            } else {
                                $this->add_form_variable($key, $value);
                            }
                        }

                        loadCartThrobPath();

                        ee()->core->generate_page();

                        ee()->extensions->end_script = $end_script;

                        return;
                    }

                    // if this is not loaded.... then the user_message template can not be output as part of show_error 2.6x
                    // basically the exception class's show_error looks to see if TMPL is set... if not it outputs the general_error.php file... which we don't want.
                    if (!isset(ee()->TMPL)) {
                        ee()->load->library('template', null, 'TMPL');
                    }
                    // since we'll be removing post in a minute, I'm creating temporary variables to store some stuff that would otherwise rely on post's existance
                    $errors = $this->errors();
                    $error_header = $this->error_header;
                    if (!empty($_POST)) {
                        unset($_POST); // we're unsetting post because show_error... a near useless function that is intended to replace show_user_error will otherwise insert a javascript back link which will then be replaced with [removed] link and will show some effed up code. show_message function of EE's output class basically has a bug. If that gets fixed, we can undo this so that the back link will be shown correctly. FOr now, removing $_POST will remove the bad back link.
                        $_POST = [];
                    }

                    return show_error($errors, $status_code = 500, $error_header);
                }
            }

            if (!$this->errors()) {
                $this->callback($this->success_callback);
            }

            ee()->functions->redirect($url);
        }

        /**
         * @param bool $action_complete_on_error
         * @return bool
         */
        public function validate($action_complete_on_error = false)
        {
            $field_name = ee('cartthrob:SettingsService')->get('cartthrob', 'idempotency_field_name');
            $idempotency = ee()->input->post($field_name);
            if ($idempotency) {
                if (!ee('cartthrob:IdempotencyService')->isValid($idempotency)) {
                    if (ee('cartthrob:IdempotencyService')->waitForRequest($idempotency)) {
                        $this->idempotent_first = false;
                        $this->action_complete();
                        exit;
                    }

                    $this->add_error('failed_idempotency', 'Form ' . ee()->input->post($field_name) . ' has already been submitted');

                    return false;
                }
            }

            $inline = ee()->input->post('error_handling') === 'inline';

            $labels = [];

            ee()->lang->loadfile('form_validation');

            foreach ($this->required_keys() as $key) {
                if (!ee()->input->post($key)) {
                    if ($inline) {
                        $this->add_error($key, lang('required'));
                    } else {
                        $this->add_error($key, sprintf(lang('validation_required'), $key));
                    }

                    return false;
                }
            }

            if (!is_array($this->rules)) {// meaning, someone has already done this processing
                // $this->rules = $this->process_rules(ee()->input->post('rules'));
                $this->rules = $this->unserialize($this->encrypt->decode(ee()->input->post('RLS')), false);

                // the unserialize failed, and we may be subject to tampering
                if (!is_array($this->rules) && in_array('RLS', $this->required_keys())) {
                    if ($inline) {
                        $this->add_error($key, lang('required'));
                    } else {
                        $this->add_error($key, sprintf(lang('validation_required'), 'RLS'));
                    }

                    return false;
                }

                foreach ($this->required as $field) {
                    if (!isset($this->rules[$field])) {
                        $this->rules[$field] = 'required';
                    } else {
                        if (strpos($this->rules[$field], 'required') === false) {
                            $this->rules[$field] = 'required|' . $this->rules[$field];
                        }
                    }
                }
            }

            if (!$this->rules && !$this->captcha) {
                return true;
            }

            foreach ($this->rules as $key => $rules) {
                // will convert item_options[item_option_1] to Item Options (Item Option 1) if validation_item_options_item_option_1 is not found in the language file.
                if (preg_match('/^([a-z_]+)(\[\d+\])?\[(.*)\]$/', $key, $match)) {
                    $lang_key_base = 'validation_' . $match[1];
                    $lang_key_full = $lang_key_base . '_' . $match[3];

                    $main_language_line = ee()->lang->line($lang_key_base);
                    $full_language_line = ee()->lang->line($lang_key_full);
                    $sub_language_line = ee()->lang->line($match[3]);

                    // main language line does not exist
                    // replace _ with spaces
                    if ($main_language_line === $lang_key_base) {
                        $main_language_line = ucwords(str_replace(['validation_', '_'], ' ', $main_language_line));
                    }

                    // sub language line does not exist
                    // replace _ with spaces
                    if ($sub_language_line === $match[3]) {
                        $sub_language_line = ucwords(str_replace(['validation_', '_'], ' ', $sub_language_line));
                    }

                    // there is no full language line
                    if ($label = $full_language_line === $lang_key_full) {
                        $label = sprintf($main_language_line, $sub_language_line);
                    } // oh wow... there is a full language line. Let's use that
                    else {
                        $label = $full_language_line;
                    }
                } elseif (preg_match('/^([a-z_]+)(:\d+)?:(.*)$/', $key, $match)) {
                    $key = $match[1];

                    if ($match[2]) {
                        $key .= '[' . $match[2] . ']';
                    }

                    $key .= '[' . $match[3] . ']';

                    $lang_key = 'validation_' . $match[1] . '_' . $match[3];

                    if (($label = ee()->lang->line($lang_key)) === $lang_key) {
                        $label = sprintf(ee()->lang->line('validation_' . $match[1]), $match[3]);
                    }
                } elseif (($label = ee()->lang->line('validation_' . $key)) === 'validation_' . $key) {
                    $label = $key;
                }

                $labels[$key] = $label;

                ee()->form_validation->set_rules($key, $label, $rules);
            }

            if ($this->rules && !$valid = ee()->form_validation->run()) {
                foreach (ee()->form_validation->_error_array as $field => $error) {
                    if (!$inline && isset($labels[$field]) && $error === lang('required')) {
                        $error = sprintf(lang('validation_required'), $labels[$field]);
                    }

                    $this->add_error($field, $error);
                }

                return false;
            }

            if ($this->captcha) {
                if (!$captcha = ee()->input->post('captcha', true)) {
                    $this->add_error('captcha', lang('captcha_required'));

                    return false;
                } else {
                    ee()->db->where('word', $captcha);
                    ee()->db->where('ip_address', ee()->input->ip_address());
                    ee()->db->where('date > ', '(UNIX_TIMESTAMP()-7200)', false);

                    if (!ee()->db->count_all_results('captcha')) {
                        $this->add_error('captcha', lang('captcha_incorrect'));

                        return false;
                    } else {
                        ee()->db->where('word', $captcha);
                        ee()->db->where('ip_address', ee()->input->ip_address());
                        ee()->db->where('date < ', '(UNIX_TIMESTAMP()-7200)', false);

                        ee()->db->delete('captcha');
                    }
                }
            }

            return true;
        }

        /**
         * @return array
         */
        public function error_variables()
        {
            return $this->form_variables();
        }

        /**
         * @return array
         */
        public function form_variables()
        {
            $hash = $this->build_form_hash();

            $variables = [
                'errors_exist' => 0,
                'global_errors_exist' => 0,
                'field_errors_exist' => 0,
                'global_errors:count' => 0,
                'field_errors:count' => 0,
                'errors' => [],
                'global_errors' => [],
                'field_errors' => [],
            ];

            if (isset($this->global_form_variables[$hash])) {
                foreach ($this->global_form_variables[$hash] as $key => $value) {
                    $variables[$key] = ee('Security/XSS')->clean($value);
                }
            }

            $total_results = count($this->errors($hash));

            if ($total_results > 0) {
                $count = 1;

                foreach ($this->errors($hash) as $key => $value) {
                    $first_row = ($count === 1);

                    $last_row = ($count === $total_results);

                    $error = [
                        'error' => $value,
                        'field' => $key,
                        'global_error' => 0,
                        'field_error' => 0,
                        'error:count' => $count,
                        'error:total_results' => $total_results,
                        'first_row' => $first_row,
                        'last_row' => $last_row,
                        'first_error' => $first_row,
                        'last_error' => $last_row,
                    ];

                    if (is_int($key) || (function_exists('ctype_digit') && ctype_digit($key))) {
                        $error['field'] = '';

                        $error['global_error'] = '1';

                        $variables['global_errors:count']++;

                        $variables['global_errors_exist'] = 1;

                        $variables['global_errors'][] = $error;
                    } else {
                        if (preg_match_all('/\[(.+?)\]/', $key, $matches)) {
                            $secondary_key = $key;

                            foreach ($matches[0] as $i => $replace) {
                                $secondary_key = str_replace($replace, ':' . $matches[1][$i], $key);
                            }

                            $variables['error:' . $secondary_key] = $value;
                        }

                        $error['field_error'] = '1';

                        $variables['error:' . $key] = $value;

                        $variables['field_errors_exist'] = 1;

                        $variables['field_errors:count']++;

                        $variables['field_errors'][] = $error;
                    }

                    $variables['errors'][] = $error;

                    $count++;
                }

                $variables['errors_exist'] = '1';
            } else {
                if (preg_match_all('#{(global_|field_)?errors(.*?)}(.*){/\\1errors}#s', ee()->TMPL->tagdata,
                    $matches)) {
                    foreach ($matches[0] as $i => $replace) {
                        $variables[substr($replace, 1, -1)] = '';
                    }
                }

                array_unshift($variables['errors'], []);
                array_unshift($variables['global_errors'], []);
                array_unshift($variables['field_errors'], []);
            }

            foreach (ee()->TMPL->var_single as $key) {
                if (strpos($key, 'error:') === 0 && !isset($variables[$key])) {
                    $variables[$key] = '';
                } else {
                    if (strncmp($key, 'encode ', 6) === 0) {
                        $params = ee('Variables/Parser')->parseTagParameters(substr($key, 6));

                        $variables[$key] = '';

                        if (isset($params['name'])) {
                            // we just want the name
                            if (!isset($params['value'])) {
                                $variables[$key] = $this->convert_input_name($params['name']);
                            } else {
                                $variables[$key] = $this->convert_input_value($params['name'], $params['value']);
                            }
                        }
                    }
                }
            }

            foreach ($this->options as $field_name => $options) {
                $field_name = 'options:' . $field_name;

                $variables[$field_name] = [];

                foreach ($options as $option_value => $option_name) {
                    $variables[$field_name][] = [
                        'option_value' => $this->encrypt->encode($option_value),
                        'option_name' => $option_name,
                    ];
                }
            }

            if (preg_match_all('#{if captcha}(.*?){/if}#s', ee()->TMPL->tagdata, $matches)) {
                foreach ($matches[0] as $i => $full_match) {
                    if ($this->captcha) {
                        $tagdata = ee()->TMPL->parse_variables_row($matches[1][$i], [
                            'captcha_word' => '',
                            'captcha' => ee('Captcha')->create(),
                        ]);

                        $tagdata = ee()->TMPL->swap_var_single('captcha', ee('Captcha')->create(), $tagdata);

                        $variables[substr($full_match, 1, -1)] = $tagdata;
                    } else {
                        $variables[substr($full_match, 1, -1)] = '';
                    }
                }
            }

            return $variables;
        }

        /**
         * @param $string
         * @param bool $default
         * @return bool
         */
        protected function bool_string($string, $default = false)
        {
            switch (strtolower($string)) {
                case 'true':
                case 't':
                case 'yes':
                case 'y':
                case 'on':
                case '1':
                    return true;
                    break;
                case 'false':
                case 'f':
                case 'no':
                case 'n':
                case 'off':
                case '0':
                    return false;
                    break;
                default:
                    return $default;
            }
        }

        /**
         * gives us a little more obscurity
         * for our encrypted boolean form values
         * @param bool $bool
         * @return string
         */
        protected function create_bool_string($bool = false)
        {
            switch (rand(1, 6)) {
                case 1:
                    $string = ($bool) ? 'true' : 'false';
                    break;
                case 2:
                    $string = ($bool) ? 't' : 'f';
                    break;
                case 3:
                    $string = ($bool) ? 'yes' : 'no';
                    break;
                case 4:
                    $string = ($bool) ? 'y' : 'n';
                    break;
                case 5:
                    $string = ($bool) ? 'on' : 'off';
                    break;
                case 6:
                    $string = ($bool) ? '1' : '0';
                    break;
            }

            $output = '';

            foreach (str_split($string) as $char) {
                $output .= (rand(0, 1)) ? $char : strtoupper($char);
            }

            return $output;
        }

        /**
         * @param $data
         * @param bool $force_array
         * @return array|bool|mixed
         */
        protected function unserialize($data, $force_array = true)
        {
            if (is_array($data)) {
                return $data;
            }

            if (false === ($data = @unserialize($data))) {
                return ($force_array) ? [] : false;
            }

            return $data;
        }

        /**
         * @return bool
         */
        protected function is_secure()
        {
            return isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on';
        }

        /**
         * @param $url
         * @param bool $domain
         * @return mixed
         */
        protected function secure_url($url, $domain = false)
        {
            if ($domain) {
                $url = preg_replace('/(https?:\/\/)([^\/]+)(.*)/', '\\1' . $domain . '\\3', $url);
            }

            return str_replace('http://', 'https://', $url);
        }

        /**
         * Callback caller
         *
         * @param array|string|bool $callback a function name, or an array($object, $method), or an array($object, $method, $arg1, $arg2, ...)
         */
        protected function callback($callback)
        {
            if (is_array($callback) && ($count = count($callback)) > 1) {
                $args = null;

                if ($count > 2) {
                    $args = $callback;

                    $callback = [array_shift($args), array_shift($args)];
                }

                if (method_exists($callback[0], $callback[1]) && is_callable($callback)) {
                    if (is_null($args)) {
                        call_user_func($callback);
                    } else {
                        call_user_func_array($callback, $args);
                    }
                }
            } else {
                if (is_string($callback) && function_exists($callback)) {
                    $callback();
                }
            }
        }

        /**
         * @param $name
         * @return string
         */
        private function convert_input_name($name)
        {
            foreach (['form_data', 'encoded_form_data', 'encoded_numbers', 'encoded_bools'] as $which) {
                foreach ($this->$which as $key => $alias) {
                    if ($which === 'form_data') {
                        $key = $alias;
                    }

                    if ($key === $name) {
                        return $alias;
                    }
                }
            }

            return '';
        }

        /**
         * @param $name
         * @param $value
         * @return \ExpressionEngine\Service\Encrypt\A
         */
        protected function convert_input_value($name, $value)
        {
            foreach (['form_data', 'encoded_form_data', 'encoded_numbers', 'encoded_bools'] as $which) {
                foreach ($this->$which as $key => $alias) {
                    if ($key !== $name) {
                        continue;
                    }

                    switch ($which) {
                        case 'encoded_form_data':
                            return $this->encrypt->encode($value);
                        case 'encoded_numbers':
                            return $this->encrypt->encode(Number::sanitize($value));
                        case 'encoded_bools':
                            return $this->encrypt->encode($this->create_bool_string($this->bool_string($value)));
                    }

                    return $value;
                }
            }
        }

        /**
         * @param $path
         * @return mixed|string|string[]|null
         */
        protected function parsePath($path)
        {
            if (!$path) {
                $path = ee()->config->item('site_url');
            }

            if (strpos($path, '{site_url}') !== false) {
                $path = str_replace('{site_url}', ee()->functions->fetch_site_index(1), $path);
            }

            if (strpos($path, '{path=') !== false) {
                $path = preg_replace_callback('/' . LD . 'path=[\042\047]?(.*?)[\042\047]?' . RD . '/',
                    [ee()->functions, 'create_url'], $path);
            }

            if (!preg_match("#^(http:\/\/|https:\/\/|www\.|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})#i", $path)) {
                if (strpos($path, '/') !== 0) {
                    $path = ee()->functions->create_url($path);
                }
            }

            return $path;
        }

        /**
         * @param $string
         * @return array
         */
        protected function param_string_to_array($string)
        {
            $values = [];

            if ($string) {
                foreach (explode('|', $string) as $value) {
                    if (strpos($value, ':') !== false) {
                        $value = explode(':', $value);

                        $values[$value[0]] = $value[1];
                    } else {
                        $values[$value] = $value;
                    }
                }
            }

            return $values;
        }
    }
}
