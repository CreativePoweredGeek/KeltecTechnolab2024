<?php

namespace CartThrob\Tags;

use CartThrob\Services\SettingsService;
use EE_Session;

class PluginSettingsTag extends Tag
{
    /**
     * @var SettingsService|\CI_Controller|mixed|null
     */
    protected ?SettingsService $settings = null;

    /**
     * @param EE_Session $session
     */
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);
        $this->settings = ee('cartthrob:SettingsService');
    }

    /**
     * @return bool|mixed|string
     */
    public function process()
    {
        if (!$this->hasParam('type') || !$this->hasParam('plugin')) {
            return $this->noResults('plugin_settings');
        }

        $data = null;
        switch ($this->param('type')) {
            case 'tax':
                $data = $this->getTaxPlugin($this->param('plugin'));
                break;

            case 'shipping':
                $data = $this->getShippingPlugin($this->param('plugin'));
                break;

            case 'payment':
                $data = $this->getPaymentPlugin($this->param('plugin'));
                break;
        }

        if (is_null($data)) {
            return lang('plugin_not_found');
        }

        return $this->parseVariablesRow($data);
    }

    /**
     * @param string $plugin
     * @return array|null
     */
    protected function getPaymentPlugin(string $plugin): ?array
    {
        $enabled = $this->settings->get('cartthrob', 'enabled_gateways') ?? [];
        if (in_array($plugin, $enabled) || in_array('Cartthrob_' . $plugin, $enabled)) {
            $settings = $this->settings->get('cartthrob', 'Cartthrob_' . $plugin . '_settings');
            if (!$settings) {
                $settings = $this->settings->get('cartthrob', $plugin . '_settings');
            }

            if ($settings) {
                return $settings;
            }
        }

        return null;
    }

    /**
     * @param string $plugin
     * @return array|null
     */
    protected function getTaxPlugin(string $plugin): ?array
    {
        $enabled = $this->settings->get('cartthrob', 'tax_plugin');
        if ($enabled == 'Cartthrob_tax_' . $plugin || $enabled == $plugin) {
            $settings = $this->settings->get('cartthrob', 'Cartthrob_tax_' . $plugin . '_settings');
            if (!$settings) {
                $settings = $this->settings->get('cartthrob', $plugin . '_settings');
            }

            if ($settings) {
                return $settings;
            }
        }

        return null;
    }

    /**
     * @param string $plugin
     * @return array|null
     */
    protected function getShippingPlugin(string $plugin): ?array
    {
        $enabled = $this->settings->get('cartthrob', 'shipping_plugin');
        if ($enabled == 'Cartthrob_shipping_' . $plugin || $enabled == $plugin) {
            $settings = $this->settings->get('cartthrob', 'Cartthrob_shipping_' . $plugin . '_settings');
            if (!$settings) {
                $settings = $this->settings->get('cartthrob', $plugin . '_settings');
            }

            if ($settings) {
                return $settings;
            }
        }

        return null;
    }
}
