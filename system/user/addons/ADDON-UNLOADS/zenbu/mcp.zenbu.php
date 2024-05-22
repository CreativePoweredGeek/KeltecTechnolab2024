<?php
require 'vendor/autoload.php';

use Zenbu\controllers\MainController;
use Zenbu\controllers\DisplaySettingsController;
use Zenbu\controllers\SavedSearchesController;
use Zenbu\controllers\PermissionsController;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * =======
 *  Zenbu
 * =======
 * See more data in your control panel entry listing
 * @version 	See addon.setup.php
 * @copyright 	Nicolas Bottari - Zenbu Studio 2011-2018
 * @author 		Nicolas Bottari - Zenbu Studio
 * ------------------------------
 *
 * *** IMPORTANT ***
 * I (Nicolas Bottari and Zenbu Studio) am not responsible for any
 * damage, data loss, etc caused directly or indirectly by the use of this add-on.
 * @license		See the license documentation (text file) included with the add-on.
 *
 * @link	http://zenbustudio.com/software/zenbu/
 * @link	http://zenbustudio.com/software/docs/zenbu/
 *
 */

class Zenbu_mcp {

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Main Page
	 *
	 * @access	public
	 */
	function index()
	{
		$controller = new MainController;
		return $controller->index();
	} // END index()

	function test()
	{
		var_dump($_GET);
		return 123;
	}

	// --------------------------------------------------------------------

	/**
	 * Run the search
	 *
	 * @return void
	 */
	function search()
	{
		$controller = new MainController;
		ee()->output->send_ajax_response($controller->search());
	} // END search()

	function fetch_fields()
	{
		$controller = new MainController;
		ee()->output->send_ajax_response($controller->fetch_fields());
	}

	function fetch_statuses()
	{
		$controller = new MainController;
		ee()->output->send_ajax_response($controller->fetch_statuses());
	}

	function fetch_authors()
	{
		$controller = new MainController;
		ee()->output->send_ajax_response($controller->fetch_authors());
	}

	function fetch_categories()
	{
		$controller = new MainController;
		ee()->output->send_ajax_response($controller->fetch_categories());
	}

	function forget_filters()
	{
		$controller = new MainController;
		ee()->output->send_ajax_response($controller->{__FUNCTION__}());
	}

	/**
	 * DISPLAY SETTINGS
	 */
	function fetch_display_settings()
	{
		$controller = new DisplaySettingsController;
		ee()->output->send_ajax_response($controller->{__FUNCTION__}());
	} // END function settings

	function save_display_settings_for_user()
	{
		$controller = new DisplaySettingsController;
		ee()->output->send_ajax_response($controller->{__FUNCTION__}());
	}

	function save_display_settings_for_group()
	{
		$controller = new DisplaySettingsController;
		ee()->output->send_ajax_response($controller->{__FUNCTION__}());
	}

	/**
	 * SAVED SEARCHES
	 */

	function fetch_saved_searches()
	{
		$controller = new SavedSearchesController;
		ee()->output->send_ajax_response($controller->{__FUNCTION__}());
	}

	function fetch_saved_search_filters()
	{
		$controller = new SavedSearchesController;
		ee()->output->send_ajax_response($controller->{__FUNCTION__}());
	}

	function save_search()
	{
		$controller = new SavedSearchesController;
		ee()->output->send_ajax_response($controller->{__FUNCTION__}());
	}

	function update_saved_searches()
	{
		$controller = new SavedSearchesController;
		ee()->output->send_ajax_response($controller->{__FUNCTION__}());
	}

	function delete_saved_searches()
	{
		$controller = new SavedSearchesController;
		ee()->output->send_ajax_response($controller->{__FUNCTION__}());
	}

	/**
	 * PERMISSIONS
	 */

	function fetch_permissions()
	{
		$controller = new PermissionsController;
		ee()->output->send_ajax_response($controller->{__FUNCTION__}());
	}

	function save_permissions()
	{
		$controller = new PermissionsController;
		ee()->output->send_ajax_response($controller->{__FUNCTION__}());
	}

	/**
	 * Zenbu Clear Cache Utility
	 * An attempt at an electroshock to restart caching
	 *
	 * @access	public
	 */
	function clearcache()
	{
		$controller = new MainController;
		$out = $controller->clear_zenbu_cache();
		return $out;
	} // END clearcache()

	// --------------------------------------------------------------------
}
// END CLASS

/* End of file mcp.zenbu.php */
/* Location: ./system/expressionengine/third_party/modules/zenbu/mcp.zenbu.php */