<?php

namespace CartThrob\Controllers\Cp\Routes\Settings;

use CartThrob\Controllers\Cp\AbstractSettingsRoute;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\Members as SettingsForm;

class Members extends AbstractSettingsRoute
{
    /**
     * @var string
     */
    protected $route_path = 'settings/members';

    protected string $cp_page_title = 'nav_members';

    /**
     * @return AbstractForm
     */
    public function getForm(): AbstractForm
    {
        return new SettingsForm();
    }

    public function process($id = false): AbstractSettingsRoute
    {
        parent::process($id);
        $this->addBreadcrumb($this->url('settings/members', true), 'General');

        return $this;
    }
}
