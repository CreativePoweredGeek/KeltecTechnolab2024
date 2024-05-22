<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require PATH_THIRD.'super_export/config.php';

class Super_export_mcp
{

	public $site_id;
	public $vars;

	public function __construct()
	{
		$this->site_id = ee()->config->item("site_id");
		ee()->load->library('Super_export_settings_lib', null, 'exportSettings');

		// $version = '&amp;v=' . (static::DEBUG ? time() : $this->version);
		// $version = '&amp;v=' . time();
		$version = "";
		ee()->cp->load_package_css("super_export" . $version);
		ee()->cp->load_package_js("super_export" . $version);
	}

	public function index()
	{

		$this->_startupForm();
		$this->vars = ee()->exportSettings->entryList($this->vars);

		return array(
			'heading'    => lang('super_export'),
			'body'       => ee('View')->make('super_export:table')->render($this->vars),
			'breadcrumb' => array(),
		);

	}

	public function settings()
	{

		$this->_startupForm();
		$this->vars = ee()->exportSettings->settingsForm($this->vars);

		if(isset($_POST) && count($_POST))
		{

			$ret = ee()->exportSettings->settingsFormPost();

			if($ret === true)
			{

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('settings_updated'))
					->addToBody(lang('export_saved_successfully'))
					->defer();
				ee()->functions->redirect(ee()->exportSettings->createURL('settings'));

			}
			else
			{
				$this->vars['errors'] = $ret;
			}

		}

		return array(
			'heading'    => lang('general_settings'),
			'body'       => "<div class='" . ((version_compare(APP_VER, '4.0.0', '<')) ? 'box' : '') . "'>" . ee('View')->make('ee:_shared/form')->render($this->vars) . "</div>",
			'breadcrumb' => array(
				ee('CP/URL', 'addons/settings/super_export/')->compile() => lang('super_export')
			),
		);

	}

	public function entry($id = "")
	{

		$this->_startupForm();
		$this->vars['id'] = $id;

		if(isset($_POST) && count($_POST))
		{

			$ret = ee()->exportSettings->entryDataPost();

			if($ret === true)
			{

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('settings_updated'))
					->addToBody(lang('export_saved_successfully'))
					->defer();
				ee()->functions->redirect(ee()->exportSettings->createURL());

			}
			else
			{
				$this->vars['errors'] = $ret;
			}

		}

		$this->vars = ee()->exportSettings->entryData($this->vars);

		return array(
			'heading'    => ($id == "") ? lang('create_new_export') : lang('modify_export_settings'),
			'body'       => "<div class='" . ((version_compare(APP_VER, '4.0.0', '<')) ? 'box' : '') . "'>" . ee('View')->make('ee:_shared/form')->render($this->vars) . "</div>",
			'breadcrumb' => array(
				ee('CP/URL', 'addons/settings/super_export/')->compile() => lang('super_export')
			),
		);

	}

	public function delete()
	{

		if(isset($_POST) && isset($_POST['bulk_action']) && isset($_POST['selection']) && count($_POST['selection']))
		{

			switch ($_POST['bulk_action'])
			{

				case 'remove':
					$data = ee('Model')->get('super_export:ExportData', ee()->input->post('selection'))->all()->delete();
					ee('CP/Alert')->makeInline('shared-form')
						->asSuccess()
						->withTitle(lang('operation_successful'))
						->addToBody(lang('export_deleted_successfully'))
						->defer();
					ee()->functions->redirect(ee()->exportSettings->createURL());
				break;

				default:
				break;

			}

		}

	}

	public function download($id = "")
	{

		$vars = array(
			'id' 	 => $id,
			'type'   => (isset($_GET['type']) && $_GET['type'] == "ajax") ? "ajax" : "normal",
			'limit'  => (isset($_GET['limit'])) ? $_GET['limit'] : 0,
			'offset' => (isset($_GET['offset'])) ? $_GET['offset'] : 0,
		);
		ee()->load->library('Super_export_download', null, 'exportDownload');
		ee()->exportDownload->process($vars);

	}

	function renderDynamicChannelFields($param = "")
	{

		$channel_id = isset($_POST['channel_id']) ? $_POST['channel_id'] : "";
		if($channel_id != "")
		{
			$channel = ee('Model')->get('Channel', $channel_id)
			    // ->with('FieldGroups', 'CustomFields')
			->first();
		}

		$ret = array(
			'dynamic_fields' => ee()->exportSettings->_renderDynamicField($channel_id, array(), $channel),
			'super_status' 	 => ee()->exportSettings->_renderStatuses($channel_id, array(), $channel),
		);
		echo json_encode($ret);exit();

	}

	private function _startupForm()
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

		ee()->view->header = array(
			'toolbar_items' => array(
				'settings' => array(
					'href'  => ee()->exportSettings->createURL('settings'),
					'title' => lang('settings')
				)
			)
		);

	}

}