<?php

namespace CartThrob\Controllers\Cp\Routes\Settings;

use CartThrob\Controllers\Cp\AbstractSettingsRoute;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\Coupons as SettingsForm;

class Coupons extends AbstractSettingsRoute
{
    /**
     * @var string
     */
    protected $route_path = 'settings/coupons';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.route.header.coupons_settings';

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

    public function process($id = false): AbstractSettingsRoute
    {
        $coupons_channel = $this->settings->get('cartthrob', 'coupon_code_channel');
        if (!$this->validateChannelId($coupons_channel)) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asWarning()
                ->withTitle(lang('must_set_coupons_channel'))
                ->defer();
            ee()->functions->redirect($this->url('coupons/set-channel'));
        }

        $this->channel_id = $coupons_channel;

        parent::process($id);
        $this->addBreadcrumb($this->url($this->getRoutePath(), true), $this->getCpPageTitle());

        return $this;
    }
}
