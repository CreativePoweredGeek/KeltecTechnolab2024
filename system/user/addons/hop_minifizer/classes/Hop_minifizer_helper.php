<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

use HopStudios\HopMinifizer\Hop\HopConfig;

require_once PATH_THIRD . 'hop_minifizer/classes/Hop_minifizer_config.php';

class Hop_minifizer_helper {
	/**
	 * Logging levels
	 */
	private static $_levels = [
		1 => 'ERROR',
		2 => 'DEBUG',
		3 => 'INFO'
	];


	/**
	 * History of logging for EE Debug Toolbar
	 */
	private static $_log = array();


	/**
	 * Flag for whether to 'flash' our toolbar tab
	 */
	private static $_log_has_error = false;


	/**
	 * Our 'Singleton' config
	 */
	private static $_config = false;


	// ----------------------------------------------


	/**
	 * Create an alias to our cache
	 *
	 * @return 	Array	Our cache in EE->session->cache
	 */
	public static function &cache()
	{
		// be sure we have a cache set up
		if ( ! isset(ee()->session->cache['hop_minifizer'])) {
			ee()->session->cache['hop_minifizer'] = array();

			self::log('Session cache has been created.', 3);
		}

		return ee()->session->cache['hop_minifizer'];
	}
	// ------------------------------------------------------


	/**
	 * Fetch/create singleton instance of config
	 *
	 * @return 	Array	Instance Hop_minifizer_config
	 */
	public static function config()
	{
		if (self::$_config === false) {
			self::$_config = new Hop_minifizer_config();
		}

		return self::$_config;
	}
	// ------------------------------------------------------


	/**
	 * Fetch our static log
	 *
	 * @return 	Array	Array of logs
	 */
	public static function get_log()
	{
		return self::$_log;
	}
	// ------------------------------------------------------


	/**
	 * Fetch our static log
	 *
	 * @return 	Array	Array of logs
	 */
	public static function log_has_error()
	{
		return self::$_log_has_error;
	}
	// ------------------------------------------------------


	/**
	 * Determine if string is valid URL
	 *
	 * @param 	string	String to test
	 * @return 	bool	true if yes, false if no
	 */
	public static function is_url($string)
	{
		// from old _isURL() file from Carabiner Asset Management Library
		// modified to support leading with double slashes
		return (preg_match('@((https?:)?//([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', $string) > 0);
	}
	// ------------------------------------------------------


	/**
	 * Loads our requested library
	 *
	 * On first call it will adjust the include_path, for Minify support
	 *
	 * @param 	string	Name of library to require
	 * @return 	void
	 */
	public static function library($which)
	{
		// a few housekeeping items before we start loading our libraries
		if ( ! isset(get_instance()->session->cache['loader'])) {
			// try to bump our memory limits for good measure
			@ini_set('memory_limit', '12M');
			@ini_set('memory_limit', '16M');
			@ini_set('memory_limit', '32M');
			@ini_set('memory_limit', '64M');
			@ini_set('memory_limit', '128M');
			@ini_set('memory_limit', '256M');

			// Latest changes to Minify adopt a "loader" over sprinkled require's
			require_once(PATH_THIRD . 'hop_minifizer/libraries/Minify/Loader.php');
			Minify_Loader::register();

			// don't do this again
			get_instance()->session->cache['loader'] = true;
		}

		// require_once our library of choice
		switch ($which) {

			case ('minify') :
				if ( ! class_exists('Minify_CSS')) {
					require_once(PATH_THIRD . 'hop_minifizer/libraries/Minify/CSS.php');
				}
				break;

			case ('cssmin') :
				if ( ! class_exists('CSSmin')) {
					// this sucks, but it's a case-insensitivity issue that we need to protect ourselves against
					if (glob(PATH_THIRD . 'hop_minifizer/libraries/CSSmin.php')) {
						require_once(PATH_THIRD . 'hop_minifizer/libraries/CSSmin.php');
					} else {
						self::log('CSSMin.php in hop_minifizer/libraries needs to be renamed to the proper capitalisation of "CSSmin.php".', 2);
						require_once(PATH_THIRD . 'hop_minifizer/libraries/CSSMin.php');
					}
				}
				break;

			case ('css_urirewriter') :
				if ( ! class_exists('Minify_CSS_UriRewriter')) {
					require_once(PATH_THIRD . 'hop_minifizer/libraries/Minify/CSS/UriRewriter.php');
				}
				break;

			case ('curl') :
				// TODO: Remove epicurl completely
				if ( ! class_exists('EpiCurl')) {
					require_once(PATH_THIRD . 'hop_minifizer/libraries/EpiCurl.php');
				}
				break;

			case ('jsmin') :

				if ( ! class_exists('JSMin')) {
					// this sucks, but it's a case-insensitivity issue that we need to protect ourselves against
					if (glob(PATH_THIRD . 'hop_minifizer/libraries/JSM*n.php')) {
						require_once(PATH_THIRD . 'hop_minifizer/libraries/JSMin.php');
					} else {
						self::log('jsmin.php in hop_minifizer/libraries needs to be renamed to the proper capitalisation of "JSMin.php".', 2);
						require_once(PATH_THIRD . 'hop_minifizer/libraries/jsmin.php');
					}
				}
				break;

			case ('jsminplus') :
				if ( ! class_exists('JSMinPlus')) {
					require_once(PATH_THIRD . 'hop_minifizer/libraries/JSMinPlus.php');
				}
				break;

			case ('html') :
				if ( ! class_exists('Minify_HTML')) {
					require_once(PATH_THIRD . 'hop_minifizer/libraries/Minify/HTML.php');
				}
				break;
		}
	}
	// ------------------------------------------------------


