<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if( ! defined('PATH_THIRD')) { define('PATH_THIRD', APPPATH . 'third_party'); };
require_once PATH_THIRD . 'zenbu/addon.setup.php';
// require_once __DIR__.'/vendor/autoload.php';

use Zenbu\librairies\platform\ee\ArrayHelper;
use Zenbu\librairies\Settings;
use Zenbu\librairies\Fields;
use Zenbu\librairies\Sections;
use Zenbu\librairies\platform\ee\Base as Base;
use Zenbu\librairies\platform\ee\Lang;
use Zenbu\librairies\platform\ee\Session;
use Zenbu\librairies\platform\ee\Cache;
use Zenbu\librairies\platform\ee\Url;
use Zenbu\librairies\platform\ee\View;

class Zenbu_ext {

	var $name				= ZENBU_NAME;
	var $version 			= ZENBU_VER;
	var $addon_short_name 	= 'zenbu';
	var $description		= 'Extension companion to module of the same name';
	var $settings_exist		= ZENBU_SETTINGS_EXIST;
	var $docs_url			= 'https://zenbustudio.com/software/docs/zenbu';
	var $settings        	= array();

	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	public function __construct($settings='')
	{
		$this->settings			= $settings;

		//	----------------------------------------
		//	Load Session Libraries if not available
		//	(eg. in cp_js_end hook) - EE 2.6
		//	----------------------------------------

		// Get the old last_call first, just to be sure we have it
		$old_last_call = ee()->extensions->last_call;

		if ( ! isset(ee()->session) || ! isset(ee()->session->userdata) )
        {

            if (file_exists(APPPATH . 'libraries/Localize.php'))
            {
                ee()->load->library('localize');
            }

            if (file_exists(APPPATH . 'libraries/Remember.php'))
            {
                ee()->load->library('remember');
            }

            if (file_exists(APPPATH.'libraries/Session.php'))
            {
                ee()->load->library('session');
            }
        }


	} // END __construct()

	// --------------------------------------------------------------------

	/**
	 * cp_js_end
	 * Hook: cp_js_end
	 * @return string $output The added JS.
	 */
	public function cp_js_end()
	{
		ee()->lang->loadfile('zenbu');
		ee()->lang->loadfile('content', 'cp');

		$permissions = array_flip(ee('Model')->get('zenbu:Permission')
			->filter('userGroupId', Session::user()->group_id)
			->filter('value', 'y')
			->all()
			->map(function($p) {
				return $p->setting;
			}));

		$output = '';

		// Sorry I forgot to add this, devs:
		if (ee()->extensions->last_call !== FALSE)
		{
			$output = ee()->extensions->last_call;
		}

		// Replaces the main CP nav with the addon
		if(isset($permissions['edit_replace']))
		{
			$output .= View::render('extension/edit_replace.js.twig');
		}

		$output .= View::render('extension/index.js.twig');

		return $output;
	} // END cp_js_end()

	// --------------------------------------------------------------------


	/**
	 * entry_save_and_close_redirect
	 * This hook is executed when a member clicks Save & Close on the publish form, and provides an opportunity to change where the member is redirected to.
	 * Hook: entry_save_and_close_redirect
	 * @param $entry (ChannelEntry) – Model object of the channel entry being saved
	 * @return string $output String of the URL to redirect to.
	 */
	public function entry_save_and_close_redirect($entry)
	{
		$permissions = array_flip(ee('Model')->get('zenbu:Permission')
			->filter('userGroupId', Session::user()->group_id)
			->filter('value', 'y')
			->all()
			->map(function($p) {
				return $p->setting;
			}));

		if (ee()->extensions->last_call !== FALSE)
		{
			$url = ee()->extensions->last_call;

			return $url;

		}

		if(isset($permissions['edit_replace']))
		{
			return Url::zenbuUrl();
		}

		//	----------------------------------------
		//	The fallback - If Zenbu doesn't kick in.
		//	----------------------------------------

		$filters = [];

		if(isset($entry->channel_id))
		{
			$filters = ['filter_by_channel' => $entry->channel_id];
		}

		$url = ee('CP/URL')->make('publish/edit', $filters);

		return $url;
	}


	public function after_channel_entry_delete($entry, $values)
	{
		$permissions = array_flip(ee('Model')->get('zenbu:Permission')
			->filter('userGroupId', Session::user()->group_id)
			->filter('value', 'y')
			->all()
			->map(function($p) {
				return $p->setting;
			}));

		if (ee()->extensions->last_call !== FALSE)
		{
			ee()->extensions->last_call;

			if(isset($permissions['edit_replace']))
			{
				ee()->functions->redirect(Url::zenbuUrl());
			}

		}
	}


	/**
	 * Settings Form
	 *
	 * @param	Array	Settings
	 * @return 	void
	 */
	public function settings_form()
	{
		ee()->load->helper('form');
		ee()->load->library('table');

		$query = ee()->db->query("SELECT settings FROM exp_extensions WHERE class = '".__CLASS__."'");
		$license = '';

		if($query->num_rows() > 0)
		{
			foreach($query->result_array() as $result)
			{
				$settings = unserialize($result['settings']);
				if(!empty($settings))
				{
					$license = $settings['license'];
				}
			}
		}

		$vars = array();

		$vars['settings'] = array(
			'license'	=> form_input('license', $license, "size='80'"),
			);


		return View::render('extension/settings.twig', $vars);
	} // END settings_form()

	// --------------------------------------------------------------------

