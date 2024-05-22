<?php

namespace CartThrob\Controllers\Cp\Routes;

use CartThrob\Controllers\Cp\AbstractRoute;
use CartThrob\Exceptions\Controllers\Cp\RouteException;

class Install extends AbstractRoute
{
    /**
     * @var string
     */
    protected $route_path = 'install';

    /**
     * @var string
     */
    protected string $cp_page_title = 'installation';

    /**
     * @var array
     */
    protected array $errors = [];

    /**
     * @var bool
     */
    protected bool $installed = false;

    /**
     * @param $id
     * @return AbstractRoute
     * @throws RouteException
     */
    public function process($id = false): AbstractRoute
    {
        if (!empty($_POST)) {
            $this->doInstall();
        }

        $vars = [
            'cp_page_title' => lang('installation'),
            'base_url' => ee('CP/URL')->make($this->base_url . '/install'),
            'save_btn_text' => lang('install'),
            'save_btn_text_working' => lang('installing'),
        ];

        ee()->load->library('mbr_addon_builder');

        ee()->mbr_addon_builder->initialize(['module_name' => $this->module_name]);

        $package = ee()->mbr_addon_builder->get_package();

        $vars['templates_installed'] = $package['templates_installed'];
        $vars['template_errors'] = $package['template_errors'];
        $vars['sections'] = [];

        if (is_array($package['install_template_groups']) && count($package['install_template_groups'])) {
            $vars['sections']['template_groups'][] = [
                'title' => lang('template_groups_install'),
                'caution' => true,
                'fields' => [
                    'template_groups' => [
                        'label' => 'NAME',
                        'type' => 'checkbox',
                        'choices' => $package['install_template_groups'],
                        'value' => $package['templates_installed'],
                    ],
                ],
            ];
        }

        if (is_array($package['install_channels']) && count($package['install_channels'])) {
            $vars['sections']['channels'][] = [
                'title' => lang('channels_install'),
                'caution' => true,
                'fields' => [
                    'channels' => [
                        'label' => 'NAME',
                        'type' => 'checkbox',
                        'choices' => $package['install_channels'],
                        'value' => $package['templates_installed'],
                    ],
                ],
            ];
        }

        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/install', $vars);
        $this->addBreadcrumb($this->url($this->getRoutePath(), false), $this->getCpPageTitle());

        return $this;
    }

    /**
     * @return void
     * @throws RouteException
     */
    private function doInstall()
    {
        ee()->load->library('package_installer', ['xml' => PATH_THIRD . $this->module_name . '/installer/installer.xml']);

        $this->installTemplates();
        $this->installChannels();

        foreach (ee()->package_installer->installed() as $installed) {
            ee()->logger->developer($installed);
        }

        if ($this->installed) {
            ee('CP/Alert')->makeInline('message_success')
                ->asSuccess()
                ->withTitle(lang('install_success'))
                ->addToBody(ee()->package_installer->installed())
                ->defer();
            ee()->functions->redirect($this->url($this->getRoutePath()));
            exit;
        } else {
            ee('CP/Alert')->makeInline('message_failure')
                ->asIssue()
                ->withTitle(lang('install_error'))
                ->addToBody($this->errors)
                ->defer();
            ee()->functions->redirect($this->url($this->getRoutePath()));
            exit;
        }
    }

    /**
     * Install templates
     * @return void
     */
    private function installTemplates()
    {
        $templates = (array)ee()->input->post('template_groups');

        foreach ($templates as $key => $value) {
            if ($value == '') {
                unset($templates[$key]);
            }
        }

        if (empty($templates)) {
            return;
        }

        foreach (ee()->package_installer->packages() as $row_id => $package) {
            if (!in_array($row_id, $templates)) {
                ee()->package_installer->removePackage($row_id);
            }
        }

        ee()->package_installer->setTemplatePath(PATH_THIRD . $this->module_name . '/installer/templates/')->installTemplates();
        if (!empty(ee()->package_installer->errors())) {
            array_merge($this->errors, ee()->package_installer->errors());
        }

        if (!empty(ee()->package_installer->installed())) {
            $this->installed = true;
        }

        ee()->package_installer->reloadConfig();
    }

    /**
     * Install channels
     * @return void
     */
    private function installChannels()
    {
        $channels = (array)ee()->input->post('channels');

        foreach ($channels as $key => $value) {
            if ($value == '') {
                unset($channels[$key]);
            }
        }

        if (empty($channels)) {
            return;
        }

        foreach (ee()->package_installer->packages() as $row_id => $package) {
            if (!in_array($row_id, $channels)) {
                ee()->package_installer->removePackage($row_id);
            }
        }

        ee()->load->helper(['inflector']);
        ee()->package_installer->installChannels();
        if (!empty(ee()->package_installer->errors())) {
            array_merge($this->errors, ee()->package_installer->errors());
        }

        if (!empty(ee()->package_installer->installed())) {
            $this->installed = true;
        }

        ee()->package_installer->reloadConfig();
    }
}
