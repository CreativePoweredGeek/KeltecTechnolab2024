<?php

namespace CartThrob\Controllers\Cp\Routes\Settings;

use CartThrob\Controllers\Cp\AbstractSettingsRoute;
use CartThrob\Exceptions\Controllers\Cp\RouteException;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\Discounts as SettingsForm;

class Discounts extends AbstractSettingsRoute
{
    /**
     * @var string
     */
    protected $route_path = 'settings/discounts';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.route.header.discounts_settings';

    /**
     * @var bool
     */
    protected $tabs_view = false;

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
        $discounts_channel = $this->settings->get('cartthrob', 'discount_channel');
        if (!$this->validateChannelId($discounts_channel)) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asWarning()
                ->withTitle(lang('must_set_discounts_channel'))
                ->defer();
            ee()->functions->redirect($this->url('discounts/set-channel'));
        }

        $this->channel_id = $discounts_channel;

        parent::process($id);
        $this->addBreadcrumb($this->url($this->getRoutePath(), true), $this->getCpPageTitle());

        return $this;
    }
}
