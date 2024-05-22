<?php namespace Zenbu\librairies\platform\ee;

require_once PATH_THIRD . 'zenbu/addon.setup.php';

use Twig_Loader_Filesystem;

class View
{
	public static function includeJs($file)
	{
		if(REQ != 'CP')
		{
			return;
		}

		if(is_array($file))
		{
			foreach($file as $f)
			{
				ee()->cp->add_to_foot('<script type="text/javascript" src="'.self::themesPath().'/'.$f.'?v='.ZENBU_VER.'"></script>');
			}
		}
		else
		{
			ee()->cp->add_to_foot('<script type="text/javascript" src="'.self::themesPath().'/'.$file.'?v='.ZENBU_VER.'"></script>');
		}
	}

	public static function includeCss($file)
	{
		if(REQ != 'CP')
		{
			return;
		}

		if(is_array($file))
		{
			foreach($file as $f)
			{
				ee()->cp->add_to_head('<link type="text/css" rel="stylesheet" href="'.self::themesPath().'/'.$f.'?v='.ZENBU_VER.'" />');
			}
		}
		else
		{
			ee()->cp->add_to_head('<link type="text/css" rel="stylesheet" href="'.self::themesPath().'/'.$file.'?v='.ZENBU_VER.'" />');
		}
	}

	public static function themesPath()
	{
		if(defined('URL_THIRD_THEMES'))
		{
			return URL_THIRD_THEMES.'zenbu';

		} else {

			return ee()->config->item('theme_folder_url').'third_party/zenbu';
		}
	}

	public static function render($path, $vars = array())
	{
		$vars['request']      = new Request();
		$vars['session']      = new Session();
		$vars['localize']     = new Localize();
		$vars['array_helper'] = new ArrayHelper();
		$vars['user']         = Session::user();

		if(! class_exists('\Twig_Autoloader'))
		{
			require_once PATH_THIRD.'zenbu/vendor/twig/twig/lib/Twig/Autoloader.php';
		}
		\Twig_Autoloader::register();

		$loader = new Twig_Loader_Filesystem(PATH_THIRD.'zenbu/views');
		$twig_cache_path = PATH_THIRD.'/../cache/'.ee()->config->item('site_short_name').'/zenbu/template_cache';
		$twig = new \Twig_Environment($loader, [
		    // 'cache' => SettingsBase::isDebugEnabled() ? FALSE : $twig_cache_path, // FALSE
		    'debug' => TRUE,
		    'autoescape' => FALSE,
		    // 'auto_reload' => TRUE,
		]);

		if(isset(ee()->config->item('zenbu')['debug']) && ee()->config->item('zenbu')['debug'] !== false)
		{
			$twig->addExtension(new \Twig_Extension_Debug());
		}
		$twig->addFunction('form_dropdown', new \Twig_Function_Function('form_dropdown'));
		$twig->addFunction('form_checkbox', new \Twig_Function_Function('form_checkbox'));
		$twig->addFunction('form_radio', new \Twig_Function_Function('form_radio'));
		$twig->addFunction('form_hidden', new \Twig_Function_Function('form_hidden'));
		$twig->addFunction('themes_path', new \Twig_Function_Function('Zenbu\librairies\platform\ee\View::themesPath'));
		$twig->addFunction('getCsrfInput', new \Twig_Function_Function('Zenbu\librairies\platform\ee\Session::getCsrfInput'));
		$twig->addFunction('cpUrl', new \Twig_Function_Function('Zenbu\librairies\platform\ee\Url::cpUrl'));
		$twig->addFunction('zenbuUrl', new \Twig_Function_Function('Zenbu\librairies\platform\ee\Url::zenbuUrl'));
		$twig->addFunction('cpEditEntryUrl', new \Twig_Function_Function('Zenbu\librairies\platform\ee\Url::cpEditEntryUrl'));
		$twig->addFunction('url', new \Twig_Function_Function('Zenbu\librairies\platform\ee\Url::frontUrl'));
		$twig->addFunction('findFontColor', new \Twig_Function_Function('Zenbu\librairies\platform\ee\Statuses::calculateFontColor'));
		$twig->addFunction('requestParam', new \Twig_Function_Function('Zenbu\librairies\platform\ee\Request::param'));
		$twig->addFilter(new \Twig_SimpleFilter('t', array('Zenbu\librairies\platform\ee\Lang', 't')));
		$twig->addFilter(new \Twig_SimpleFilter('call', array('Zenbu\librairies\Hook', 'call')));

		return $twig->render($path, $vars, TRUE);
	}

