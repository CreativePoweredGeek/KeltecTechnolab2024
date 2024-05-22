<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// our helper will require_once() everything else we need
require_once PATH_THIRD . 'hop_minifizer/classes/Hop_minifizer_helper.php';
require_once PATH_THIRD . 'hop_minifizer/classes/Hop_minifizer_lib.php';
require_once PATH_THIRD . 'hop_minifizer/vendor/autoload.php';

use Aws\S3\Exception\S3Exception;
use Aws\S3\Exception\AWSException;
use Aws\S3\S3Client;

class Hop_minifizer {

	/**
	 * Reference to our cache
	 */
	private $cache 					= null;

	/**
	 * Our magical config class
	 */
	private $config 				= null;

	/**
	 * Our Hop_minifizer_lib
	 */
	private $MEE 					= null;

	/**
	 * An array of attributes to use when wrapping cache contents in a tag
	 */
	public $attributes				= '';

	/**
	 * Delimiter when exploding files from string
	 */
	public $delimiter				= ',';

	/**
	 * Type of format/content to return (contents, url or tag)
	 */
	public $display 				= '';

	/**
	 * When combine="no", what to separate each cache return value with
	 */
	public $display_delimiter		= ['contents' => "\n", 'url' => ',', 'tag' => ''];

	/**
	 * Our local property of filenames to cache
	 */
	public $files					= [];

	/**
	 * What to return if error
	 */
	public $on_error				= '';

	/**
	 * Name of our queue, if running
	 */
	public $queue					= '';
	
	/**
	 * Template with which to render css link or js script tags
	 */
	public $template				= '{hop_minifizer}';

	/**
	 * What type of asset to process
	 */
	public $type					= '';

	/**
	 * Output type
	 */
	public $output					= 'file';

	/**
	 * Output file name for amazon S3
	 */
	public $filename				= '';

	/**
	 * SCSS templates
	 */
	public $scss_templates			= [];

	/**
	 * Is API call
	 */
	public $is_api_call				= false;

	// ------------------------------------------------------


	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct($str = '')
	{
		// grab reference to our cache
		$this->cache =& Hop_minifizer_helper::cache();

		// grab instance of our config object
		$this->config = Hop_minifizer_helper::config();

		// instantiate our Hop_minifizer_lib, pass our static config
		$this->MEE = new Hop_minifizer_lib($this->config);

		Hop_minifizer_helper::log('Hop_minifizer instantiated', 3);

		// magic: run as our "api"
		// Tagparts would have a length of 1 if calling Hop_Minifizer like {exp:hop_minifizer}...{/exp:hop_minifizer}
		// $str would contain custom field content if used as a field modifier (e.g. {ft_stylesheet:hop_minifizer})
		// Note that this is entirely untested and undocumented, and would require passing a type=""
		// parameter so that Hop Minifizer knows what sort of asset it's operating on. But in theory it's possible.
		if (isset(ee()->TMPL) && count(ee()->TMPL->tagparts) == 1 || $str) {
			$this->is_api_call = true;
			$this->return_data = $this->api($str);
		}

		ee()->lang->loadfile('hop_minifizer');

		Hop_minifizer_helper::log('Hop_Minifizer instantiated', 3);
	}

