<?php namespace Zenbu\librairies\platform\ee;

use Zenbu\librairies\platform\ee\Base as Base;

class File extends Base {

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Function _get_file_upload_prefs
	 *
	 * @return    array upload preferences
	 */
	public function upload_preferences()
	{
		// Return data if already cached
		if($this->cache->get('file_upload_prefs'))
		{
			return $this->cache->get('file_upload_prefs');
		}

		if(isset($this->upload_prefs))
		{
			return $this->upload_prefs;
		}

		// EE 2.4+ only
		ee()->load->model('file_upload_preferences_model');
		$result = ee()->file_upload_preferences_model->get_file_upload_preferences($this->user->group_id);
		$this->cache->set('file_upload_prefs', $result, 600);

		return $result;
	} // END function _get_file_upload_prefs

	/**
	 * function display_filesize
	 *
	 * Make filesizes (in bytes) human-readable.
	 *
	 * @param  string $size The filesize (number in bytes)
	 *
	 * @return string The human-readable filesize
	 */
	public function display_filesize($size)
	{
		$units = array(' B', ' KB', ' MB', ' GB', ' TB');

		for($i = 0; $size > 1000; $i++)
		{
			$size /= 1000;
		}

		return round($size, 2) . $units[$i];
	}

	/**
	 * Get file dimensions
	 *
	 * @return    array
	 */
	public static function getAllDimensions()
	{
		if($file_dimensions = Cache::get('all_file_dimensions'))
		{
			return $file_dimensions;
		}

		$file_dimensions = ee('Model')->get('FileDimension')->all();

		Cache::set('all_file_dimensions', $file_dimensions, 300);

		return $file_dimensions;
	}

	public static function getDimensionById($id)
	{
		$file_dimensions = self::getAllDimensions();
		$dimension       = $file_dimensions->filter('id', $id)->first();

		$out = null;

		if($dimension)
		{
			$out = $dimension->getValues();
		}

		return $out;
	}

	static public function get_thumb_path($string, $dimension_name = 'thumbs')
	{
		ee()->load->library('file_field');

		if (!$string) {
			return '';
	 	}

		$file_data = ee()->file_field->parse_field($string);

		$filepath = rtrim($file_data['path'], '/') . "/_{$dimension_name}/" . $file_data['file_name'];

		return $filepath;
	}

	static public function getFileModel($field_content = null)
	{
		if(! $field_content)
		{
			return '';
		}

		ee()->load->library('file_field');

		$file_data = ee()->file_field->parse_field($field_content);

		return isset($file_data['model_object']) ? $file_data['model_object'] : null;;
	}

}