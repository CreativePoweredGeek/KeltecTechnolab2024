<?php

namespace CartThrob\Controllers\Cp\Routes\Shipping;

use CartThrob\Controllers\Cp\AbstractPluginRoute;

class PerProduct extends AbstractPluginRoute
{
    /**
     * @var string
     */
    protected $route_path = 'shipping-plugins/per-product';

    /**
     * @var string
     */
    protected $active_sidebar = 'settings/shipping';

    /**
     * @var string
     */
    protected string $cp_page_title = 'shipping_defined_per_product';

    public function process($id = false): AbstractPluginRoute
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settings = [];
            if (ee()->input->post('enabled') == 1) {
                $settings['shipping_plugin'] = '';
            }

            if ($this->settings->save('cartthrob', $settings)) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('settings_saved'))
                    ->defer();
                ee()->functions->redirect($this->url('settings/shipping'));
            }
        }

        $defaults['enabled'] = $this->settings->get('cartthrob', 'shipping_plugin') == '' ? '1' : '0';
        $form = [
            [
                'title' => 'ct.enabled',
                'desc' => 'plugin_enabled_description',
                'fields' => [
                    'enabled' => [
                        'name' => 'enabled',
                        'type' => 'select',
                        'value' => $defaults['enabled'],
                        'required' => true,
                        'choices' => [
                            '1' => 'Yes',
                            '0' => 'No',
                        ],
                    ],
                ],
            ],
        ];

        $vars['cp_page_title'] = lang($this->getCpPageTitle());
        $vars['base_url'] = $this->url($this->getRoutePath($id));
        $vars['sections'] = ['activate_plugin' => $form];
        $vars['save_btn_text'] = lang('save');
        $vars['save_btn_text_working'] = 'saving';

        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/form', $vars);

        $this->addBreadcrumb($this->url('settings/general', true), 'Settings');
        $this->addBreadcrumb($this->url('settings/shipping', true), 'ct.route.header.shipping_options');
        $this->addBreadcrumb($this->url($this->getRoutePath(), false), $this->getCpPageTitle());

        return $this;
    }
}