	/**
	 * API-like interface
	 *
	 * @param String 	Filename(s) of asset(s) IFF being called as field modifier
	 * @return void
	 */
	public function api($assets = '')
	{
		// a custom field modifier needs a type
		if ($assets != '' && ! ee()->TMPL->fetch_param('type')) {
			$this->on_error = $assets;
			return $this->_abort('You must specify a type (css or js) when using custom field modifier.');
		}

		// can't specify css and js at same time
		if ($assets == '' && ee()->TMPL->fetch_param('css') && ee()->TMPL->fetch_param('js')) {
			// this will be horribly wrong, but it's at least something
			$this->on_error = ee()->TMPL->fetch_param('css') . "\n" . ee()->TMPL->fetch_param('js');
			return $this->_abort('You may not specify css="" and js="" in the same API call.');
		}

		if (ee()->TMPL->fetch_param('js')) {
			$assets = ee()->TMPL->fetch_param('js');
			$this->type = 'js';
		}

		if (ee()->TMPL->fetch_param('css')) {
			$assets = ee()->TMPL->fetch_param('css');
			$this->type = 'css';
		}

		$this->on_error = $assets;

		// set our display format
		$this->_set_display();

		// set parameters that affect config
		$this->_fetch_params();

		$this->_fetch_files($assets);

		// should we set our files to queue for later?
		if ($this->queue) {
			return $this->_set_queue();
		}
		try {
			$filenames = $this->MEE->run($this->filename, $this->output, $this->type, $this->files, $this->scss_templates);

			// format and return
			return $this->_return($filenames);

		} catch (Exception $e) {
			return $this->_abort($e);
		}
	}

