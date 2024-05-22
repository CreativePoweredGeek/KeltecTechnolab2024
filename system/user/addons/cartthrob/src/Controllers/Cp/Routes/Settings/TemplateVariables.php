<?php

namespace CartThrob\Controllers\Cp\Routes\Settings;

use CartThrob\Controllers\Cp\AbstractSettingsRoute;
use CartThrob\Exceptions\Controllers\Cp\RouteException;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\TemplateVariables as SettingsForm;

class TemplateVariables extends AbstractSettingsRoute
{
    /**
     * @var string
     */
    protected $route_path = 'settings/template-variables';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.route.nav.template_vars';

    /**
     * @var bool
     */
    protected $tabs_view = false;

    /**
     * @return AbstractForm
     */
    public function getForm(): AbstractForm
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
        parent::process($id);
        $this->addBreadcrumb($this->url($this->getRoutePath(), true), $this->getCpPageTitle());

        return $this;
    }
}