	/**
	 * Log method
	 *
	 * By default will pass message to log_message();
	 * Also will log to template if rendering a PAGE.
	 *
	 * @access  public
	 * @param   string      $message        The log entry message.
	 * @param   int         $severity       The log entry 'level'.
	 * @return  void
	 */
	public static function log($message, $severity = 1)
	{
		// translate our severity number into text
		$severity = (array_key_exists($severity, self::$_levels)) ? self::$_levels[$severity] : self::$_levels[1];

		// save our log for EE Debug Toolbar
		self::$_log[] = array($severity, $message);
		if ($severity == 'ERROR') {
			self::$_log_has_error = true;
		}

		// basic EE logging
		log_message($severity, "Hop Minifizer: {$message}");

		// Can we also log our message to the template debugger?
		if (REQ == 'PAGE') {
			get_instance()->TMPL->log_item("Hop Minifizer:  [{$severity}]: {$message}");
		}
	}
	// ------------------------------------------------------


	/**
	 * Returns an array of all public properties of our Hop_minifizer plugin.
	 * Used to easily reset() to defaults.
	 *
	 * @return 	array	Array of public properties of Hop_minifizer class
	 */
	public static function hop_minifizer_class_vars()
	{
		$m = new Hop_minifizer;
		return get_class_vars(get_class($m));
	}
	// ------------------------------------------------------


	/**
	 * Helper function to parse content looking for CSS and JS tags.
	 * Returns array of links found.
	 * @param 	string	String to search
	 * @param 	string	Which type of tags to search for - CSS or JS
	 * @return 	array	Array of found matches
	 */
	public static function preg_match_by_type($haystack, $type)
	{
		// let's find the location of our cache files
		switch (strtolower($type)) {
			case 'css' :
				$pat = "/<link{1}.*?href=['\"']{1}(.*?)['\"]{1}(.*?(data-parse-scss))?[^>]*>/iU";
				break;

			case 'js' :
				$pat = "/<script{1}.*?src=['\"]{1}(.*?)['\"]{1}[^>]*>(.*?)<\/script>/iU";
				break;

			default :
				return false;
				break;
		}

		if ( ! preg_match_all($pat, $haystack, $matches, PREG_PATTERN_ORDER)) {
			return false;
		}

		// free memory where possible
		unset($pat);

		return $matches;
	}
	// ------------------------------------------------------

	
	/**
	 * Modified remove_double_slashes()
	 *
	 * If the string passed is a URL, it will preserve leading double slashes
	 *
	 * @param 	string	String to remove double slashes from
	 * @param 	boolean	True if string is a URL
	 * @return 	string	String without double slashes
	 */
	public static function remove_double_slashes($string, $url = false)
	{
		// is our string a URL?
		if ($url) {
			// regex pattern removes all double slashes, preserving http:// and '//' at start
			return preg_replace("#([^:])//+#", "\\1/", $string);
		}
		// nope just a path
		else {
			// regex pattern removes all double slashes - straight from EE->functions->remove_double_slashes();
			return preg_replace("#(^|[^:])//+#", "\\1/", $string);
		}
	}
	// ------------------------------------------------------


	/**
	 * A protocol-agnostic function to replace URL with path
	 *
	 * @param 	string	base url
	 * @param 	boolean	base path
	 * @return 	string	String to perform replacement upon
	 */
	public static function replace_url_with($url, $with, $haystack)
	{
		// protocol-agnostic URL
		$agnostic_url = substr($url, strpos($url, '//') + 2, strlen($url));

		// pattern search & replace
		return preg_replace('@(https?:)?\/\/' . $agnostic_url . '@', $with, $haystack);
	}

	/**
	 * Verifies if a path ends with an specific character
	 *
	 * @param 	string	value
	 * @param 	string	match
	 * @return 	boolean	String to perform replacement upon
	 */
	public static function ends_with($str, $sub)
	{
		return (substr($str, strlen($str) - strlen($sub)) === $sub);
	}
}