	/**
	 * Plugin function: exp:hop_minifizer:contents
	 *
	 * @return mixed string or empty
	 */
	public function contents()
	{
		return $this->display('contents');
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:hop_minifizer:css
	 *
	 * @return mixed string or empty
	 */
	public function css()
	{
		// set local version of tagdata
		$this->on_error = ee()->TMPL->tagdata;

		// our asset type
		$this->type = 'css';

		return $this->_run();
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:hop_minifizer:display
	 *
	 * @param string type of display to return
	 * @param bool true or false whether calling from template_post_parse hook
	 * @return mixed string or empty
	 */
	public function display($method = '', $calling_from_hook = false)
	{
		// abort error if no queue was provided
		if ( ! ee()->TMPL->fetch_param('js') && ! ee()->TMPL->fetch_param('css')) {
			return $this->_abort('You must specify a queue name.');
		}

		// see if calling via exp:hop_minifizer:display:method syntax
		$this->_set_display($method);

		// try to postpone until template_post_parse
		if ( ! $calling_from_hook && $out = $this->_postpone($this->display)) {
			return $out;
		}

		// walk through both types
		$return = '';

		// now determine what asset type, and fetch our queue
		if (ee()->TMPL->fetch_param('js')) {
			$this->queue = ee()->TMPL->fetch_param('js');
			$this->type = 'js';

			$return .= $this->_display();
		}

		if (ee()->TMPL->fetch_param('css')) {
			$this->queue = ee()->TMPL->fetch_param('css');
			$this->type = 'css';

			$return .= $this->_display();
		}

		return $return;
	}

	/**
	 * Plugin function: exp:hop_minifizer:embed
	 *
	 * Alias of exp:hop_minifizer:contents
	 *
	 * @return mixed string or empty
	 */
	public function embed()
	{
		return $this->display('contents');
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:hop_minifizer:html
	 *
	 * @return void
	 */
	public function html()
	{
		// we do not need to actually do anything. Simply being called is enough.
		return;
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:hop_minifizer:js
	 *
	 * @return mixed string or empty
	 */
	public function js()
	{
		// set local version of tagdata
		$this->on_error = ee()->TMPL->tagdata;

		// our asset type
		$this->type = 'js';

		return $this->_run();
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:hop_minifizer:link
	 *
	 * Alias to exp:hop_minifizer:url
	 *
	 * @return mixed string or empty
	 */
	public function link()
	{
		return $this->display('url');
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:hop_minifizer:tag
	 *
	 * Return the tags for cache
	 *
	 * @return mixed string or empty
	 */
	public function tag()
	{
		return $this->display('tag');
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:hop_minifizer:url
	 *
	 * Rather than returning the tags or cache contents, simply return URL to cache(s)
	 *
	 * @return mixed string or empty
	 */
	public function url()
	{
		return $this->display('url');
	}
	// ------------------------------------------------------

	/**
	 * Abort and return original tagdata.
	 * Logs the error message.
	 *
	 * @param mixed The caught exception or string
	 * @return string The value of our Hop_minifizer::on_error property
	 */
	protected function _abort($e = false)
	{
		if ($e && is_string($e)) {
			$log = $e;
		} elseif ($e) {
			$log = $e->getMessage();
		} else {
			$log = 'Aborted without a specific error.';
		}

		// log our error message
		Hop_minifizer_helper::log($log, 1);

		// return our on_error content
		return $this->on_error;
	}
	// ------------------------------------------------------


	/**
	 * Internal function to return contents of cache file
	 *
	 * @return	Contents of cache (css or js)
	 */
	protected function _cache_contents($filename)
	{
		$open = $close = '';

		// If attributes have been supplied, it is inferred we should wrap
		// our output in tags
		if ($this->attributes) {
			switch ($this->type) {
				case 'css' :
					$open = '<style' . $this->attributes . '>';
					$close = '</style>';
					break;

				case 'js' :
					$open = '<script' . $this->attributes . '>';
					$close = '</script>';
					break;
			}
		}

		// silently get and return cache contents, wrapped in open and close
		return $open . @file_get_contents($this->_cache_path($filename)) . $close;
	}
	// ------------------------------------------------------


	/**
	 * Internal function for making link to cache
	 *
	 * @return	String containing an HTML tag reference to given reference
	 */
	protected function _cache_path($filename)
	{
		// build link from cache url + cache filename
		return Hop_minifizer_helper::remove_double_slashes($this->config->cache_path . '/' . $filename, true);
	}
	// ------------------------------------------------------


	/**
	 * Internal function for making tag strings
	 *
	 * @return	String containing an HTML tag reference to given reference
	 */
	protected function _cache_tag($filename)
	{
		$tmpl = $this->template;
		if ($tmpl == '' || $tmpl == '{hop_minifizer}' || $this->attributes) {
			switch($this->type) {
				case 'css' :
					$tmpl = '<link rel="stylesheet" type="text/css" href="{hop_minifizer}"' . $this->attributes . ' />';
					break;

				case 'js' :
					$tmpl = '<script src="{hop_minifizer}"' . $this->attributes . '></script>';
					break;
			}
		}

		// inject our cache url into template and return
		return str_replace('{hop_minifizer}', $this->_cache_url($filename), $tmpl);
	}
	// ------------------------------------------------------


	/**
	 * Internal function for making link to cache
	 *
	 * @return	String containing an HTML tag reference to given reference
	 */
	protected function _cache_url($filename)
	{
		// build link from cache url + cache filename
		if ($this->output == "S3") {
			return 'https://s3.' . $this->config->amazon_s3_api_region . '.amazonaws.com/' . $this->config->amazon_s3_bucket . '/' . $this->config->amazon_s3_folder . (!empty($this->filename) ? $this->filename . '.' . $this->type : $filename);
		}

		$temp_cache_key = ee()->cache->get('/hop_minifizer/' . $filename);

		if (!empty($temp_cache_key)) {
			$filename = (!empty($this->filename) ? $this->filename . '.' : '') . $this->type . QUERY_MARKER . 'v=' . $temp_cache_key['creation_date'];
		}

		return Hop_minifizer_helper::remove_double_slashes($this->config->cache_url . '/' . $filename , true);
	}
	// ------------------------------------------------------


	/**
	 * Internal function used by exp:hop_minifizer:display
	 *
	 * @return mixed string or empty
	 */
	protected function _display()
	{
		// fetch our parameters
		$this->_fetch_params();

		// fetch from our queue
		$this->_fetch_queue();

		// let's do this
		try {
			$filenames = $this->MEE->run($this->filename, $this->output, $this->type, $this->files);

			// format and return
			return $this->_return($filenames);

		} catch (Exception $e) {
			return $this->_abort($e);
		}
	}
	// ------------------------------------------------------


	/**
	 * Parse tagdata for <link> and <script> tags,
	 * pulling out href & src attributes respectively.
	 * [Adapted from SL Combinator]
	 *
	 * @return bool true on success of fetching files; false on failure
	 */
	protected function _fetch_files($haystack = false)
	{
		if ($haystack === false) {
			$tagdata = ee()->TMPL->tagdata;
		}

		// try to match any pattern of css or js tag
		if ($matches = Hop_minifizer_helper::preg_match_by_type($tagdata, $this->type)) {
			// set our tag template
			$this->template = str_replace($matches[1][0], '{hop_minifizer}', $matches[0][0]);

			// set our files array
			$this->files[0] = $matches[1];

			if ($this->type == 'css' && isset($matches[3])) {
				$this->files[1] = $matches[3];
			}
		}

		// no matches; assume entire haystack is our asset
		// this should only happen when using API interface
		// Joshua Fonseca: Let's not assume and use is_api_call variable
		if ($this->is_api_call) {
			$this->files[0] = explode($this->delimiter, $haystack);
		}

		if (count($this->files) == 0) {
			Hop_minifizer_helper::log('Hop_minifizer inner tag content is empty.', 3);
		}

		// chaining
		return $this;
	}
	// ------------------------------------------------------


	/**
	 * Fetch parameters from ee()->TMPL
	 *
	 * @return void
	 */
	protected function _fetch_params()
	{
		/*
		 * Part 1: Parameters which may override defaults
		 */
		// set type
		$this->type = ee()->TMPL->fetch_param('type', $this->type);
		
		// override display format
		$this->display = ee()->TMPL->fetch_param('display', $this->display);

		// override delimiters?
		if ( ee()->TMPL->fetch_param('delimiter')) {
			$this->delimiter = ee()->TMPL->fetch_param('delimiter');
			$this->display_delimiter[$this->display] = ee()->TMPL->fetch_param('delimiter');
		}

		// display delimiter may also be specified
		$this->display_delimiter[$this->display] = ee()->TMPL->fetch_param('display_delimiter', $this->display_delimiter[$this->display]);

		// tag attributes for returning cache contents
		if (is_array(ee()->TMPL->tagparams)) {
			foreach (ee()->TMPL->tagparams as $key => $val) {
				if (strpos($key, 'attribute:') === 0) {
					$this->attributes .= ' ' . substr($key, 10) . '="' . $val . '"';
				}
			}
		}

		/*
		 * Part 2: config
		 */
		$tagparams = ee()->TMPL->tagparams;

		// we do need to account for the fact that minify="no" is assumed to be pertaining to the tag
		if (isset($tagparams['combine'])) {
			$tagparams['combine_' . $this->type] = $tagparams['combine'];
		}

		if (isset($tagparams['minify'])) {
			$tagparams['minify_' . $this->type] = $tagparams['minify'];
		}

		// pass all params through our config, will magically pick up what's needed
		$this->MEE->config->reset()->extend($tagparams);

		// fetch queue if it hasn't already been set via Hop_minifizer::_display()
		if ( ! $this->queue) {
			$this->queue = strtolower(ee()->TMPL->fetch_param('queue', null));
		}

		$this->filename = ee()->TMPL->fetch_param('filename', $this->filename);
		$this->output = ee()->TMPL->fetch_param('output', $this->output);

		//Set scss_templates only when type is css
		if ($this->type == 'css') {
			$temp_scss_templates = ee()->TMPL->fetch_param('scss_templates', '');

			if($temp_scss_templates != ''){
				$this->scss_templates = preg_split('/[|]/', $temp_scss_templates);
			}
		}

		unset($tagparams);

		// chaining
		return $this;
	}
	// ------------------------------------------------------


	/**
	 * Retrieve files from cache
	 *
	 * @return void
	 */
	protected function _fetch_queue()
	{
		if ( ! isset($this->cache[$this->type][$this->queue])) {
			Hop_minifizer_helper::log('Could not find a queue of files by the name of \'' . $this->queue . '\'.', 3);
		} else {
			// set our tag template
			$this->template = $this->cache[$this->type][$this->queue]['template'];

			// TODO: re-set other runtime properties

			// files: order by priority
			ksort($this->cache[$this->type][$this->queue]['files']);

			// build our files property
			foreach ($this->cache[$this->type][$this->queue]['files'] as $file) {
				$this->files[0] = array_merge($this->files[0], $file);
			}

			// on_error: order by priority
			ksort($this->cache[$this->type][$this->queue]['on_error']);

			// build our on_error property
			foreach ($this->cache[$this->type][$this->queue]['on_error'] as $error) {
				$this->on_error .= implode("\n", $error) . "\n";
			}

			// No files found?
			if ( ! is_array($this->files[0]) OR count($this->files[0]) == 0) {
				Hop_minifizer_helper::log('No files found in the queue named \'' . $this->type . '\'.', 3);
			}
		}

		// chaining
		return $this;
	}
	// ------------------------------------------------------


	protected function _flightcheck()
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
	}

	/**
	 * Postpone processing our method until template_post_parse hook?
	 *
	 * @param String	Method name
	 * @return Mixed	true if delay, false if not
	 */
	protected function _postpone($method)
	{
		// definitely do not postpone if EE is less than 2.4
		if (version_compare(APP_VER, '2.4', '<')) {
			return false;
		} else {
			// base our needle off the calling tag
			$needle = sha1(ee()->TMPL->tagproper);

			// save our tagparams to re-instate during calling of hook
			$tagparams = ee()->TMPL->tagparams;

			if ( ! isset($this->cache['template_post_parse'])) {
				$this->cache['template_post_parse'] = [];
			}

			$this->cache['template_post_parse'][$needle] = [
				'method' => $method,
				'tagparams' => $tagparams
			];

			Hop_minifizer_helper::log('Postponing process of Hop_minifizer::display(`' . $method . '`) until template_post_parse hook.', 3);

			// return needle so we can find it later
			return LD.$needle.RD;
		}
	}
	// ------------------------------------------------------


	/**
	 * Reset class properties to their defaults
	 *
	 * @return mixed string or empty
	 */
	public function reset()
	{
		$defaults = Hop_minifizer_helper::hop_minifizer_class_vars();

		foreach ($defaults as $name => $default) {
			$this->$name = $default;
		}

		Hop_minifizer_helper::log('Public properties have been reset to their defaults.', 3);

		return $this;
	}
	// ------------------------------------------------------


	/**
	 * Return contents as determined by $this->display
	 *
	 * @return mixed string or empty
	 */
	protected function _return($filenames)
	{
		// what we will eventually return
		$return = [];

		// cast to array for ease
		if ( ! is_array($filenames)) {
			$filenames = [$filenames];
		}

		foreach ($filenames as $filename) {
			switch ($this->display) {
				case 'contents' :
					$return[] = $this->_cache_contents($filename);
					break;

				case 'url' :
					$return[] = $this->_cache_url($filename);
					break;

				case 'tag' :
				default :
					$return[] = $this->_cache_tag($filename);
					break;
			}
		}

		// glue output based on type
		return implode($this->display_delimiter[$this->display], $return);
	}
	// ------------------------------------------------------


	/**
	 * Called by Hop_minifizer:css and Hop_minifizer:js, performs basic run command
	 *
	 * @return mixed string or empty
	 */
	protected function _run()
	{
		// set our return format
		$this->_set_display();

		// fetch our parameters
		$this->_fetch_params();

		// fetch our files
		$this->_fetch_files();

		// quick flightcheck
		try {
			$this->_flightcheck();
		} catch (Exception $e) {
			return $this->_abort($e);
		}

		// should we set our files to queue for later?
		if ($this->queue) {
			return $this->_set_queue();
		}

		// let's do this
		try {
			$filenames = $this->MEE->run($this->filename, $this->output, $this->type, $this->files, $this->scss_templates);

			// format and return
			return $this->_return($filenames);

		} catch (Exception $e) {
			return $this->_abort($e);
		}
	}
	// ------------------------------------------------------


	/**
	 * Set our display property
	 *
	 * @return void
	 */
	protected function _set_display($format = '')
	{
		// if not passed, fetch last tagpart
		if( ! $format) {
			$format = ee()->TMPL->tagparts[count(ee()->TMPL->tagparts) - 1];
		}

		// consolidate our aliases into allowed methods
		switch ($format) {
			case 'hop_minifizer' :
			case 'url' :
			case 'link' :
				$this->display = 'url';
				break;

			case 'contents' :
			case 'embed' :
				$this->display = 'contents';
				break;

			case 'css' :
			case 'js' :
			case 'tag' :
			case 'display' :
			default :
				$this->display = 'tag';
				break;
		}
	}
	// ------------------------------------------------------


	/**
	 * Adds the files to be queued into session
	 *
	 * @param string either 'js' or 'css'
	 * @return void
	 */
	protected function _set_queue()
	{
		// be sure we have a cache set up
		if ( ! isset($this->cache[$this->type])) {
			$this->cache[$this->type] = [];
		}

		// create new session array for this queue
		if ( ! isset($this->cache[$this->type][$this->queue])) {
			$this->cache[$this->type][$this->queue] = [
				'template' => $this->template,
				'on_error' => [],
				'files' => []
			];
		}

		// be sure we have a priority key in place
		$priority = (int) ee()->TMPL->fetch_param('priority', 0);
		if ( ! isset($this->cache[$this->type][$this->queue]['files'][$priority])) {
			$this->cache[$this->type][$this->queue]['files'][$priority] = [];
		}

		// Add $on_error
		if ( ! isset($this->cache[$this->type][$this->queue]['on_error'][$priority])) {
			$this->cache[$this->type][$this->queue]['on_error'][$priority] = [];
		}
		$this->cache[$this->type][$this->queue]['on_error'][$priority][] = $this->on_error;

		// TODO: save other runtime properties

		// Add all files to the queue cache 
		foreach($this->files as $file) {
			$this->cache[$this->type][$this->queue]['files'][$priority][] = $file[0];
		}
	}
	// ------------------------------------------------------

    public function test_amazon_access_keys()
    {
		$error = "";

        $bucket = $this->config->amazon_s3_bucket;
        $folder = $this->config->amazon_s3_folder;

        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => $this->config->amazon_s3_api_region,
            'credentials' => [
                'key' => $this->config->amazon_s3_access_key_id,
                'secret' => $this->config->amazon_s3_secret_access_key,
            ],
        ]);

        try {
            $results = $s3Client->getObject([
                'Bucket' => $bucket,
                'Key' => $folder,
            ]);

            //Verify the object is a folder
			if (($results['ContentType'] == "application/octet-stream" || 
			$results['ContentType'] == "application/octet-stream") && Hop_minifizer_helper::ends_with($results['Key'], "/")) {
                $error = lang('folder_not_exists') . ": $folder" . $results['ContentType'];
            }
        } catch (S3Exception $ex) {
            switch ($ex->getAwsErrorCode()) {
                case "NoSuchKey":
                    $error = lang('folder_not_exists') . ": $folder";
                    break;
                case "NoSuchBucket":
                    $error = lang('bucket_not_exists') . ": $bucket";
                    break;
                default:
                    $error = $ex->getMessage();
                    break;
            }
        }

        unset($s3Client);

        header('Content-Type: text/html; charset=utf-8');

        if ($error != '') 
            echo "<h2>Hop Minifizer</h2><p>$error</p>";
        else 
			echo "<h2>Hop Minifizer</h2><p style='color:green'>Test to Amazon S3 passed.</p>";
			
		exit();
	}
}