<?php

namespace CartThrob\Tags;

use CartThrob\Math\Number;
use EE_Session;

class AddToCartFormTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library(['form_builder', 'languages']);
        ee()->load->model('subscription_model');
    }

    public function process()
    {
        $this->guardLoggedOutRedirect();

        ee()->form_builder->initialize([
            'form_data' => [
                'entry_id',
                'quantity',
                'secure_return',
                'title',
                'language',
                'return',
            ],
            'encoded_form_data' => array_merge(
                ee()->subscription_model->encoded_form_data(),
                [
                    'shipping' => 'SHP',
                    'weight' => 'WGT',
                    'permissions' => 'PER',
                    'upload_directory' => 'UPL',
                    'class' => 'CLS',
                ]
            ),
            'encoded_numbers' => array_merge(
                ee()->subscription_model->encoded_numbers(),
                [
                    'price' => 'PR',
                    'expiration_date' => 'EXP',
                ]
            ),
            'encoded_bools' => [
                'allow_user_price' => 'AUP',
                'allow_user_weight' => 'AUW',
                'allow_user_shipping' => 'AUS',
                'on_the_fly' => 'OTF',
                'show_errors' => ['ERR', true],
                'license_number' => 'LIC',
            ],
            'array_form_data' => [
                'item_options',
            ],
            'encoded_array_form_data' => [
                'meta' => 'MET',
            ],
            'classname' => 'Cartthrob',
            'method' => 'add_to_cart_action',
            'params' => $this->params(),
        ]);

        // can't just shove these in the encoded bools, or they will always be FALSE by default unless set.
        // since the field type overrides them, we don't even want them set here unless explicitly set.
        foreach (ee()->subscription_model->encoded_bools() as $key => $value) {
            if ($this->hasParam($key)) {
                ee()->form_builder->set_encoded_bools($key, $value)->set_params($this->params());
            }
        }

        if ($this->hasParam('no_tax')) {
            ee()->form_builder->set_encoded_bools('no_tax', 'NTX')->set_params($this->params());
        } elseif ($this->hasParam('tax_exempt')) {
            ee()->form_builder->set_encoded_bools('tax_exempt', 'NTX')->set_params($this->params());
        }

        if ($this->hasParam('no_shipping')) {
            ee()->form_builder->set_encoded_bools('no_shipping', 'NSH')->set_params($this->params());
        } elseif ($this->hasParam('shipping_exempt')) {
            ee()->form_builder->set_encoded_bools('shipping_exempt', 'NSH')->set_params($this->params());
        }

        $data = array_merge(
            $this->itemOptionVars($this->param('entry_id')),
            $this->globalVariables(true)
        );

        $this->addEncodedOptionVars($data);

        foreach ($this->getVarSingle() as $var) {
            if (preg_match('/^inventory:reduce(.+)$/', $var, $match)) {
                $data[$match[0]] = '';

                $var_params = ee('Variables/Parser')->parseTagParameters($match[1]);

                if (!empty($var_params['entry_id'])) {
                    if (empty($var_params['quantity'])) {
                        $var_params['quantity'] = 1;
                    } else {
                        $var_params['quantity'] = abs(Number::sanitize($var_params['quantity']));
                    }

                    ee()->form_builder->set_hidden('inventory_reduce[' . $var_params['entry_id'] . ']', $var_params['quantity']);
                }
            }
        }

        ee()->languages->set_language($this->param('language'));
        ee()->form_builder->set_content(ee()->template_helper->parse_variables_row($data));

        return ee()->form_builder->form();
    }
}
