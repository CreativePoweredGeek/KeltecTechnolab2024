<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

use HopStudios\HopMinifizer\Hop\HopConfig;

require_once PATH_THIRD . HopConfig::SHORT_NAME . '/Hop/HopInstaller.php';
require_once PATH_THIRD . 'hop_minifizer/classes/Hop_minifizer_helper.php';

class Hop_minifizer_upd extends HopInstaller
{
    public $name        = HopConfig::ADDON_NAME;
    public $version     = HopConfig::VERSION;
    public $short_name  = HopConfig::SHORT_NAME;
    public $class_name  = HopConfig::CLASS_NAME;

    public $has_cp_backend = 'y';
    public $has_publish_fields = 'n';

    public function __construct()
    {
        parent::__construct();

        $this->checkLicense();
    }

    public function install()
    {
        parent::install();

        $this->initialDbScripts();

        return true;
    }

    public function uninstall()
    {
        parent::uninstall();

        $this->hopUninstall();

        return true;
    }

    public function update($current = '')
    {
        if ($current === $this->version) {
            return false;
        }

        parent::update($current);

        return true;
    }

    private function initialDbScripts($current = '')
    {
        $this->setupLicenseSettings();
        if (empty($current)) {
            $data = [
                'class' => 'Hop_minifizer',
                'method' => 'test_amazon_access_keys',
            ];

            ee()->db->insert('actions', $data);
        }
    }
}