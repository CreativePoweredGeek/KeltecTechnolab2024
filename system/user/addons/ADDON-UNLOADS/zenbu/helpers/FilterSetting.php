<?php

namespace Zenbu\helpers;

use Zenbu\librairies\platform\ee\Session;

class FilterSetting {

	/**
	 * Retrieve specific filter setting
	 * @param string $channel_id
	 * @param null   $setting_name
	 *
	 * @return |null
	 */
	public static function getSetting($channel_id = '', $setting_name = null)
	{

		$filter_setting = ee('Model')->get('zenbu:FilterSetting')
			->filter('userId', Session::user()->member_id)
			->filter('sectionId', $channel_id)
			->filter('setting', $setting_name)
			->all();

		//	----------------------------------------
		//	Fallback if no results are found for Display Settings:
		//  We try to look for any member group-level Display Settings.
		//	----------------------------------------

		if($filter_setting->count() === 0)
		{
			$filter_setting = ee('Model')->get('zenbu:FilterSetting')
				->filter('userGroupId', Session::user()->group_id)
				->filter('sectionId', $channel_id)
				->filter('setting', $setting_name)
				->all();
		}

		if($filter_setting->count() > 0)
		{
			return $filter_setting->first()->value;
		}

		return null;
	}

	/**
	 * Retrieve ALL filter settings
	 * @param string $channel_id
	 *
	 * @return array
	 */
	public static function getSettings($channel_id = '')
	{
		/**
		 * Load the Filter Settings
		 */
		$filter_settings = ee('Model')->get('zenbu:FilterSetting')
			->filter('userId', Session::user()->member_id)
			->filter('sectionId', $channel_id)
			->first();

		//	----------------------------------------
		//	Fallback if no results are found for Display Settings:
		//  We try to look for any member group-level Display Settings.
		//	----------------------------------------

		if($filter_settings->count() === 0)
		{
			$filter_settings = ee('Model')->get('zenbu:FilterSetting')
				->filter('userGroupId', Session::user()->group_id)
				->filter('sectionId', $channel_id)
				->first();
		}

		$output = [];

		foreach($filter_settings as $gs)
		{
			$output[$gs->setting] = $gs->value;
		}

		return $output;
	}

}