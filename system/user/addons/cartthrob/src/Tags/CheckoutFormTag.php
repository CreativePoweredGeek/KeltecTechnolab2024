<?php

namespace CartThrob\Tags;

use CartThrob\Model\Vault as VaultModel;
use EE_Session;

class CheckoutFormTag extends Tag
{
    private ?string $formExtra = '';

    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library([
            'form_builder',
            'api/api_cartthrob_payment_gateways',
            'api/api_cartthrob_shipping_plugins',
            'template_helper',
        ]);
        ee()->load->model('subscription_model');
    }

    public function process()
    {
        $this->guardLoggedOutRedirect();

        $data = [];

        if (ee()->cartthrob->cart->is_empty()) {
            ee()->template_helper->tag_redirect($this->param('cart_empty_redirect'));
        }

        if ($this->param('live_rates')) {
            return $this->require_shipping_update();
        }

        if (!$this->param('id')) {
            $this->setParam('id', 'checkout_form');
        }

        if (!ee()->cartthrob->store->config('allow_gateway_selection')) {
            $this->clearParam('gateway');
        } elseif ($this->param('gateway')) {
            ee()->api_cartthrob_payment_gateways->set_gateway($this->param('gateway'));
        } elseif (ee()->cartthrob->cart->customer_info('gateway')) {
            ee()->api_cartthrob_payment_gateways->set_gateway(ee()->cartthrob->cart->customer_info('gateway'));
        }

        // ensure vault_id belongs to member for the love of all that's holy...
        if ($this->hasParam('vault_id')) {
            $vault = ee('cartthrob:VaultService')->getMemberVault($this->param('vault_id'), $this->getMemberId());
            if (!$vault instanceof VaultModel) {
                $this->clearParam('vault_id');
            } else {
                $this->setParam('gateway', $vault->gateway);
                ee()->api_cartthrob_payment_gateways->set_gateway($vault->gateway);
            }
        }

        if (!$this->hasParam('vault_id') && str_contains($this->tagdata(), '{gateway_fields}')) {
            $this->setTagdata(str_replace('{gateway_fields}', ee()->api_cartthrob_payment_gateways->gateway_fields(), $this->tagdata()));
        } elseif ($this->hasParam('vault_id') && str_contains($this->tagdata(), '{vault_fields}')) {
            $this->setTagdata(str_replace('{vault_fields}', ee()->api_cartthrob_payment_gateways->gateway_fields(false, 'vault_fields'), $this->tagdata()));
        }

        if ($this->hasParam('required') && strncmp($this->param('required'), 'not ', 4) === 0) {
            $this->setParam('not_required', substr($this->param('required'), 4));
            $this->clearParam('required');
        }

        $this->addEncodedOptionVars($data);

        ee()->form_builder->initialize([
            'captcha' => !$this->memberLoggedIn() && ee()->cartthrob->store->config('checkout_form_captcha'),
            'form_data' => [
                'action',
                'secure_return',
                'return',
                'language',
                'authorized_redirect',
                'failed_redirect',
                'declined_redirect',
                'processing_redirect',
                'create_user',
                'member_id',
                'order_id',
            ],
            'encoded_form_data' => array_merge(
                [
                    'required' => 'REQ',
                    'file' => 'FI',
                    'not_required' => 'NRQ',
                    'gateway' => 'gateway',
                    'permissions' => 'PER',
                ],
                ee()->subscription_model->encoded_form_data()
            ),
            'encoded_numbers' => array_merge(
                [
                    'price' => 'PR',
                    'shipping' => 'SHP',
                    'tax' => 'TX',
                    'group_id' => 'GI',
                    'role_id' => 'GI',
                    'expiration_date' => 'EXP',
                    'vault_id' => 'vault_id',
                ],
                ee()->subscription_model->encoded_numbers()
            ),
            'encoded_bools' => array_merge(
                [
                    'allow_user_price' => 'AUP',
                    'allow_user_shipping' => 'AUS',
                    'on_the_fly' => 'OTF',
                    'license_number' => 'LIC',
                    'force_vault' => 'VLT',
                    'force_processing' => 'FPR',
                ],
                ee()->subscription_model->encoded_bools()
            ),
            'classname' => 'Cartthrob',
            'method' => 'checkout_action',
            'params' => $this->params(),
            'action' => ee()->cartthrob->store->config('payment_system_url'),
        ]);

        // setting the subscription id. if a subscription id is set the contents of the cart are removed, and only the subscription itself is updated.
        if ($this->param('sub_id')) {
            ee()->form_builder->set_hidden('sub_id', $this->param('sub_id'));
        }

        if ($this->param('gateway_method')) {
            ee()->form_builder->set_hidden('gateway_method', $this->param('gateway_method'));
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

        if (!$this->hasParam('custom_js') || $this->param('custom_js') !== true) {
            $gateway = ee()->api_cartthrob_payment_gateways->gateway();
            if ($gateway) {
                $gatewayInitialized = new $gateway['classname']();
                $this->formExtra = @$gatewayInitialized->form_extra(true);
            }
        }

        // do this after initialize so captch vars are set
        $variables = $this->globalVariables(true);

        ee()->form_builder->set_content($this->parseVariablesRow($variables));

        if ($this->param('order_id') || $this->param('member_id')) {
            ee()->form_builder->set_hidden('save_member_data', 0);
        }

        if ($this->hasParam('vault_id')) {
            return ee()->form_builder->form();
        } else {
            $form = ee()->form_builder->form() . $this->formExtra;

            // coilpack lovin'
            ee()->TMPL->add_data(form_close() . $this->formExtra, 'form_close');

            return $form;
        }
    }

    private function require_shipping_update()
    {
        ee()->cartthrob->cart->shipping();

        if ($this->hasParam('shipping_plugin')) {
            ee()->api_cartthrob_shipping_plugins->set_plugin($this->param('shipping_plugin'));
        }

        if (!ee()->api_cartthrob_shipping_plugins->shipping_options()) {
            $error = ee()->cartthrob->cart->custom_data('shipping_error');
        } else {
            $error = null;
        }

        if (ee()->cartthrob->cart->custom_data('shipping_requires_update') || $error != null) {
            if ($error) {
                $content = "<span class='error_message'>Shipping Error: " . ee()->cartthrob->cart->custom_data('shipping_error') . '</span>';
            } else {
                $content = '';
            }

            $content .= '
				{exp:cartthrob:customer_info}
					{exp:cartthrob:update_cart_form return="" id="shipping_update_required"}
						<div>
							<h2>' . ee()->lang->line('shipping_update_required') . '</h2>

							<fieldset class="shipping" id="shipping">
								<legend>Shipping</legend>

								<label for="shipping_address">Shipping Address
								<input type="text" value="{customer_shipping_address}" name="shipping_address" id="shipping_address" />
								</label>

								<label for="shipping_address2">Shipping Address (apartment/suite number)
								<input type="text" value="{customer_shipping_address2}" name="shipping_address2" id="shipping_address2" />
								</label>

								<label for="shipping_city">Shipping City
								<input type="text" value="{customer_shipping_city}" name="shipping_city" id="shipping_city" />
								</label>

								<label for="shipping_state">Shipping State
								{exp:cartthrob:state_select  id="shipping_state" name="shipping_state" selected="{customer_shipping_state}" add_blank="yes" }
								</label>

								<label for="shipping_zip">Shipping Zip/Postal Code
								<input type="text" value="{customer_shipping_zip}" name="shipping_zip" id="shipping_zip" />
								</label>

								<label for="shipping_country">Shipping Country
								    {exp:cartthrob:country_select name="shipping_country_code" id="shipping_country" selected="{customer_shipping_country_code}"}
								</label>

								<label for="shipping_country">Shipping Option
									<select name="shipping_option">
									    {exp:cartthrob:get_shipping_options shipping_plugin="' . $this->param('shipping_plugin') . '"}
									        <option value="{rate_short_name}" {selected}>{rate_title} - {price}</option>
									    {/exp:cartthrob:get_shipping_options}
									</select>
								</label>

							</fieldset>

							<input type="submit" value="Submit" name="submit"/>
						</div>
					{/exp:cartthrob:update_cart_form}
				{/exp:cartthrob:customer_info}
				';

            $this->setTagdata($content);

            return true;
        }

        return null;
    }
}
