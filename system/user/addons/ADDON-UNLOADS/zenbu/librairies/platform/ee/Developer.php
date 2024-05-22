<?php namespace Zenbu\librairies\platform\ee;

use Zenbu\librairies\platform\ee\Base as Base;

class Developer extends Base
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Get the version of an addon (module to be specific)
	 * @param  string $module_name The module name as found in exp_modules
	 * @return string              The module version
	 */
	public function getVersion($module_name = 'Zenbu')
	{
		// Return data if already cached
        if($this->cache->get('addon_version_'.$module_name))
        {
            return $this->cache->get('addon_version_'.$module_name);
        }

		/* Do the query */
		$results = ee()->db->query("/* getVersion - " . $module_name . " */ \n SELECT module_version
			FROM exp_modules m
			WHERE m.module_name = '" . ee()->db->escape_str($module_name) . "'");

		if($results->num_rows() > 0)
		{
			foreach($results->result() as $row)
			{
				$version = $row->module_version;
			}

			$this->cache->set('addon_version_'.$module_name, $version);
			$results->free_result();
			return $version;
		}
		else
		{
			return FALSE;
		}
	} // END getVersion()

	// --------------------------------------------------------------------

	/**
	 * Log message in Developer log
	 * @param  string  $message The message
	 * @param  boolean $update  Update previous log message
	 * @param  integer $expires Expiration period for message
	 * @return void           Message logged
	 */
	public static function log($message = '', $update = TRUE, $expires = 604800)
	{
		if(! empty($message))
		{
			ee()->logger->developer($message, $update, $expires);
		}
	} // END log()

	// --------------------------------------------------------------------
}