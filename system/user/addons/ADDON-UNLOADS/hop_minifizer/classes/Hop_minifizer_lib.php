<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

// our helper will require_once() everything else we need
require_once PATH_THIRD . 'hop_minifizer/classes/Hop_minifizer_helper.php';
require_once PATH_THIRD . 'hop_minifizer/vendor/autoload.php';
require_once PATH_THIRD . 'hop_minifizer/libraries/scssphp/scss.inc.php';

use Leafo\ScssPhp\Compiler;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

class Hop_minifizer_lib
{

	/**
	 * runtime variables
	 */
	public $cache_lastmodified      = '';       // lastmodified value for cache
	public $cache_filename_hash     = '';       // a hash of settings & filenames
	public $cache_filename          = '';       // eventual filename of cache
	public $filesdata               = array();  // array of assets to process
	public $remote_mode             = '';       // 'fgc' or 'curl'
	public $stylesheet_query        = false;    // Boolean of whether to fetch stylesheets from DB
	public $type                    = '';       // 'css' or 'js'
	public $filename                = '';
	public $output                  = 'file';
	public $cache_namespace         = '/hop_minifizer/';


	/**
	 * keep track of how many bytes saved during minification
	 */
	protected $diff_total           = 0;

	/**
	 * Hop_minifizer_config
	 */
	public $config;


	/**
	 * Reference to our cache
	 */
	public $cache;


	// ------------------------------------------------------


	/**
	 * Constructor
	 *
	 * @param Mixed     Instance of Hop_minifizer_config, or Array to be passed to Hop_minifizer_config
	 * @return void
	 */
	public function __construct($config = array())
	{
		// grab reference to our cache
		$this->cache =& Hop_minifizer_helper::cache();

		// set instance of our config object
		if ($config instanceof Hop_minifizer_config) {
			$this->config = $config;
		} else {
			$this->config = new Hop_minifizer_config($config);
		}
	}
	// ------------------------------------------------------


	/**
	 * Convenience wrapper for running CSS minification on a batch of files
	 *
	 * @param mixed     String or array of files to cache
	 * @return mixed    String or array of cache filename(s)
	 */
	public function css($files)
	{
		return $this->run('css', $files);
	}
	// ------------------------------------------------------


	/**
	 * Convenience wrapper for running JS minification on a batch of files
	 *
	 * @param mixed     String or array of files to cache
	 * @return mixed    String or array of cache filename(s)
	 */
	public function js($files)
	{
		return $this->run('js', $files);
	}
	// ------------------------------------------------------


	/**
	 * Get or create our cache
	 *
	 * Here handles the rare combine="no" circumstances.
	 * @return mixed    String or array of cache filename(s)
	 */
	public function cache()
	{
		// Be sure we have a valid type
		if ( ! $this->type) {
			throw new Exception('Must specify a valid asset type.');
		}

		// combining files?
		if ($this->config->is_yes('combine_' . $this->type)) {
			// what to eventually return
			$return = '';

			// first try to fetch from cache
			if ($this->_get_cache() === false) {
				// write new cache
				$this->_create_cache();
			}

			$return = $this->cache_filename;
		}
		// manual work to combine each file in turn
		else {
			$filesdata = $this->filesdata;
			$this->filesdata = array();
			$out = '';
			$return = array();
			foreach($filesdata as $file) {
				$this->filesdata = array($file);

				// first try to fetch from cache
				if ($this->_get_cache() === false) {
					// write new cache
					$this->_create_cache();
				}

				$return[] = $this->cache_filename;
			}

			unset($out);
		}

		// return string or array
		return $return;
	}
	// ------------------------------------------------------


