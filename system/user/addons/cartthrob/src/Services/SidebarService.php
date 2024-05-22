<?php

namespace CartThrob\Services;

class SidebarService
{
    /**
     * @var array
     */
    protected array $compiled_sidebar = [];

    /**
     * The default Sidebar
     * @var array|array[]
     */
    protected array $sidebar_data = [
        'ct.route.nav.cartthrob' => [
            'path' => '',
            'list' => [
                'ct.route.nav.general_settings' => [
                    'path' => 'settings/general',
                    'with_base_url' => true,
                ],
                'ct.route.nav.cart_sessions_settings' => 'settings/cart-sessions',
                'ct.route.nav.products' => 'settings/products',
                'ct.route.nav.orders' => 'settings/orders',
                'ct.route.nav.purchased_items' => 'settings/purchased-items',
                'ct.route.nav.members' => 'settings/members',
                'ct.route.nav.notifications' => 'settings/notifications',
                'ct.route.nav.shipping' => 'settings/shipping',
                'ct.route.nav.taxes' => 'settings/taxes',
                'ct.route.nav.discounts' => 'settings/discounts',
                'ct.route.nav.coupons' => 'settings/coupons',
                'ct.route.nav.payment_gateways' => 'settings/payments',
                'ct.route.nav.template_vars' => 'settings/template-variables',
            ],
        ],
        'ct.route.nav.addons' => [
            'path' => '',
            'list' => [
            ],
        ],
        'ct.route.nav.utilities' => [
            'path' => '',
            'list' => [
                'ct.route.nav_installation' => 'install',
                'ct.route.nav.settings_files' => 'import-export',
                'ct.route.nav.tax_db' => 'tax-db',
                'ct.route.nav.vaults' => 'vaults',
            ],
        ],
    ];

    /**
     * @return $this
     */
    public function compile(): SidebarService
    {
        $this->compiled_sidebar = $this->sidebar_data;
        if (ee()->extensions->active_hook('cp_menu_array') === true) {
            $data = ee()->extensions->call('cp_menu_array', $this->compiled_sidebar);
            $this->compiled_sidebar = ee()->extensions->last_call;
        }

        return $this;
    }

    /**
     * @param string $addon
     * @return bool
     */
    public function isAddonActive(string $addon): bool
    {
        $parts = explode('/', ee()->uri->uri_string());
        if (in_array($addon, $parts)) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $this->compile();

        return $this->compiled_sidebar;
    }
}