	/**
	 *
	 */
	public static function prepNativeBulkEditing()
	{
		ee()->javascript->set_global([
			'lang.remove_confirm' => lang('entry') . ': <b>### ' . lang('entries') . '</b>',

			'publishEdit.sequenceEditFormUrl' => ee('CP/URL')->make('publish/edit/entry/###')->compile(),
			'publishEdit.bulkEditFormUrl' => ee('CP/URL')->make('publish/bulk-edit')->compile(),
			'publishEdit.addCategoriesFormUrl' => ee('CP/URL')->make('publish/bulk-edit/categories/add')->compile(),
			'publishEdit.removeCategoriesFormUrl' => ee('CP/URL')->make('publish/bulk-edit/categories/remove')->compile(),
			'bulkEdit.lang' => [
				'selectedEntries'       => lang('selected_entries'),
				'filterSelectedEntries' => lang('filter_selected_entries'),
				'noEntriesFound'        => sprintf(lang('no_found'), lang('entries')),
				'showing'               => lang('showing'),
				'of'                    => lang('of'),
				'clearAll'              => lang('clear_all'),
				'removeFromSelection'   => lang('remove_from_selection'),
			]
		]);

		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/date_picker',
				'cp/publish/entry-list',
				'components/bulk_edit_entries',
				'cp/publish/bulk-edit',
			),
		));

		ee()->lang->loadfile('calendar');
		ee()->javascript->set_global('date.date_format', ee()->localize->get_date_format());
		ee()->javascript->set_global('lang.date.months.full', array(
			lang('cal_january'),
			lang('cal_february'),
			lang('cal_march'),
			lang('cal_april'),
			lang('cal_may'),
			lang('cal_june'),
			lang('cal_july'),
			lang('cal_august'),
			lang('cal_september'),
			lang('cal_october'),
			lang('cal_november'),
			lang('cal_december')
		));
		ee()->javascript->set_global('lang.date.months.abbreviated', array(
			lang('cal_jan'),
			lang('cal_feb'),
			lang('cal_mar'),
			lang('cal_apr'),
			lang('cal_may'),
			lang('cal_june'),
			lang('cal_july'),
			lang('cal_aug'),
			lang('cal_sep'),
			lang('cal_oct'),
			lang('cal_nov'),
			lang('cal_dec')
		));
		ee()->javascript->set_global('lang.date.days', array(
			lang('cal_su'),
			lang('cal_mo'),
			lang('cal_tu'),
			lang('cal_we'),
			lang('cal_th'),
			lang('cal_fr'),
			lang('cal_sa'),
		));



		//  ------
		//	Modals
		//  ------

		$modal = ee('View')->make('ee:_shared/modal_confirm_remove')->render([
			'name'		=> 'modal-confirm-remove-entry',
			'form_url'	=> Url::cpPublishEditUrl(),
			'hidden'	=> [
				'bulk_action'	=> 'remove'
			]
		]);


		ee('CP/Modal')->addModal('remove-entry', $modal);

		$modal = ee('View')->make('ee:_shared/modal-bulk-edit')->render([
			'name' => 'modal-bulk-edit',
		]);

		ee('CP/Modal')->addModal('bulk-edit', $modal);

		$modal = ee('View')->make('ee:_shared/modal-form')->render([
			'name' => 'modal-form',
			'contents' => '',
		]);
		ee('CP/Modal')->addModal('modal-form', $modal);
	}
}