	/**
	 * Find out more info about each file
	 * Attempts to get file modification times, determine what files exist, etc
	 *
	 * @return bool true if all are found; false if at least one is not found
	 */
	public function check_headers()
	{
		// let's be sure we have files
		if ( ! $this->filesdata) {
			throw new Exception('Must specify at least one file to minify.');
		}

		// query for any stylesheets
		$stylesheet_versions = $this->_fetch_stylesheet_versions();

		// temporarily store runtime settings
		$runtime = $this->config->get_runtime();

		// now, loop through our filesdata and set all headers
		foreach ($this->filesdata as $key => $file) {

			// file runtime settings can be overridden by tag runtime settings
			$this->config->reset()->extend($runtime)->extend($this->filesdata[$key]['runtime']);

			switch ($this->filesdata[$key]['source']) {

				/**
				 * scss template
				 */
				case('scss_template') : 
					if ($stylesheet_versions && array_key_exists($this->filesdata[$key]['name'], $stylesheet_versions)) {
						$this->filesdata[$key]['lastmodified'] = $stylesheet_versions[$this->filesdata[$key]['name']];
						$this->filesdata[$key]['name'] =  $this->config->base_url . '/' .  $this->filesdata[$key]['name'] . QUERY_MARKER . ((ee()->config->item('send_headers') == 'y') && isset($stylesheet_versions[$this->filesdata[$key]['name']]) ? 'v=' . $stylesheet_versions[$this->filesdata[$key]['name']] : '');
						Hop_minifizer_helper::log('Headers OK for scss template: `' . $this->filesdata[$key]['name'] . '`.', 3);
					}
					// couldn't find scss template in db
					else {
						throw new Exception('Missing scss template: ' . $this->filesdata[$key]['name']);
					}
					break;

				/**
				 * Stylesheets (e.g. {stylesheet='template/file'}
				 */
				case('stylesheet') :
					// the stylesheet matches one we've found in db or files
					if ($stylesheet_versions && array_key_exists($this->filesdata[$key]['stylesheet'], $stylesheet_versions)) {
						// transform name out of super global and into valid URL
						$this->filesdata[$key]['name'] = ee()->functions->fetch_site_index() . QUERY_MARKER . 'css=' . $this->filesdata[$key]['stylesheet'] . ((ee()->config->item('send_headers') == 'y') && isset($stylesheet_versions[$this->filesdata[$key]['stylesheet']]) ? '.v.' . $stylesheet_versions[$this->filesdata[$key]['stylesheet']] : '');
						$this->filesdata[$key]['lastmodified'] = $stylesheet_versions[$this->filesdata[$key]['stylesheet']];

						Hop_minifizer_helper::log('Headers OK for stylesheet template: `' . $this->filesdata[$key]['stylesheet'] . '`.', 3);
					}
					// couldn't find stylesheet in db
					else {
						throw new Exception('Missing stylesheet template: ' . $this->filesdata[$key]['stylesheet']);
					}

					break;

				/**
				 * Remote files
				 * All we can do for these is test if the file is in fact local
				 */
				case('remote') :

					// let's strip out all variants of our base url
					$local = Hop_minifizer_helper::replace_url_with($this->config->base_url, '', $file['name']);

					// the filename needs to be without any cache-busting or otherwise $_GETs
					if ($position = strpos($local, '?')) {
						$local = substr($local, 0, $position);
					}

					$realpath = realpath(Hop_minifizer_helper::remove_double_slashes($this->config->base_path . '/' . $local));

					// if the $local file exists, let's alter the file source & name, and calculate lastmodified
					if (file_exists($realpath) && $local != '/') {
						$this->filesdata[$key]['name'] = $local;
						$this->filesdata[$key]['source'] = 'local';
						
						$this->filesdata[$key]['lastmodified'] = filemtime($realpath);

						Hop_minifizer_helper::log('Treating `' . $file['name'] . '` as a local file: `' . $this->filesdata[$key]['name'] . '`', 3);
					}
					// nope; keep as remote
					else {
						Hop_minifizer_helper::log('Processing remote file: `' . $file['name'] . '`.', 3);
					}

					break;
					
				/**
				 * Local files
				 */
				default:

					// the filename needs to be without any cache-busting or otherwise $_GETs
					if ($position = strpos($this->filesdata[$key]['name'], '?')) {
						$this->filesdata[$key]['name'] = substr($this->filesdata[$key]['name'], 0, $position);
					}

					$realpath = realpath(Hop_minifizer_helper::remove_double_slashes($this->config->base_path . '/' . $this->filesdata[$key]['name']));

					if (file_exists($realpath)) {
						$this->filesdata[$key]['lastmodified'] = filemtime($realpath);

						Hop_minifizer_helper::log('Headers OK for file: `' . $this->filesdata[$key]['name'] . '`.', 3);
					} else {
						throw new Exception('Missing local file: ' . Hop_minifizer_helper::remove_double_slashes($this->config->base_path . '/' . $this->filesdata[$key]['name']));
					}
					break;
			}
		}
		
		// return our settings to our runtime
		$this->config->reset()->extend($runtime);

		// free memory where possible
		unset($runtime, $stylesheet_versions);

		// chaining
		return $this;
	}
	// ------------------------------------------------------


	/**
	 * Add to total diff; returns new total
	 *
	 * @return Integer Total bytes saved after minification
	 */
	public function diff_total($diff = 0)
	{
		$this->diff_total = $diff + $this->diff_total;

		return $this->diff_total;
	}
	// ------------------------------------------------------


	/**
	 * Flightcheck - make some basic config checks before proceeding
	 *
	 * @return void
	 */
	public function flightcheck()
	{
		// Manually disabled?
		if ($this->config->is_yes('disable')) {
			// we can actually figure out if it's a runtime setting or default
			$runtime = $this->config->get_runtime();

			if (isset($runtime['disable']) && $runtime['disable'] == 'yes') {
				throw new Exception('Disabled via tag parameter.');
			} else {
				throw new Exception('Disabled via config.');
			}
		}

		// If our cache_path doesn't appear to exist, try appending it to our base_url and check again.
		if ( ! file_exists($this->config->cache_path)) {
			Hop_minifizer_helper::log('Cache Path `' . $this->config->cache_path . '` is being appended to Base Path `' . $this->config->base_path . '`.', 3);

			$this->config->cache_path = Hop_minifizer_helper::remove_double_slashes($this->config->base_path . '/' . $this->config->cache_path);

			Hop_minifizer_helper::log('Cache Path is now `' . $this->config->cache_path . '`.', 3);

			if ( ! file_exists($this->config->cache_path)) {
				throw new Exception('Not configured correctly: your cache folder `' . $this->config->cache_path . '` does not exist.');
			}
		}

		// Be sure our cache path is also writable
		if ( ! is_really_writable($this->config->cache_path)) {
			throw new Exception('Not configured correctly: your cache folder `' . $this->config->cache_path . '` is not writable.');
		}

		// If our cache_url doesn't appear a valid url, append it to our base_url
		if ( ! Hop_minifizer_helper::is_url($this->config->cache_url)) {
			Hop_minifizer_helper::log('Cache URL `' . $this->config->cache_url . '` is being appended to Base URL `' . $this->config->base_url . '`.', 3);

			$this->config->cache_url = Hop_minifizer_helper::remove_double_slashes($this->config->base_url . '/' . $this->config->cache_url, true);

			Hop_minifizer_helper::log('Cache URL is now `' . $this->config->cache_url . '`.', 3);
		}

		// Determine our runtime remote_mode setting
		$this->_set_remote_mode();

		// Passed flightcheck!
		Hop_minifizer_helper::log('Passed flightcheck.', 3);


		// chaining
		return $this;
	}
		// ------------------------------------------------------


