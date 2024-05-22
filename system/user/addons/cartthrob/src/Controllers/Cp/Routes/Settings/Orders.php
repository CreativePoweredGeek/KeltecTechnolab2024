<?php

namespace CartThrob\Controllers\Cp\Routes\Settings;

use CartThrob\Controllers\Cp\AbstractSettingsRoute;
use CartThrob\Exceptions\Controllers\Cp\RouteException;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\Orders as SettingsForm;

class Orders extends AbstractSettingsRoute
{
    /**
     * @var string
     */
    protected $route_path = 'settings/orders';

    /**
     * @var string
     */
    protected string $cp_page_title = 'nav_orders';

    /**
     * @var bool
     */
    protected $tabs_view = true;

    /**
     * @return AbstractForm
     */
    public function getForm(): AbstractForm
    {
        $form = new SettingsForm();
        $form->setChannelId($this->channel_id);
        $form->setBaseUrl($this->base_url);

        return $form;
    }

    /**
     * @param $id
     * @return AbstractSettingsRoute
     * @throws RouteException
     */
    public function process($id = false): AbstractSettingsRoute
    {
        $orders_channel = $this->settings->get('cartthrob', 'orders_channel');
        if (!$this->validateChannelId($orders_channel)) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asWarning()
                ->withTitle(lang('must_set_orders_channel'))
                ->defer();
            ee()->functions->redirect($this->url('orders/set-channel'));
        }

        $this->channel_id = $orders_channel;

        parent::process($id);
        $this->addBreadcrumb($this->url($this->getRoutePath(), true), $this->getCpPageTitle());

        return $this;
    }
}
