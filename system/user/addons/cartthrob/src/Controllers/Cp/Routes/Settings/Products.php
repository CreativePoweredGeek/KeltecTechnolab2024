<?php

namespace CartThrob\Controllers\Cp\Routes\Settings;

use CartThrob\Controllers\Cp\AbstractSettingsRoute;
use CartThrob\Exceptions\Controllers\Cp\RouteException;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\Products as SettingsForm;
use ExpressionEngine\Library\CP\Table;

class Products extends AbstractSettingsRoute
{
    /**
     * @var string
     */
    protected $route_path = 'settings/products';

    protected string $cp_page_title = 'product_options_header';

    /**
     * @return AbstractForm
     */
    protected function getForm(): AbstractForm
    {
        return new SettingsForm();
    }

    /**
     * @param $id
     * @return AbstractSettingsRoute
     * @throws RouteException
     */
    public function process($id = false): AbstractSettingsRoute
    {
        $form = $this->getForm();
        $defaults = [];
        $vars = [];
        $form->setData($this->settings->settings('cartthrob'));
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form->setData($_POST);
            $result = $form->validate($_POST);
            if ($result->isValid()) {
                if ($this->settings->save('cartthrob', $_POST)) {
                    ee('CP/Alert')->makeInline('shared-form')
                        ->asSuccess()
                        ->withTitle(lang('settings_saved'))
                        ->defer();
                    ee()->functions->redirect($this->url($this->getRoutePath()));
                }
            } else {
                $defaults = array_merge($_POST, $defaults);
                $form->setData($defaults);
                $vars['errors'] = $result;
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('validation_settings_failed'))
                    ->now();
            }
        }

        $vars['cp_page_title'] = lang($this->getCpPageTitle());
        $vars['base_url'] = $this->url($this->getRoutePath());
        $vars['sections'] = $form->generate();
        $vars['table'] = $this->getTable();
        $vars['save_btn_text'] = lang('save');
        $vars['save_btn_text_working'] = 'saving';

        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/product_settings', $vars);

        $this->addBreadcrumb($this->url('settings/general', true), 'Settings');
        $this->addBreadcrumb($this->url($this->getRoutePath(), false), $this->getCpPageTitle());

        return $this;
    }

    protected function getTable()
    {
        $product_channels = $this->settings->get('cartthrob', 'product_channels');
        $table = ee('CP/Table', [
            'lang_cols' => true,
            'class' => 'product_channels',
        ]);

        $table->setColumns([
            'ct.route.channel_id' => ['sort' => false],
            'ct.route.channel_name' => ['sort' => false],
            'ct.route.channel_short_name' => ['sort' => false],
            'ct.route.total_products' => ['sort' => false],
            'manage' => [
                'type' => Table::COL_TOOLBAR,
            ],
        ]);

        $table->setNoResultsText(sprintf(lang('no_found'), lang('product_channels')));

        $channels = ee('Model')
            ->get('Channel')
            ->filter('channel_id', 'IN', $product_channels);

        $data = [];
        foreach ($channels->all() as $channel) {
            $url = ee('CP/URL')->make($this->base_url . '/products/edit-channel/' . $channel->getId());
            $total = ee()->db->from('channel_titles')->where('channel_id', $channel->getId())->count_all_results();
            $data[] = [
                [
                    'content' => $channel->getId(),
                    'href' => $url,
                ],
                $channel->channel_title,
                $channel->channel_name,
                $total,
                ['toolbar_items' => [
                    'edit' => [
                        'href' => $url,
                        'title' => lang('edit'),
                    ],
                    'remove' => [
                        'href' => ee('CP/URL')->make($this->base_url . '/products/remove-channel/' . $channel->getId()),
                        'title' => lang('delete'),
                    ],
                ]],
            ];
        }

        $table->setData($data);

        $base_url = ee('CP/URL')->make($this->base_url);

        return $table->viewData($base_url);
    }
}