	/**
	 * Set up our Hop_minifizer_lib::filesdata arrays to prepare for processing
	 *
	 * @param array array of files
	 * @return void
	 */
	public function set_files($files, $scss_files)
	{
		$dups = array();

		// cast to array to be safe
		if ( ! is_array($files)) {
			$files = array($files);
		}

		if ( ! is_array($scss_files)) {
			$scss_files = array($scss_files);
		}
		
		if (count($scss_files)) {
			$this->stylesheet_query = true;
		}

		$i = 0;
		foreach ($scss_files as $key => $file) {
			// try to avoid duplicates and emptyness
			if (in_array($file, $dups) || ! $file) continue;

			$dups[] = $file;

			$this->filesdata[$i] = array(
				'name' => $file,
				'source' => null,
				'runtime' => $this->config->get_runtime(),
				'lastmodified' => '0000000000',
				'stylesheet' => null
			);

			$curr_file_name = $file;

			if ($position = strpos($file, '?')) {
				$curr_file_name = substr($file, 0, $position);
			}

			$realpath = realpath(Hop_minifizer_helper::remove_double_slashes($this->config->base_path . '/' . $curr_file_name));

			if (file_exists($realpath)) {
				$this->filesdata[$i]['source'] = 'scss_template_local';
			} else {
				$this->filesdata[$i]['source'] = 'scss_template';
			}
			
			$i++;
		}

		if (count($files) > 0) {
			foreach ($files[0] as $key => $file) {
				// try to avoid duplicates and emptyness
				if (in_array($file, $dups) || ! $file) continue;

				$dups[] = $file;

				$this->filesdata[$i] = array(
					'name' => $file,
					'source' => null,
					'runtime' => $this->config->get_runtime(),
					'lastmodified' => '0000000000',
					'stylesheet' => null
				);

				if (Hop_minifizer_helper::is_url($this->filesdata[$i]['name'])) {
					$this->filesdata[$i]['source'] = 'remote';
				} elseif (preg_match("/" . LD . "\s*stylesheet=[\042\047]?(.*?)[\042\047]?" . RD . "/", $this->filesdata[$i]['name'], $matches)) {
					$this->filesdata[$i]['source'] = 'stylesheet';
					$this->filesdata[$i]['stylesheet'] = $matches[1];
				} elseif (preg_match("/" . LD . "\s*path=[\042\047]?(.*?)[\042\047]?" . RD . "/", $this->filesdata[$i]['name'], $matches)) {
					// Use ee's template class to parse the path tag
					$tmpl = clone ee()->TMPL;
					$tmpl->parse($this->filesdata[$i]['name']);
					$this->filesdata[$i]['name'] = $tmpl->parse_globals($tmpl->final_template);
					$this->filesdata[$i]['source'] = 'remote';
				} else {
					$this->filesdata[$i]['source'] = 'local';
				}

				//Should compile scss?
				if ($this->type) {
					$this->filesdata[$i]['parse_scss'] = (isset($files[1]) && isset($files[1][$i]) && $files[1][$i] == 'data-parse-scss') ? "yes" : "no";
				}

				// flag to see if we need to run SQL query later
				if ($this->filesdata[$i]['source'] == 'stylesheet') {
					$this->stylesheet_query = true;
				}
				$i++;
			}
		}

		// free memory where possible
		unset($dups);

		// chaining
		return $this;
	}
	// ------------------------------------------------------


	/**
	 * Set up our Hop_minifizer_lib::type flag
	 *
	 * @param String        css or js
	 * @return void
	 */
	public function set_type($type)
	{

		if (preg_match('/css|js/i', $type)) {
			$this->type = strtolower($type);
		} else {
			throw new Exception('`' . $type . '` is not a valid type of asset.');
		}

		// chaining
		return $this;

	}
	// ------------------------------------------------------


	/**
	 * Reset all internal props
	 *
	 * @return object   Self
	 */
	public function reset()
	{
		$this->cache_lastmodified       = '';
		$this->cache_filename_hash      = '';
		$this->cache_filename           = '';
		$this->filesdata                = array();
		$this->remote_mode              = '';
		$this->stylesheet_query     = false;
		$this->type                 = '';

		// chaining
		return $this;
	}
	// ------------------------------------------------------


