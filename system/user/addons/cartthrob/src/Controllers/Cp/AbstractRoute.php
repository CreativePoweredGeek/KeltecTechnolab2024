<?php

namespace CartThrob\Controllers\Cp;

use CartThrob\Controllers\AbstractRoute as CpRoute;
use CartThrob\Exceptions\Controllers\Cp\RouteException;
use CartThrob\Services\SettingsService;
use ExpressionEngine\Model\Channel\Channel as ChannelModel;

abstract class AbstractRoute extends CpRoute
{
    /**
     * @var SettingsService
     */
    protected $settings = null;

    /**
     * @var string
     */
    protected string $module_name = 'cartthrob';

    /**
     * @var string
     */
    protected string $cp_page_title = '';

    /**
     * @var bool
     */
    protected $active_sidebar = false;

    /**
     * @var string
     */
    protected $route_path = '';

    /**
     * The Control Panel Heading text
     * @var string
     */
    protected $heading = '';

    /**
     * The raw HTML body for the Control Panel view
     * @var string
     */
    protected $body = ' ';

    /**
     * An array of urls => text for breadcrumbs
     * @var array
     */
    protected $breadcrumbs = [];

    /**
     * @var int
     */
    public $per_page = 25;

    /**
     * @var string
     */
    protected $base_url = '';

    /**
     * @var array[]
     */
    protected $sidebar_data = [];

    /**
     * @var null
     */
    protected $sidebar = null;

    public function __construct()
    {
        ee()->lang->loadfile('cartthrob_routes', 'cartthrob');
        ee()->load->helper('array');
        $this->base_url = 'addons/settings/' . $this->getModuleName();
        $this->sidebar_data = ee('cartthrob:SidebarService')->toArray();
        if ($this->sidebar_data) {
            $this->generateSidebar();
        }

        $this->settings = ee('cartthrob:SettingsService');
//        $providers = ee('App')->getProviders();
//        if (ee('Addon')->get('cartthrob_subscriptions')->isInstalled()) {
//            $this->sidebar->addHeader('Subscriptions', ee('CP/URL')->make('/addons/settings/cartthrob_subscriptions'));
//        }
//
//        if (ee('Addon')->get('cartthrob_order_manager')->isInstalled()) {
//            $this->sidebar->addHeader('Order Management', ee('CP/URL')->make('/addons/settings/cartthrob_order_manager'));
//        }

        $this->sidebar
            ->addHeader(lang('ct.route.nav.docs'))
            ->withUrl('https://www.cartthrob.com/docs')
            ->urlIsExternal();

        if ($this->settings->get('cartthrob', 'license_number') == '') {
            ee('CP/Alert')->makeInline('shared-form')
                ->asWarning()
                ->withTitle(lang('license_not_installed'))
                ->addToBody(lang('license_please_enter'))
                ->cannotClose()
                ->now();
        }
    }

    /**
     * @return string
     */
    protected function getCpPageTitle(): string
    {
        return $this->cp_page_title;
    }

    /**
     * @param $channel_id
     * @return bool
     */
    protected function validateChannelId($channel_id): bool
    {
        if (!$channel_id) {
            return false;
        }

        $channel = ee('Model')
            ->get('Channel')
            ->filter('channel_id', $channel_id)
            ->first();

        if ($channel instanceof ChannelModel) {
            return true;
        }

        return false;
    }

    /**
     * @param $id
     * @return AbstractRoute
     */
    abstract public function process($id = false): AbstractRoute;

    /**
     * @return string
     */
    public function getHeading(): string
    {
        return $this->heading;
    }

    /**
     * @param string $heading
     * @return $this
     */
    public function setHeading(string $heading): AbstractRoute
    {
        $this->heading = $heading;

        return $this;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $view
     * @param array $variables
     * @return $this
     */
    public function setBody(string $view, array $variables = []): AbstractRoute
    {
        $variables = $this->prepareBodyVars($variables);
        $this->body = ee('View')->make($this->module_name . ':' . $view)->render($variables);

        return $this;
    }

    /**
     * Compiles some universal variables for use in views
     * @param array $variables
     */
    protected function prepareBodyVars(array $variables = [])
    {
        return array_merge([
            'cp_page_title' => $this->getHeading(),
            'base_url' => $this->base_url,
        ], $variables);
    }

    /**
     * @return array
     */
    public function getBreadcrumbs(): array
    {
        return $this->breadcrumbs;
    }

    /**
     * @param $url
     * @param $text
     * @return $this
     */
    protected function addBreadcrumb(string $url, string $text): AbstractRoute
    {
        $this->breadcrumbs[$url] = lang($text);

        return $this;
    }

    /**
     * @param array $breadcrumbs
     * @return $this
     */
    protected function setBreadcrumbs(array $breadcrumbs = []): AbstractRoute
    {
        $this->breadcrumbs = $breadcrumbs;

        return $this;
    }

    /**
     * @param $path
     * @param bool $with_base
     * @param array $query
     * @return mixed
     */
    protected function url(string $path, bool $with_base = true, array $query = []): string
    {
        if ($with_base) {
            $path = $this->base_url . '/' . $path;
        }

        return ee('CP/URL')->make($path, $query)->compile();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'heading' => lang($this->getHeading()),
            'breadcrumb' => $this->getBreadcrumbs(),
            'body' => $this->getBody(),
        ];
    }

    /**
     * @param string $id
     * @return string
     * @throws RouteException
     */
    protected function getRoutePath($id = ''): string
    {
        if ($this->route_path == '') {
            throw new RouteException("Your route_path property isn't setup in your Route object!");
        }

        return $this->route_path . ($id !== false && $id != '' ? '/' . $id : '');
    }

    /**
     * @throws RouteException
     */
    protected function generateSidebar(): void
    {
        $this->sidebar = ee('CP/Sidebar')->make();
        $active = false;
        foreach ($this->sidebar_data as $title => $sidebar) {
            if ($sidebar['path'] != '') {
                $subsHeader = $this->sidebar
                    ->addHeader(lang($title), $this->url($sidebar['path']));
            } else {
                $subsHeader = $this->sidebar
                    ->addHeader(lang($title));
            }
            if (isset($sidebar['list']) && is_array($sidebar['list'])) {
                $subsHeaderList = $subsHeader->addBasicList();
                foreach ($sidebar['list'] as $title => $path) {
                    $url = $path['path'] ?? $path;
                    $with_base_url = $path['with_base_url'] ?? true;
                    if ($this->active_sidebar == $url && !$active) {
                        $subsHeaderList->addItem(lang($title), $this->url($url, $with_base_url))->isActive();
                        $active = true;
                    } elseif ($url == $this->getRoutePath() && !$active) {
                        $subsHeaderList->addItem(lang($title), $this->url($url, $with_base_url))->isActive();
                        $active = true;
                    } else {
                        $subsHeaderList->addItem(lang($title), $this->url($url, $with_base_url));
                    }
                }
            }
        }
    }
}
