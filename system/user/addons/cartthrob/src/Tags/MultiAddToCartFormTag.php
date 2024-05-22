<?php

namespace CartThrob\Tags;

use EE_Session;

class MultiAddToCartFormTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library(['languages', 'form_builder']);
    }

    public function process()
    {
        $this->guardLoggedOutRedirect();

        ee()->languages->set_language($this->param('language'));

        $TMPL = [
            'tagdata' => $this->tagdata(),
            'var_single' => $this->getVarSingle(),
            'var_pair' => $this->getVarPair(),
            'tagparams' => $this->params(),
        ];

        foreach ($TMPL as $key => $value) {
            ee()->TMPL->{$key} = $value;
        }

        $data = array_merge(
            $this->itemOptionVars(),
            $this->globalVariables(true)
        );

        $data['encoded:yes'] = ee('cartthrob:EncryptionService')->encode('Yes');
        $data['encoded:no'] = ee('cartthrob:EncryptionService')->encode('No');
        ee()->form_builder->initialize([
            'classname' => 'Cartthrob',
            'method' => 'multi_add_to_cart_action',
            'params' => $this->params(),
            'content' => $this->parseVariablesRow($data),
            'form_data' => [
                'secure_return',
                'language',
                'return',
            ],
            'encoded_form_data' => [
                'shipping' => 'SHP',
                'weight' => 'WGT',
                'permissions' => 'PER',
                'upload_directory' => 'UPL',
                'class' => 'CLS',
            ],
            'encoded_bools' => [
                'allow_user_price' => 'AUP',
                'allow_user_shipping' => 'AUS',
                'allow_user_weight' => 'AUW',
                'on_the_fly' => 'OTF',
                'json' => 'JSN',
                'tax_exempt' => 'TXE',
                'shipping_exempt' => 'SHX',
            ],
        ]);

        if ($this->param('no_tax')) {
            ee()->form_builder->set_encoded_bools('no_tax', 'NTX')->set_params($this->params());
        } elseif ($this->param('tax_exempt')) {
            ee()->form_builder->set_encoded_bools('tax_exempt', 'NTX')->set_params($this->params());
        }

        if ($this->param('no_shipping')) {
            ee()->form_builder->set_encoded_bools('no_shipping', 'NSH')->set_params($this->params());
        } elseif ($this->param('shipping_exempt')) {
            ee()->form_builder->set_encoded_bools('shipping_exempt', 'NSH')->set_params($this->params());
        }

        return ee()->form_builder->form();
    }
}