	/**
	 * Our basic run
	 *
	 * @param String    Type of cache (css or js)
	 * @param mixed     String or array of files to cache
	 * @return mixed    String or array of cache filename(s)
	 */
	public function run($filename, $output, $type, $files, $scss_templates)
	{
		$this->filename = $filename;
		$this->output = $output;
		try {
			return $this->reset()
				->set_type($type)
				->set_files($files, $scss_templates)
				->flightcheck()
				->check_headers()
				->cache();
		} catch (Exception $e) {
			ee()->TMPL->log_item('Hop Minifizer [ERROR]: ' . $e->getMessage());
			return $this->cache();
		}
	}
	// ------------------------------------------------------

	/**
	 * Performs heavy lifting of creating our cache
	 *
	 * @return string The final tag to be returned to template
	 */
	protected function _create_cache()
	{
		// zero our diff total
		$this->diff_total = 0;

		// the eventual contents of our cache
		$cache = '';

		// the contents of each file
		$contents = '';

		// the relative path for each file
		$css_prepend_url = '';

		// the concanated contents of the SCSS templates
		$scss_contents = '';

		// save our runtime settings temporarily
		$runtime = $this->config->get_runtime();

		//Filter only scss_template, source = scss_template or source = scss_template_local
		$curr_files_data = array_filter($this->filesdata, function ($array_value)
			{
				return $array_value['source'] == 'scss_template' || $array_value['source'] == 'scss_template_local';
			}
		);

		//Begin: SCSS files processing
		//We need to fetch scss templates first, then we need to parse these
		foreach ($curr_files_data as $key => $file) {
			// file runtime settings can be overridden by tag runtime settings
			$this->config->reset()->extend($runtime)->extend($file['runtime']);

			// determine our initial prepend url
			$css_prepend_url = ($this->config->css_prepend_url) ? $this->config->css_prepend_url : $this->config->base_url;

			if ($file['source'] == 'scss_template') {
				$contents = $this->_get_external_file_contents($file['name']);
			} elseif($file['source'] == 'scss_template_local') {
				// grab contents of file
				$contents = file_get_contents(realpath(Hop_minifizer_helper::remove_double_slashes($this->config->base_path . '/' . $file['name'])));

				// base the prepend url off the location of asset
				$css_prepend_url = Hop_minifizer_helper::remove_double_slashes($css_prepend_url . '/' . $file['name'], true);

				// get directory level URL of the asset
				$css_prepend_url = dirname($css_prepend_url);
			}

			// Let's log a warning message if the contents of file are empty
			if ( ! $contents) {
				Hop_minifizer_helper::log('The contents from `' . $file['name'] . '` were empty.', 2);
			}
			// log & concat scss contents
			else {
				Hop_minifizer_helper::log('Fetched contents of `' . $file['name'] . '`.', 3);

				//concatenate scss templates data
				$scss_contents .= $contents;
			}
		}

		if ($scss_contents != '') {
			$scss = new Compiler();

			// compile/parse scss contents
			$scss_contents = $scss->compile($scss_contents);

			Hop_minifizer_helper::log('Compiled scss templates', 3);
			
			// minify contents
			$minified = $this->_minify('css', $scss_contents, 'scss_build', $css_prepend_url);

			// and append to $cache
			$cache .= $minified . "\n";
		}
		//End: SCSS files processing

		//Begin: JS and CSS files processing
		$curr_files_data = array_filter($this->filesdata, function ($array_value) {
				return $array_value['source'] != 'scss_template' && $array_value['source'] != 'scss_template_local';
			}
		);

		foreach ($curr_files_data as $key => $file)  {
			// file runtime settings can be overridden by tag runtime settings
			$this->config->reset()->extend($runtime)->extend($file['runtime']);

			// determine our initial prepend url
			$css_prepend_url = ($this->config->css_prepend_url) ? $this->config->css_prepend_url : $this->config->base_url;

			switch ($file['source']) {
				case ('remote') :

					// overwrite the prepend url based off the location of remote asset
					$css_prepend_url = $file['name'];

					// get directory level URL of the asset
					$css_prepend_url = dirname($css_prepend_url);

					// notice we are NOT breaking, because we also want to do everything in stylesheet...

				case ('stylesheet'):
					$contents = $this->_get_external_file_contents($file['name']);
					break;

				case ('local') :
				default :
					// grab contents of file
					$contents = @file_get_contents(realpath(Hop_minifizer_helper::remove_double_slashes($this->config->base_path . '/' . $file['name'])));

					// base the prepend url off the location of asset
					$css_prepend_url = Hop_minifizer_helper::remove_double_slashes($css_prepend_url . '/' . $file['name'], true);

					// get directory level URL of the asset
					$css_prepend_url = dirname($css_prepend_url);
					break;
			}

			// Let's log a warning message if the contents of file are empty
			if ( ! $contents) {
				Hop_minifizer_helper::log('The contents from `' . $file['name'] . '` were empty.', 2);
			}
			// log & minify contents
			else {
				Hop_minifizer_helper::log('Fetched contents of `' . $file['name'] . '`.', 3);

				//Compile scss if needed
				if ($file['parse_scss'] == "yes") {
					$scss = new Compiler();
	
					// compile/parse single scss contents
					$contents = $scss->compile($contents);
					Hop_minifizer_helper::log('Compiled scss `' . $file['name'] . '`.', 3);
				}

				// minify contents
				$minified = $this->_minify($this->type, $contents, $file['name'], $css_prepend_url);

				// tack on a semicolon at end of JS?
				if ($this->type == 'js' && substr($minified, -1) != ';')
				{
					$minified .= ';';
				}

				//  and append to $cache
				$cache .= $minified . "\n";
			}
		}
		//End: JS and CSS files processing

		// return our settings to our runtime
		$this->config->reset()->extend($runtime);

		// Log total bytes saved, if we saved any, and if there was more than one file to minify (otherwise we're reporting something we've already mentioned in a previous log)
		if ($this->diff_total > 0 && count($this->filesdata) > 1) {
			$diff_formatted = ($this->diff_total < 100) ? $this->diff_total . 'b' : round($this->diff_total / 1000, 2) . 'kb';
			Hop_minifizer_helper::log('Total savings: ' . $diff_formatted . ' across ' . count($this->filesdata) . ' files.', 3);
		}

		// write our cache file
		$this->_write_cache($cache);

		// free memory where possible
		unset($cache, $contents, $css_prepend_url, $runtime, $curr_files_data);

		// return true
		return true;
	}
	// ------------------------------------------------------

	
	/**
	 * Utility method
	 *
	 * @param string file name
	 * @return string
	 */
	protected function _create_cache_name($name)
	{
		// remove any cache-busting strings so the cache name doesn't change with every edit.
		// format: .v.1330213450
		$name = preg_replace('/\.v\.(\d+)/i', '', $name);

		// remove any variations of our base url
		$name = Hop_minifizer_helper::replace_url_with($this->config->base_url, '', $name);

		Hop_minifizer_helper::log('Creating cache name from `' . $name . '`.', 3);

		// create our cache filename by selected hash
		switch ($this->config->hash_method) :

		case 'sanitise' :
		case 'sanitize' :

			// pattern to match any stylesheet= queries
			$s_key = (ee()->config->item('index_page')) ? '/' . ee()->config->item('index_page') . '\?css=/' : '/\?css=/';

			// what to find & replace
			$find_replace = array(
				// stylesheet= $_GET query
				$s_key => '',
				// type extension
				'/\.' . $this->type . '/i' => '',
				// leading slashes
				'/^\/+/'    => '', 
				// other slashes
				'/\//'  => '.'
			);

			// first, remove leading slashes and replace the rest with periods
			$name = preg_replace(array_keys($find_replace), array_values($find_replace), $name);

			// now sanitise
			$this->cache_filename_hash = ee()->security->sanitize_filename($name);

			// reduce length to be safe?
			if (strlen($this->cache_filename_hash) > 200) {
				$this->cache_filename_hash = substr($this->cache_filename_hash, 0, 200);
			}

			break;

		case 'md5' :
			$this->cache_filename_hash = md5($name);
			break;

		default :
		case 'sha1' :
			$this->cache_filename_hash = sha1($name);
			break;

		endswitch;

		// include cachebust if provided
		$cachebust = ($this->config->cachebust) ? '.' . ee()->security->sanitize_filename($this->config->cachebust) : '';

		$temp_file_name = (!empty($this->filename) ? $this->filename . '.' : '') . $this->cache_filename_hash . '.' . $this->cache_lastmodified . $cachebust . '.' . $this->type;

		// put it all together
		return $temp_file_name;
	}
	// ------------------------------------------------------

