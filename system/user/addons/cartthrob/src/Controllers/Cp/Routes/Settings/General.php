<?php

namespace CartThrob\Controllers\Cp\Routes\Settings;

use CartThrob\Controllers\Cp\AbstractSettingsRoute;
use CartThrob\Exceptions\Controllers\Cp\RouteException;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\General as SettingsForm;

class General extends AbstractSettingsRoute
{
    /**
     * @var string
     */
    protected $route_path = 'settings/general';

    /**
     * @var string
     */
    protected string $cp_page_title = 'nav_general_settings';

    /**
     * @var bool
     */
    protected $tabs_view = true;

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
