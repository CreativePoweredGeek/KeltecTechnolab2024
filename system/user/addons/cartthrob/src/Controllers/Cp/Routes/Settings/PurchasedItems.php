<?php

namespace CartThrob\Controllers\Cp\Routes\Settings;

use CartThrob\Controllers\Cp\AbstractSettingsRoute;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\PurchasedItems as SettingsForm;

class PurchasedItems extends AbstractSettingsRoute
{
    /**
     * @var string
     */
    protected $route_path = 'settings/purchased-items';

    protected string $cp_page_title = 'nav_purchased_items';

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

    public function process($id = false): AbstractSettingsRoute
    {
        // ensure we have a set channel to store purchased data into
        $purchased_items_channel = $this->settings->get('cartthrob', 'purchased_items_channel');
        if (!$this->validateChannelId($purchased_items_channel)) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asWarning()
                ->withTitle(lang('must_set_purchased_items_channel'))
                ->defer();
            ee()->functions->redirect($this->url('purchased-items/set-channel'));
        }

        $this->channel_id = $purchased_items_channel;

        parent::process($id);
        $this->addBreadcrumb($this->url($this->getRoutePath(), true), $this->getCpPageTitle());

        return $this;
    }
}