	/**
	 * Query DB for any stylesheets
	 * Borrowed from ee()->TMPL->parse_globals(): ./system/expressionengine/libraries/Template.php
	 *
	 * @return mixed array or false
	 */
	protected function _fetch_stylesheet_versions()
	{

		// nothing to do if Hop_minifizer_lib::stylesheet_query is false
		if ( ! $this->stylesheet_query) return false;

		// let's only do this once per session
		if ( ! isset($this->cache['stylesheet_versions'])) {
			$versions = array();

			$sql = "SELECT t.template_name, tg.group_name, t.edit_date FROM exp_templates t, exp_template_groups tg
					WHERE  t.group_id = tg.group_id
					AND    t.template_type = 'css'
					AND    t.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";

			$css_query = ee()->db->query($sql);

			if ($css_query->num_rows() > 0) {
				foreach ($css_query->result_array() as $row) {
					$versions[$row['group_name'] . '/' . $row['template_name']] = $row['edit_date'];

					if (ee()->config->item('save_tmpl_files') == 'y' AND ee()->config->item('tmpl_file_basepath') != '') {
						$basepath = ee()->config->slash_item('tmpl_file_basepath') . ee()->config->item('site_short_name') . '/';
						$basepath .= $row['group_name'] . '.group/' . $row['template_name'] . '.css';

						if (is_file($basepath)) {
							$versions[$row['group_name'] . '/' . $row['template_name']] = filemtime($basepath);
						}
					}
				}

				// now save our versions info to cache
				$this->cache['stylesheet_versions'] = $versions;

				Hop_minifizer_helper::log('Stylesheet templates found in DB, and saved to cache.', 3);
			} else {
				// record fact that no stylesheets were found
				$this->cache['stylesheet_versions'] = false;

				Hop_minifizer_helper::log('No stylesheet templates were found in DB.', 2);
			}

			// free memory where possible
			$css_query->free_result();
			unset($sql, $versions);
		}

		// return whatever we've saved in cache
		return $this->cache['stylesheet_versions'];
	}
	// ------------------------------------------------------


