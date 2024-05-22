<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'hop_minifizer/classes/Hop_minifizer_helper.php';

class Hop_minifizer_ext {

	public $name			= HOP_MINIFIZER_NAME;
	public $short_name 		= HOP_MINIFIZER_SHORT_NAME;
	public $version			= HOP_MINIFIZER_VERSION;
	public $settings 		= '';
	public $settings_exist	= 'y';

	/**
	 * Our magical config class
	 */
	public $config;

	/**
	 * Reference to our cache
	 */
	public $cache;

	/**
	 * Constructor
	 *
	 * NOTE: We never use the $settings variable passed to us,
	 * because we want our Hop_minifizer_config object to always be in charge.
	 *
	 * @param 	mixed	Settings array - only passed when activating a hook
	 * @return void
	 */
	public function __construct()
	{
		// grab a reference to our cache
		$this->cache =& Hop_minifizer_helper::cache();

	}

	private function _loadConfig()
	{
		if (empty($this->config)) {
			$this->config = Hop_minifizer_helper::config();
		}
	}

	public function activate_extension() {
		$this->_setExtensions();
	}
	
	public function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version) {
			return false;
		}

		$this->_setExtensions();
	}

	public function disable_extension()
	{
		ee()->db->where('class', __CLASS__);
		ee()->db->delete('extensions');
	}

	private function _setExtensions()
	{
		$extensions = [
			[
				// Add this add on to the menu manger
				'class'		=> __CLASS__,
				'method'	=> 'addToMenu',
				'hook'		=> 'cp_custom_menu',
				'settings'	=> $this->settings,
				'priority'	=> 10,
				'version'	=> $this->version,
				'enabled'	=> 'y'
			],
			[
				'class'		=> __CLASS__,
				'hook'		=> 'template_post_parse',
				'method'	=> 'template_post_parse',
				'settings'	=> $this->settings,
				'priority'	=> 10,
				'version'	=> $this->version,
				'enabled'	=> 'y'
			],
			[
				'class'		=> __CLASS__,
				'hook'		=> 'ee_debug_toolbar_add_panel',
				'method'	=> 'ee_debug_toolbar_add_panel',
				'settings'	=> $this->settings,
				'priority'	=> 10,
				'version'	=> $this->version,
				'enabled'	=> 'y'
			],
			[
				'class'		=> __CLASS__,
				'hook'		=> 'ce_cache_pre_save',
				'method'	=> 'ce_cache_pre_save',
				'settings'	=> $this->settings,
				'priority'	=> 10,
				'version'	=> $this->version,
				'enabled'	=> 'y'
			]
		];

		foreach ($extensions as $extension) {
			// Check if set already
			if (ee()->db->select('extension_id')->from('extensions')->where(['class' => $extension['class'], 'method' => $extension['method']])->get()->num_rows() > 0) {
				// Update the extensions
				ee()->db->update(
					'extensions',
					[
						'hook'		=> $extension['hook'],
						'settings'	=> $extension['settings'],
						'priority'	=> $extension['priority'],
						'version'	=> $extension['version'],
						'enabled'	=> $extension['enabled']
					],
					[
						'class'		=> $extension['class'],
						'method'	=> $extension['method']
					]
				);
			} else {
				ee()->db->insert('extensions', $extension);
			}
		}
	}

	/**
	 * Method for template_post_parse hook
	 *
	 * @param 	array	Array of debug panels
	 * @param 	arrat	A collection of toolbar settings and values
	 * @return 	array	The amended array of debug panels
	 */
	public function ee_debug_toolbar_add_panel($panels, $view)
	{
		// do nothing if not a page
		if(REQ != 'PAGE') return $panels;

		// play nice with others
		$panels = (ee()->extensions->last_call != '' ? ee()->extensions->last_call : $panels);

		$panels['hop_minifizer'] = new Eedt_panel_model();
		$panels['hop_minifizer']->set_name('hop_minifizer');
		$panels['hop_minifizer']->set_button_label("Hop_minifizer");
		$panels['hop_minifizer']->set_button_icon("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyhpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDIxIDc5LjE1NTc3MiwgMjAxNC8wMS8xMy0xOTo0NDowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTQgKE1hY2ludG9zaCkiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6NjQxMzVDNTBGRDdCMTFFMzhDQzk5MzI3QzQ4QkE1NDUiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6NjQxMzVDNTFGRDdCMTFFMzhDQzk5MzI3QzQ4QkE1NDUiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpFQkM3RkNGNUZENzUxMUUzOENDOTkzMjdDNDhCQTU0NSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpFQkM3RkNGNkZENzUxMUUzOENDOTkzMjdDNDhCQTU0NSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PpUOrpsAAAAQSURBVHjaYvj//z8DQIABAAj8Av7bok0WAAAAAElFTkSuQmCC");
		$panels['hop_minifizer']->set_panel_contents(ee()->load->view('eedebug_panel', ['logs' => Hop_minifizer_helper::get_log()], true));

		if (Hop_minifizer_helper::log_has_error()) {
			$panels['hop_minifizer']->set_panel_css_class('flash');
		}

		return $panels;
	}

	/**
	 * Alias for backwards-compatibility with M1
	 */
	public function minify_html($template, $sub, $site_id)
	{
		return $this->template_post_parse($template, $sub, $site_id);
	}

	/**
	 * Hook for CE Cache
	 *
	 * @param string $template
	 * @param string $type 'fragment' or 'static'
	 */
	public function ce_cache_pre_save($template, $type)
	{
		$this->_loadConfig();

		// play nice with others
		if (isset(ee()->extensions->last_call) && ee()->extensions->last_call) {
			$template = ee()->extensions->last_call;
		}

		// Are we configured to run HTML minification on this hook?
		if ($this->config->minify_html_hook != 'ce_cache_pre_save') {
			Hop_minifizer_helper::log('HTML minification is not configured to run when saving CE Cache contents.', 3);
			return $template;
		}

		// do and done
		Hop_minifizer_helper::log('HTML minification is configured to run whenever saving CE Cache contents.', 3);
		return $this->_minify_html($template);
	}

	/**
	 * Method for template_post_parse hook
	 *
	 * @param 	string	Parsed template string
	 * @param 	bool	Whether is a sub-template (partial as of EE 2.8) or not
	 * @param 	string	Site ID
	 * @return 	string	Template string, possibly minified
	 */
	public function template_post_parse($template, $sub, $site_id)
	{
		$this->_loadConfig();

		// play nice with others
		if (isset(ee()->extensions->last_call) && ee()->extensions->last_call) {
			$template = ee()->extensions->last_call;
		}

		// do nothing if not final template
		if ($sub !== false) {
			return $template;
		}

		// do nothing if not (likely) html!
		if ( ! preg_match('/webpage|static/i', ee()->TMPL->template_type)) {
			return $template;
		}

		// attempt to post-process Hop_minifizer's display tag
		$template = $this->_display_post_parse($template);

		// Are we configured to run HTML minification on this hook?
		if ($this->config->minify_html_hook != 'template_post_parse') {
			Hop_minifizer_helper::log('HTML minification is not configured to run during template_post_parse.', 3);
			return $template;
		}

		// do and done
		Hop_minifizer_helper::log('HTML minification is configured to run during the final call to template_post_parse.', 3);
		return $this->_minify_html($template);
	}

	/**
	 * Helper function to find & process any queue'd plugin tags
	 *
	 * @param string $template
	 * @return string
	 */
	protected function _display_post_parse($template)
	{
		// see if we need to post-render any plugin methods
		if (isset($this->cache['template_post_parse'])) {
			if ( ! class_exists('Hop_minifizer')) {
				include_once PATH_THIRD . 'hop_minifizer/pi.hop_minifizer.php';
			}

			// create a new instance of Hop_minifizer each time to guarantee defaults
			$m = new Hop_minifizer();

			// save our TMPL values to put back into place once finished
			$tagparams = ee()->TMPL->tagparams;

			// loop through & call each method
			foreach($this->cache['template_post_parse'] as $needle => $tag) {
				Hop_minifizer_helper::log('Calling Hop_minifizer::display("' . $tag['method'] . '") during template_post_parse: ' . serialize($tag['tagparams']), 3);

				ee()->TMPL->tagparams = $tag['tagparams'];

				// our second parameter tells Hop_minifizer we are calling from template_post_parse
				$out = $m->display($tag['method'], true);

				// replace our needle with output
				$template = str_replace(LD.$needle.RD, $out, $template);

				// reset Hop_minifizer for next loop
				$m->reset();
			}

			// put things back into place
			ee()->TMPL->tagparams = $tagparams;
		}

		return $template;
	}

	/**
	 * Run html minification on template tagdata
	 *
	 * @param string $template
	 * @return 	string
	 */
	protected function _minify_html($template)
	{
		$this->_loadConfig();
		
		// Are we configured to run through HTML minifier?
		if ($this->config->is_no('minify_html')) {
			Hop_minifizer_helper::log('HTML minification is disabled.', 3);
			return $template;
		}

		// is Hop_minifizer nonetheless disabled?
		if ($this->config->is_yes('disable')) {
			Hop_minifizer_helper::log('HTML minification aborted because Hop_minifizer has been disabled completely.', 3);
			return $template;
		}

		Hop_minifizer_helper::log('Running HTML minification.', 3);

		Hop_minifizer_helper::library('html');

		// run css & js minification?
		$opts = [];

		if ($this->config->is_yes('minify_css')) {
			$opts['cssMinifier'] = ['Minify_CSS', 'minify'];
		}

		if ($this->config->is_yes('minify_js')) {
			$opts['jsMinifier'] = ['JSMin', 'minify'];
		}

		return Minify_HTML::minify($template, $opts);
	}

	public function addToMenu($menu)
	{
		$sub = $menu->addItem($this->name, ee('CP/URL')->make('addons/settings/' . $this->short_name));

		$addon_icon_act = ee()->cp->fetch_action_id('File', 'addonIcon');

		// For EE6 only
		if (!empty($addon_icon_act)) {
			$site_url = site_url();
			$addon_icon_url = $site_url . '?ACT=' . $addon_icon_act . '&addon=' . $this->short_name . '&file=icon.svg';

			$javascript_addon_name = 'addon_' . $this->short_name;

			// Replace with our own icon :D
			ee()->cp->add_to_foot('<script>
				if (typeof ' . $javascript_addon_name . ' === \'undefined\') {
					let ' . $javascript_addon_name . ' = $(\'.ee-sidebar__item[title="' . $this->name . '"] .ee-sidebar__item-custom-icon\');' .
					$javascript_addon_name . '.html(\'<img src="' . $addon_icon_url . '" style="display: inline-block; vertical-align: middle;">\');' .
					$javascript_addon_name . '.css(\'background\', \'none\');
				}
			</script>');
		}
	}
}