	/**
	* Save Settings
	*
	* This public function provides a little extra processing and validation
	* than the generic settings form.
	*
	* @return void
	*/
	public function save_settings()
	{
		if (empty($_POST))
		{
			show_error(ee()->lang->line('unauthorized_access'));
		}

		unset($_POST['submit']);

		$settings = $_POST;

		ee()->db->where('class', __CLASS__);
		ee()->db->update('extensions', array('settings' => serialize($settings)));

		ee()->session->set_flashdata(
			'message_success',
		 	ee()->lang->line('preferences_updated')
		);
	} // END save_settings()

	// --------------------------------------------------------------------


	public function activate_extension()
	{
		$data[] = array(
			'class'    => __CLASS__,
			'method'   => "cp_js_end",
			'hook'     => "cp_js_end",
			'settings' => serialize(array()),
			'priority' => 100,
			'version'  => $this->version,
			'enabled'  => "y"
		 );
		$data[] = array(
			'class'    => __CLASS__,
			'method'   => "entry_save_and_close_redirect",
			'hook'     => "entry_save_and_close_redirect",
			'settings' => serialize(array()),
			'priority' => 100,
			'version'  => $this->version,
			'enabled'  => "y"
		);
		$data[] = array(
			'class'    => __CLASS__,
			'method'   => "after_channel_entry_delete",
			'hook'     => "after_channel_entry_delete",
			'settings' => serialize(array()),
			'priority' => 100,
			'version'  => $this->version,
			'enabled'  => "y"
		);

		// insert in database
		foreach($data as $key => $data)
		{
			ee()->db->insert('exp_extensions', $data);
		}
	}


	public function disable_extension()
	{
	  ee()->db->where('class', __CLASS__);
	  ee()->db->delete('exp_extensions');
	}

	  /**
	 * Update Extension
	 *
	 * This public function performs any necessary db updates when the extension
	 * page is visited
	 *
	 * @return 	mixed	void on update / false if none
	 */
	public function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}

		if ($current < $this->version)
		{
			// Update to version 1.0
		}

		if(version_compare($current, '2.1.0', '<'))
		{
			$data[] = array(
				'class'    => __CLASS__,
				'method'   => "cp_custom_menu",
				'hook'     => "cp_custom_menu",
				'settings' => serialize($this->settings),
				'priority' => 100,
				'version'  => $this->version,
				'enabled'  => "y"
			 );

			// insert in database
			foreach($data as $key => $data)
			{
				ee()->db->insert('exp_extensions', $data);
			}
		} // END 2.1.0 update script

		if(version_compare($current, '2.1.3', '<'))
		{
			ee()->db->update('exp_extensions',
				array(
					'method'   => 'after_channel_entry_save',
					'hook'     => 'after_channel_entry_save',
					'priority' => 900
					),
				array(
					'class'  => __CLASS__,
					'method' => 'send_to_addon_post_delete'
					)
			);
		} // END 2.1.3 update script

		if(version_compare($current, '2.2.1', '<'))
		{
			$data[] = array(
				'class'    => __CLASS__,
				'method'   => "sessions_end",
				'hook'     => "sessions_end",
				'settings' => serialize($this->settings),
				'priority' => 100,
				'version'  => $this->version,
				'enabled'  => "y"
			 );

			// insert in database
			foreach($data as $key => $data)
			{
				ee()->db->insert('exp_extensions', $data);
			}
		} // END 2.2.1 update script

		if(version_compare($current, '3.0.0', '<'))
		{
			unset($row_delete);

			$row_delete[] = array(
				'class'    => __CLASS__,
				'method'   => "cp_custom_menu",
				'hook'     => "cp_custom_menu",
			);

			$row_delete[] = array(
				'class'    => __CLASS__,
				'method'   => "sessions_end",
				'hook'     => "sessions_end",
			);

			$row_delete[] = array(
				'class'    => __CLASS__,
				'method'   => "send_to_addon_post_edit",
				'hook'     => "update_multi_entries_start",
			);

			$row_delete[] = array(
				'class'    => __CLASS__,
				'method'   => "after_channel_entry_save",
				'hook'     => "after_channel_entry_save",
			);

			// delete in database
			foreach($row_delete as $data)
			{
				ee()->db->delete('exp_extensions', $data);
			}

			unset($data);

			$data[] = array(
				'class'    => __CLASS__,
				'method'   => "entry_save_and_close_redirect",
				'hook'     => "entry_save_and_close_redirect",
				'settings' => serialize($this->settings),
				'priority' => 100,
				'version'  => $this->version,
				'enabled'  => "y"
			);

			// insert in database
			foreach($data as $key => $data)
			{
				ee()->db->insert('exp_extensions', $data);
			}

			// Rename custom "replace_edit_dropdown" named hook method to match the hook's name, "cp_js_end"
			ee()->db->update('exp_extensions', ['method' => 'cp_js_end'], ['class' => __CLASS__, 'method' => 'replace_edit_dropdown']);

		} // END 3.0.0 update script

		if(version_compare($current, '3.3.1', '<'))
		{
			unset($data);

			$data[] = array(
				'class'    => __CLASS__,
				'method'   => "after_channel_entry_delete",
				'hook'     => "after_channel_entry_delete",
				'settings' => serialize($this->settings),
				'priority' => 100,
				'version'  => $this->version,
				'enabled'  => "y"
			);

			// insert in database
			foreach($data as $key => $data)
			{
				ee()->db->insert('exp_extensions', $data);
			}
		} // END 3.3.1 update script

		ee()->db->where('class', __CLASS__);
		ee()->db->update(
					'extensions',
					array('version' => $this->version)
		);

	}




}
// END CLASS