	/**
	 * Internal function to look for cache file(s)
	 *
	 * @return mixed String of final tag output or false if cache needs to be refreshed
	 */
	protected function _get_cache()
	{
		// (re)set our usage vars
		$this->cache_filename = '';
		$this->cache_filename_hash = '';
		$this->cache_lastmodified = '';

		// loop through our files once
		foreach ($this->filesdata as $key => $file) {
			// max to determine most recently modified
			$this->cache_lastmodified = max($this->cache_lastmodified, $file['lastmodified']);

			// prepend for combined cache name
			$this->cache_filename .= $file['name'];
		}

		$this->cache_lastmodified = ($this->cache_lastmodified == 0) ? '0000000000' : $this->cache_lastmodified;
		$this->cache_filename = $this->_create_cache_name($this->cache_filename);

		// check for cache file
		$success = false;

		if ($this->output == 'file') {
			if ($this->filename != '') {
				$success = file_exists(Hop_minifizer_helper::remove_double_slashes($this->config->cache_path . '/'  . $this->filename . '.' . $this->type));

				$temp_cache_key = ee()->cache->get($this->cache_namespace . $this->filename);

				$success = $success && isset($temp_cache_key) && $temp_cache_key['cache_filename'] == $this->cache_filename;
			} else {
				$success = file_exists(Hop_minifizer_helper::remove_double_slashes($this->config->cache_path . '/'  . $this->cache_filename));
			}
		} elseif ($this->output == 'S3') {
			$success = $this->_s3_object_exists();
		}

		if ($success) {
			Hop_minifizer_helper::log('Cache file found: `' . $this->cache_filename . '`', 3);
		} else {
			Hop_minifizer_helper::log('Cache file not found: `' . $this->cache_filename . '`', 3);
		}

		return $success;
	}
		// ------------------------------------------------------


	/**
	 * Internal function for (maybe) minifying assets
	 *
	 * @param   Type of asset to minify (css/js)
	 * @param   Contents to be minified
	 * @param   The name of the file being minified (used for logging)
	 * @param   mixed A relative path to use, if provided (for css minification)
	 * @return  String (maybe) minified contents of file
	 */
	protected function _minify($type, $contents, $filename, $rel = false) {
		// used in case we need to return orig
		$contents_orig = $contents;

		switch ($type) {
			case 'js':

				/**
				 * JS pre-minify hook
				 */
				if (ee()->extensions->active_hook('hop_minifizer_pre_minify_js')) {
					Hop_minifizer_helper::log('Hook `hop_minifizer_pre_minify_js` has been activated.', 3);

					// pass contents to be minified, and instance of self
					$contents = ee()->extensions->call('hop_minifizer_pre_minify_js', $contents, $filename, $this);

					if (ee()->extensions->end_script === true) {
						return $contents;
					}

					// re-set $contents_orig in case we need to return
					$contents_orig = $contents;
				}
				// HOOK END


				// be sure we want to minify
				if ($this->config->is_yes('minify_js')) {

					// See if JSMinPlus was explicitly requested
					if ($this->config->js_library == 'jsminplus') {
						Hop_minifizer_helper::log('Running minification with JSMinPlus.', 3);

						Hop_minifizer_helper::library('jsminplus');

						$contents = JSMinPlus::minify($contents);
					}
					// Running JSMin is default
					else if ($this->config->js_library == 'jsmin') {
						Hop_minifizer_helper::log('Running minification with JSMin.', 3);

						Hop_minifizer_helper::library('jsmin');

						$contents = JSMin::minify($contents);
					}
				}
				break;

			case 'css':
				/**
				 * CSS pre-minify hook
				 */
				if (ee()->extensions->active_hook('hop_minifizer_pre_minify_css')) {
					Hop_minifizer_helper::log('Hook `hop_minifizer_pre_minify_css` has been activated.', 3);

					// pass contents to be minified, relative path, and instance of self
					$contents = ee()->extensions->call('hop_minifizer_pre_minify_css', $contents, $filename, $rel, $this);

					if (ee()->extensions->end_script === true) {
						return $contents;
					}

					// copy to $contents_orig in case we need to return
					$contents_orig = $contents;
				}
				// HOOK END

				// prepend URL if relative path exists & configured to do so
				if ($rel !== false && $this->config->is_yes('css_prepend_mode')) {
					Hop_minifizer_helper::library('css_urirewriter');
					$contents = Minify_CSS_UriRewriter::prepend($contents, $rel . '/');

					// copy to $contents_orig in case we need to return
					$contents_orig = $contents;
				}

				// minify if configured to do so
				if ($this->config->is_yes('minify_css')) {
					// See if CSSMin was explicitly requested
					if ($this->config->css_library == 'cssmin') {
						Hop_minifizer_helper::log('Running minification with CSSMin.', 3);

						Hop_minifizer_helper::library('cssmin');

						$cssmin = new CSSmin(false);

						$contents = $cssmin->run($contents);

						unset($cssmin);
					}
					// the default is to run Minify_CSS
					else if ($this->config->css_library == 'minify') {
						Hop_minifizer_helper::log('Running minification with Minify_CSS.', 3);

						Hop_minifizer_helper::library('minify');

						$contents = Minify_CSS::minify($contents);
					}
				}

				break;
		}

		// calculate weight loss
		$before = strlen($contents_orig);
		$after = strlen($contents);
		$diff = $before - $after;

		// quick check that contents are not empty
		if ($after == 0) {
			Hop_minifizer_helper::log('Minification has returned an empty string for `' . $filename . '`.', 2);
		}

		// did we actually reduce our file size? It's possible an already minified asset
		// uses a more aggressive algorithm than Minify; in that case, keep original contents
		if ($diff > 0) {
			$diff_formatted = ($diff < 100) ? $diff . 'b' : round($diff / 1000, 2) . 'kb';
			$change = round(($diff / $before) * 100, 2);

			Hop_minifizer_helper::log('Minification has reduced ' . $filename . ' by ' . $diff_formatted . ' (' . $change . '%).', 3);

			// add to our running total
			$this->diff_total($diff);
		} else {
			Hop_minifizer_helper::log('Minification unable to reduce ' . $filename . ', so using original content.', 3);
			$contents = $contents_orig;
		}

		// cleanup (leave some smaller variables because they may or may not have ever been set)
		unset($contents_orig);

		// return our (maybe) minified contents
		return $contents;
	}
	// ------------------------------------------------------


