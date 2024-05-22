<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* The software is provided "as is", without warranty of any
* kind, express or implied, including but not limited to the
* warranties of merchantability, fitness for a particular
* purpose and noninfringement. in no event shall the authors
* or copyright holders be liable for any claim, damages or
* other liability, whether in an action of contract, tort or
* otherwise, arising from, out of or in connection with the
* software or the use or other dealings in the software.
* -----------------------------------------------------------
* Amici Infotech - Super Address Field
*
* @package      superAddressField
* @author       Mufi
* @copyright    Copyright (c) 2019, Amici Infotech.
* @link         http://expressionengine.amiciinfotech.com/super-address-field
* @filesource   ./system/expressionengine/third_party/super_address_field/mcp.super_address_field.php
*/
require PATH_THIRD.'super_address_field/config.php';

class Super_address_field_mcp
{

	public $site_id;
	public $vars;

	public function __construct()
	{
		$this->site_id = ee()->config->item("site_id");
		ee()->load->library('Super_address_field_lib', null, 'superAddress');
	}

	public function index()
	{

		$this->_startupForm();

        if(isset($_POST) && count($_POST))
        {
        	$ret = ee()->superAddress->generalSettingsPost();

        	if($ret === true)
        	{
        		ee('CP/Alert')->makeInline('shared-form')
        			->asSuccess()
        			->withTitle(lang('settings_updated'))
        			->addToBody(lang('general_settings_saved'))
        			->defer();
        		ee()->functions->redirect(ee()->superAddress->createURL());
        	}
        	else
        	{
        		$this->vars['errors'] = $ret;
        	}
        }

		$this->vars = ee()->superAddress->generalSettings($this->vars);

		return array(
			'heading'    => lang('general_settings'),
			'body'       => "<div class='" . ((version_compare(APP_VER, '4.0.0', '<')) ? 'box' : '') . "'>" . ee('View')->make('ee:_shared/form')->render($this->vars) . "</div>",
			'breadcrumb' => array(
				ee('CP/URL', 'addons/settings/super_address_field/')->compile() => lang('super_address_field')
			),
		);

	}

	function _startupForm()
	{

		$this->vars = array();

		/*CSRF and XID is same after EE V 2.8.0. For previous versions (Backword compatability)*/
		if(version_compare(APP_VER, '2.8.0', '<'))
		{
			$this->vars['csrf_token']   = ee()->security->get_csrf_hash();
			$this->vars['xid']          = ee()->functions->add_form_security_hash('{XID_HASH}'); 
		}
		else
		{
			$this->vars['csrf_token']   = XID_SECURE_HASH;
			$this->vars['xid']          = XID_SECURE_HASH;
		}
		
	}

}