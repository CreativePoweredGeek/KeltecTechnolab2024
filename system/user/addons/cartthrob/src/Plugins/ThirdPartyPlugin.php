<?php

namespace CartThrob\Plugins;

trait ThirdPartyPlugin
{
    public $version;
    public $settings;

    /**
     * Activate the extension
     */
    public function activate_extension()
    {
        $data = [
            'class' => get_called_class(),
            'method' => 'register',
            'hook' => 'cartthrob_boot',
            'settings' => serialize($this->settings),
            'priority' => 5,
            'version' => $this->version,
            'enabled' => 'y',
        ];

        ee()->db->insert('extensions', $data);
    }
}