	/**
	 * Determine our remote mode for this call
	 *
	 * @param string either 'js' or 'css'
	 * @return void
	 */
	protected function _set_remote_mode()
	{
		// let's only do this once per session
		if ( ! isset($this->cache['remote_mode']))
		{
			// empty to start, then attempt to update it
			$this->cache['remote_mode'] = '';

			// if 'auto', then we try curl first
			if (preg_match('/auto|curl/i', $this->config->remote_mode) && in_array('curl', get_loaded_extensions())) {
				Hop_minifizer_helper::log('Using CURL for remote files.', 3);
				$this->cache['remote_mode'] = 'curl';
			}
			// file_get_contents() is auto mode fallback
			elseif (preg_match('/auto|fgc/i', $this->config->remote_mode) && ini_get('allow_url_fopen')) {
				Hop_minifizer_helper::log('Using file_get_contents() for remote files.', 3);

				if ( ! defined('OPENSSL_VERSION_NUMBER'))
				{
					Hop_minifizer_helper::log('Your PHP compile does not appear to support file_get_contents() over SSL.', 2);
				}

				$this->cache['remote_mode'] = 'fgc';
			}
			// if we're here, then we cannot fetch remote files
			else {
				Hop_minifizer_helper::log('Remote files cannot be fetched.', 2);
			}
		}

		$this->remote_mode = $this->cache['remote_mode'];
	}
	// ------------------------------------------------------


	/**
	 * Internal function for writing cache files
	 * [Adapted from CodeIgniter Carabiner library]
	 *
	 * @param   String of contents of the new file
	 * @return  boolean Returns true on successful cache, false on failure
	 */
	protected function _write_cache($file_data)
	{
		if (ee()->extensions->active_hook('hop_minifizer_pre_write_cache')) {
			Hop_minifizer_helper::log('Hook `hop_minifizer_pre_write_cache` has been activated.', 3);

			// pass contents of file, and instance of self
			$file_data = ee()->extensions->call('hop_minifizer_pre_write_cache', $file_data, $this);

			if (ee()->extensions->end_script === true) {
				return;
			}
		}

		if ($this->filename != '') {
			$filepath = Hop_minifizer_helper::remove_double_slashes($this->config->cache_path . '/' . $this->filename . '.' . $this->type);
		} else {
			$filepath = Hop_minifizer_helper::remove_double_slashes($this->config->cache_path . '/' . $this->cache_filename);
		}

		$success = false;

		//if not match found default should be file
		if ($this->output == 'S3') {
			$success = $this->_put_s3_object($file_data);
		} else {
			$success = file_put_contents($filepath, $file_data);
			if ($this->filename != '') {
				//Create a EE cache key for named files. 
				//This will allow us to clear browser cache for local cache files using v=[creation_date].
				ee()->cache->save(
					$this->cache_namespace . $this->filename, 
					array(
						"cache_filename" => $this->cache_filename,
						"creation_date" => time(),
					),
					31557600
				);
			}
		}
	   
		if ($success === false) {
			throw new Exception('There was an error writing cache file ' . $this->cache_filename . ' to ' . ($this->output == 'S3') ? $this->config->amazon_s3_folder : $this->config->cache_path);
		}

		if ($success === 0) {
			Hop_minifizer_helper::log('The new cache file is empty.', 2);
		}

		// borrowed from /system/expressionengine/libraries/Template.php
		// FILE_READ_MODE is set in /system/expressionengine/config/constants.php
		@chmod($filepath, FILE_READ_MODE);

		Hop_minifizer_helper::log('Cache file `' . $this->cache_filename . '` was written to ' .   ($this->output == 'S3') ? $this->config->amazon_s3_folder : $this->config->cache_path, 3);
	  
		// creating the compressed file
		if ($this->config->is_yes('save_gz')) {
			$z_file = gzopen($filepath . '.gz', 'w9');
			gzwrite($z_file, $file_data);
			gzclose($z_file);
			@chmod($filepath . '.gz', FILE_READ_MODE);
			Hop_minifizer_helper::log('Gzipped file `' . $this->cache_filename . '.gz` was written to ' . $this->config->cache_path, 3);
		}

		// Do we need to clean up expired caches?
		if ($this->config->is_yes('cleanup')) {
			if ($handle = opendir($this->config->cache_path)) {
				while (false !== ($file = readdir($handle))) {
					if ($file == '.' || $file == '..' || $file === $this->cache_filename || $file === $this->cache_filename . '.gz') continue;

					// matches should be deleted
					if (strpos($file, $this->cache_filename_hash) === 0) {
						@unlink($this->config->cache_path . '/' . $file);
						Hop_minifizer_helper::log('Cache file `' . $this->cache_filename . '` has expired. File deleted.', 3);
					}
				}
				closedir($handle);
			}
		}

		// free memory where possible
		unset($filepath, $z_file, $success);
	}
	// ------------------------------------------------------

