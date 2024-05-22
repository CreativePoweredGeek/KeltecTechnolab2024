<?php namespace Zenbu\librairies\platform\ee;

class Lang {

	public static function t($key, $fallback = '')
	{
		return lang($key, $fallback);
	}

	public static function load($file)
	{
		if(is_array($file))
		{
			foreach($file as $f)
			{
				ee()->lang->loadfile($f);
			}
		}
		else
		{
			ee()->lang->loadfile($file);
		}
	}

	public static function getAllStrings($file)
	{
		$path = PATH_THIRD . 'zenbu/language/' . (ee()->config->item('language') ?: 'english') . '/';
		$filename = $file . '_lang.php';

		$M = [];
		if(file_exists($path . $filename) && is_readable($path . $filename))
		{
			require($path . $filename);

			$M = $lang;

			unset($lang);
		}

		return $M;
	}
}