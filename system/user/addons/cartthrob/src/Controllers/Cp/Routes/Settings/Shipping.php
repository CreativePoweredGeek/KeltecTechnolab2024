<?php

namespace CartThrob\Controllers\Cp\Routes\Settings;

use CartThrob\Controllers\Cp\AbstractPluginRoute;
use EllisLab\ExpressionEngine\Library\CP\Table;

class Shipping extends AbstractPluginRoute
{
    /**
     * @var string
     */
    protected $route_path = 'settings/shipping';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.route.header.shipping_options';

    /**
     * @param false $id
     * @return AbstractPluginRoute
     */
    public function process($id = false): AbstractPluginRoute
    {
        $plugins = $this->getShippingPlugins();

        $shipping_plugin = $this->settings->get('cartthrob', 'shipping_plugin');
        $table = ee('CP/Table', [
            'autosort' => false,
            'autosearch' => false,
            'lang_cols' => true,
            'sort_dir' => 'desc',
            'sort_col' => 'id',
            'class' => 'notifications',
        ]);

        $table->setColumns([
            'plugin_name' => ['sort' => false],
            'details' => ['sort' => false],
            'active' => ['sort' => false, 'encode' => false],
            'manage' => [
                'type' => Table::COL_TOOLBAR,
            ],
        ]);

        $vars = [];
        $table->setNoResultsText(sprintf(lang('no_found'), lang('shipping_plugins')));

        // shipping plugins are weird in how they're stored.
        // If the value for $shipping_plugin is empty then it's the "Per Product" setting
        // But there's no Plugin file for it so... we get the below
        $url = ee('CP/URL')->make($this->base_url . '/shipping-plugins/per-product');
        $status = ($shipping_plugin == '' ? 'yes' : 'no');
        $status_css = ($status == 'yes' ? 'st-open' : 'st-pending');
        $data = [
            [
                [
                    'content' => lang('shipping_defined_per_product'),
                    'href' => $url,
                ],
                lang('by_weight_global_rate_note'),
                "<span class='" . $status_css . "'>" . lang($status) . '</span>',
                ['toolbar_items' => [
                    'edit' => [
                        'href' => $url,
                        'title' => lang('edit'),
                    ],
                ]],
            ],
        ];

        // madness end
        foreach ($plugins as $plugin) {
            $url = ee('CP/URL')->make($this->base_url . '/shipping-plugins/edit/' . element('classname', $plugin));
            $note = lang(element('note', $plugin));
            $status = ($shipping_plugin == element('classname', $plugin) ? 'yes' : 'no');
            $status_css = ($status == 'yes' ? 'st-open' : 'st-pending');
            $content = !empty($plugin['fulltext_title']) ? $plugin['fulltext_title'] : 'N/A';
            $data[] = [
                [
                    'content' => $content,
                    'href' => $url,
                ],
                $note,
                "<span class='" . $status_css . "'>" . lang($status) . '</span>',
                ['toolbar_items' => [
                    'edit' => [
                        'href' => $url,
                        'title' => lang('edit'),
                    ],
                ]],
            ];
        }

        $table->setData($data);

        $base_url = ee('CP/URL')->make($this->base_url);

        $vars['cp_page_title'] = lang($this->getCpPageTitle());
        $vars['base_url'] = $this->url($this->getRoutePath());
        $vars['table'] = $table->viewData($base_url);
        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/table', $vars);

        $this->addBreadcrumb($this->url('settings/general', true), 'Settings');
        $this->addBreadcrumb($this->url($this->getRoutePath(), false), $this->getCpPageTitle());

        return $this;
    }
}