	/**
	 * Get Amazon S3 client
	 *
	 * @return   S3Client Returns the S3 client. No need to wrap it since errors will be thown on API calls.
	 */
	protected function _get_amazon_s3_client()
	{
		return new S3Client([
				'version' => 'latest',
				'region' => $this->config->amazon_s3_api_region,
				'credentials' => [
					'key' => $this->config->amazon_s3_access_key_id,
					'secret' => $this->config->amazon_s3_secret_access_key,
				],
			]);
	}

	/**
	 * Logs specific Amazon S3 client API calls errors 
	 *
	 * @param    S3Exception ex
	 * @return   void
	 */
	protected function _log_s3_exception($ex)
	{
		switch ($ex->getAwsErrorCode()) {
			case "NoSuchKey":
				Hop_minifizer_helper::log(lang('subfolder_not_exists') . ': `' . $subfolder . '` in Amazon S3', 3);
				break;
			case "NoSuchBucket":
				Hop_minifizer_helper::log(lang('bucket_not_exists') . ': `' . $subfolder . '` in Amazon S3', 3);
				break;
			default:
				Hop_minifizer_helper::log($ex->getMessage());
				break;
		}
	}

	/**
	 * Updates or creates Amazon S3 object
	 *
	 * @param    String  object_contents
	 * @return   boolean Returns true if successfull transaction, false on failure
	 */
	protected function _put_s3_object($object_contents)
	{
		$s3Client = $this->_get_amazon_s3_client();

		try {
			$results = $s3Client->putObject([
				'Bucket' => $this->config->amazon_s3_bucket,
				'Key' => ($this->config->amazon_s3_folder . (($this->filename != '') ? $this->filename . '.' . $this->type : $this->cache_filename)),
				'Body' => $object_contents,
				'StorageClass' => 'STANDARD',
				'ContentType' => $this->type == 'css' ? 'text/css' : 'application/x-javascript',
				'ACL' => 'public-read',
				'Metadata' => [
					'hop-minifizer-key' => $this->cache_filename
				],
			]);

			return true;
		} catch (S3Exception $ex) {
			$this->_log_s3_exception($ex);
			return false;
		}
	}

	/**
	 * Checks if the cache_filename exists in Amazon S3 as an object
	 * Hop Minifizer will look for x-amz-meta-hop-minifizer-key header in order to refresh content.
	 * 
	 * @return   boolean Returns true if successfull transaction, false on failure
	 */
	protected function _s3_object_exists()
	{
		$s3Client = $this->_get_amazon_s3_client();

		try {
			$results = $s3Client->headObject([
				'Bucket' => $this->config->amazon_s3_bucket,
				'Key' => ($this->config->amazon_s3_folder .  (($this->filename != '') ? $this->filename . '.' . $this->type : $this->cache_filename))
			]);

			if (isset($results['Metadata']) && 
				isset($results['Metadata']['hop-minifizer-key']) && 
				$results['Metadata']['hop-minifizer-key'] == $this->cache_filename) {
				return true;
			}
			return false;
		} catch (S3Exception $ex) {
			$this->_log_s3_exception($ex);
			return false;
		}
	}

	protected function _get_external_file_contents($file_name){

		$contents = '';
		// fgc & curl both need http(s): on front
		// so if ommitted, prepend it manually, based on requesting protocol
		if (strpos($file_name, '//') === 0) {
				$prefix = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https:' : 'http:';
				Hop_minifizer_helper::log('Manually prepending protocol `' . $prefix . '` to front of file `' . $file_name . '`', 3);
				$file_name = $prefix . $file_name;
		}
		// determine how to fetch contents
		switch ($this->remote_mode) {
			case ('fgc') :
				// Jason Wells: I hate to suppress errors, but it's only way to avoid one from a 404 response
				// Joshua Fonseca: We keep doing requests through EE to let it handle and parse templates.
				$response = @file_get_contents($file_name);
				if ($response && isset($http_response_header) && (substr($http_response_header[0], 9, 3) < 400))
				{
					$contents = $response;
				}
				else
				{
					throw new Exception('A problem occurred while fetching the following over file_get_contents(): ' . $file_name);
				}

				break;

			case ('curl') :

				$ch = false;
				$ch = curl_init($file_name);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

				$result = curl_exec($ch);

				if ($result == false) {
					if ($this->config->is_yes('debug') && curl_errno($ch)) {
					    $error_msg = curl_error($ch);
					    echo '<pre>';
					    print_r($error_msg);
					    echo '</pre>';
					}

					throw new Exception('Error encountered while fetching `' . $file_name . '` over cURL.');
				}

				$contents = $result;

				break;

			default :
				throw new Exception('Could not fetch file `' . $file_name . '` because neither cURL or file_get_contents() appears available.');
				break;
		}

		return $contents;
	}
}