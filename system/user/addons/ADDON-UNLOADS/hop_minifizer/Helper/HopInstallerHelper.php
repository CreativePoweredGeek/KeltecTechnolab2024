<?php
use ExpressionEngine\Service\Addon\Installer;

if (class_exists('\ExpressionEngine\Service\Addon\Installer') && ! class_exists('HopInstallerHelper')) {
	class HopInstallerHelper extends Installer {}
} elseif ( ! class_exists('HopInstallerHelper')) {
	class HopInstallerHelper {
		public function __construct() {
			// Nothing...
		}
	}
}