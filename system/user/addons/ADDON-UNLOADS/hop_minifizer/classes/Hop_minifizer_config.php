<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// just in case
require_once PATH_THIRD . 'hop_minifizer/classes/Hop_minifizer_helper.php';

class Hop_minifizer_config {

    /**
     * Where we find our config - 'db', 'config', 'hook', 'default' or 'manual'.
     * A 3rd party hook may also rename to something else.
     */
    public $location = FALSE;

    /**
     * Allowed settings - the master list
     * If it isn't listed here, it won't exist during runtime
     */
    protected $_allowed = array(
        'base_path'                     => '',
        'base_url'                      => '',
        'cachebust'                     => '',
        'cache_path'                    => '',
        'cache_url'                     => '',
        'cleanup'                       => '',
        'combine_css'                   => '',
        'combine_js'                    => '',
        'css_library'                   => '',
        'css_prepend_mode'              => '',
        'css_prepend_url'               => '',
        'debug'                         => '',
        'disable'                       => '',
        'hash_method'                   => '',
        'js_library'                    => '',
        'minify_css'                    => '',
        'minify_html'                   => '',
        'minify_html_hook'              => '',
        'minify_js'                     => '',
        'save_gz'                       => '',
        'remote_mode'                   => '',
        'amazon_s3_access_key_id'       => '',
        'amazon_s3_secret_access_key'   => '',
        'amazon_s3_bucket'              => '',
        'amazon_s3_api_region'          => '',
        'amazon_s3_folder'              => '',
    );


    /**
     * Default settings
     *
     * Set once during init and NOT modified thereafter.
     */
    protected $_default = array();


    /**
     * Runtime settings
     *
     * Overrides of defaults used at runtime; our only settings modified directly.
     */
    protected $_runtime = array();


    // ------------------------------------------------------


    /**
     * Constructor function
     *
     * If an array is passed, then we will avoid our own internal init
     *
     * @param Array An array of settings to override normal init()
     */
    public function __construct($override = FALSE)
    {
        $this->init($override);
    }
    // ------------------------------------------------------


    /**
     * Magic Getter
     *
     * First looks for setting in Hop_minifizer_config::$_runtime; then Hop_minifizer_config::$_default.
     * If requesting all settings, returns complete array
     *
     * @param   string  Name of setting to retrieve
     * @return  mixed   Array of all settings, value of individual setting, or NULL if not valid
     */
    public function __get($prop)
    {
        // Find & retrieve the runtime setting
        if (array_key_exists($prop, $this->_runtime))
        {
            return $this->_runtime[$prop];
        }

        // Find & retrieve the default setting
        if (array_key_exists($prop, $this->_default))
        {
            return $this->_default[$prop];
        }

        // I guess it's OK to ask for a raw copy of our settings
        if ($prop == 'settings')
        {
            // merge with defaults first
            return array_merge($this->_default, $this->_runtime);
        }

        // Nothing found. Something might be wrong so log a debug message
        Hop_minifizer_helper::log(sprintf(lang('config_prop_not_valid'), $prop), 2);

        return NULL;
    }
    // ------------------------------------------------------


    /**
     * Magic Setter
     *
     * @param   string  Name of setting to set
     * @return  mixed   Value of setting or NULL if not valid
     */
    public function __set($prop, $value)
    {
        // are we setting the entire Hop_minifizer_config::settings array?
        if ($prop == 'settings' && is_array($value))
        {
            // is our array empty? if so, consider it "reset"
            if (count($value) === 0)
            {
                $this->_runtime = array();
            }
            else
            {
                $this->_runtime = $this->sanitise_settings($value);
            }
        }
        // just set an individual setting
        elseif (array_key_exists($prop, $this->_allowed))
        {
            $this->_runtime[$prop] = $this->sanitise_setting($prop, $value);
        }
    }
    // ------------------------------------------------------


    /**
     * Explicit method for setting/modifying runtime settings
     * __set() still does heavy lifting
     *
     * @param   array
     * @return  Object  $this
     */
    public function extend($extend = array())
    {
        // must be an array
        if (is_array($extend))
        {
            $this->settings = $extend;
        }

        //chaining
        return $this;
    }
    // ------------------------------------------------------


    /**
     * Reset our runtime to 'factory' defaults
     *
     * @return  Object  $this
     */
    public function factory()
    {
        // reset & extend to our empty allowed
        $this->reset()->extend($this->get_allowed());

        //chaining
        return $this;
    }
    // ------------------------------------------------------


    /**
     * Return copy of allowed settings
     *
     * @return  array
     */
    public function get_allowed()
    {
        return $this->_allowed;
    }
    // ------------------------------------------------------


    /**
     * Return copy of default settings
     *
     * @return  array
     */
    public function get_default()
    {
        return $this->_default;
    }
    // ------------------------------------------------------


    /**
     * Return copy of runtime settings
     *
     * @return  array
     */
    public function get_runtime()
    {
        return $this->_runtime;
    }
    // ------------------------------------------------------


    /**
     * Initialise / Initialize.
     *
     * Retrieves settings from: session, hop_minifizer_get_settings hook, config OR database (and in that order).
     *
     * @return void
     */
    public function init($override = FALSE)
    {
        // we are trying to turn this into an array full of goodness.
        $settings = FALSE;

        //Test: See if anyone is hooking in
        // Skip this if we're doing anything with our own extension settings
        if ( ! (isset($_GET['M']) && $_GET['M'] == 'extension_settings' && $_GET['file'] == 'hop_minifizer'))
        {
            $settings = $this->_from_hook();
        }

        // Test: Manually passed?
        if ($settings === FALSE && is_array($override))
        {
            Hop_minifizer_helper::log(lang('config_settings_manual_override'), 3);

            $this->location = 'manual';

            $settings = $override;
        }

        // Test: Look in config
        if ($settings === FALSE)
        {
            $settings = $this->_from_config();
        }

        // Test 3: Look in db
        if ($settings === FALSE)
        {
            $settings = ee('Model')->get('hop_minifizer:Config')->all()->getDictionary('setting_name', 'value');
        }

        // Test 4: Legacy backwards-compatibility
        if ($settings === FALSE)
        {
            $settings = $this->_from_config_legacy();

            // global vars??
            if ($settings === FALSE)
            {
                $settings = $this->_from_global_vars_legacy();
            }
        }

        // Run on default
        if ( $settings === FALSE)
        {
            Hop_minifizer_helper::log(lang('config_settings_using_defaults'), 3);

            $this->location = 'default';

            // start with an empty array
            $settings = array();
        }

        // Legacy check: combine= ?
        if (array_key_exists('combine', $settings))
        {
            $settings['combine_css'] = $settings['combine'];
            $settings['combine_js'] = $settings['combine'];
            unset($settings['combine']);
        }

        // Legacy check: minify= ?
        if(array_key_exists('minify', $settings))
        {
            $settings['minify_css'] = $settings['minify'];
            $settings['minify_js'] = $settings['minify'];
            unset($settings['minify']);
        }

        // Default cache_path?
        if ( ! array_key_exists('cache_path', $settings) || $settings['cache_path'] == '')
        {
            // use global FCPATH if nothing set
            $settings['cache_path'] = FCPATH . '/cache';
        }

        // Default cache_url?
        if ( ! array_key_exists('cache_url', $settings) || $settings['cache_url'] == '')
        {
            // use config base_url if nothing set
            $settings['cache_url'] = ee()->config->item('base_url') . '/cache';
        }

        // Default base_path?
        if ( ! array_key_exists('base_path', $settings) || $settings['base_path'] == '')
        {
            // use global FCPATH if nothing set
            $settings['base_path'] = FCPATH;
        }

        // Default base_url?
        if ( ! array_key_exists('base_url', $settings) || $settings['base_url'] == '')
        {
            // use config base_url if nothing set
            $settings['base_url'] = ee()->config->item('base_url');
        }

        //Now make a complete & sanitised settings array, and set as our default
        $this->_default = $this->sanitise_settings(array_merge($this->_allowed, $settings));

        // cleanup
        unset($settings);

        /*
         * See if we need to inject ourselves into extensions hook.
         * This allows us to bind to the template_post_parse hook without installing our extension
         */
        if (ee()->config->item('allow_extensions') == 'y' &&  ! isset(ee()->extensions->extensions['template_post_parse'][10]['Hop_minifizer_ext']))
        {
            // Taken from EE_Extensions::__construct(), around line 70 in system/expressionengine/libraries/Extensions.php
            ee()->extensions->extensions['template_post_parse'][10]['Hop_minifizer_ext'] = ['template_post_parse', '', HOP_MINIFIZER_VERSION];
            ee()->extensions->extensions['ee_debug_toolbar_add_panel'][10]['Hop_minifizer_ext'] = ['ee_debug_toolbar_add_panel', '', HOP_MINIFIZER_VERSION];
            ee()->extensions->version_numbers['Hop_minifizer_ext'] = HOP_MINIFIZER_VERSION;

            Hop_minifizer_helper::log(lang('config_extension_manually_inject'), 3);
        }

        Hop_minifizer_helper::log(sprintf(lang('config_settings_saved'), $this->location), 3);

        // chaining
        return $this;

    }
    // ------------------------------------------------------


    /**
     * Utility method
     *
     * Usage: if ($Hop_minifizer_config->is_no('disable')) {...}
     */
    public function is_no($setting)
    {
        return ($this->$setting == 'no') ? TRUE : FALSE;
    }
    // ------------------------------------------------------


    /**
     * Utility method
     *
     * Usage: if ($Hop_minifizer_config->is_yes('disable')) {...}
     */
    public function is_yes($setting)
    {
        return ($this->$setting == 'yes') ? TRUE : FALSE;
    }
    // ------------------------------------------------------


    /**
     * Reset runtime settings to empty array
     * Same as doing $Hop_minifizer_config->settings = array();
     *
     * @return  Object  $this
     */
    public function reset()
    {
        $this->_runtime = array();

        // chaining
        return $this;
    }
    // ------------------------------------------------------


    /**
     * Sanitise an array of settings
     *
     * @param   array   Array of possible settings
     * @return  array   Sanitised array
     */
    public function sanitise_settings($settings)
    {
        if ( ! is_array($settings)) {
            Hop_minifizer_helper::log(lang('config_sanitise_non_array'), 2);
            return array();
        }

        // reduce our $settings array to only valid keys
        $valid = array_flip(array_intersect(array_keys($this->_allowed), array_keys($settings)));

        foreach($valid as $setting => $value)
        {
            $valid[$setting] = $this->sanitise_setting($setting, $settings[$setting]);
        }

        return $valid;
    }
    // ------------------------------------------------------


    /**
     * Sanitise an individual setting
     *
     * @param   string  Name of setting
     * @param   string  potential value of setting
     * @return  string  Sanitised setting
     */
    public function sanitise_setting($setting, $value)
    {
        switch($setting) :

            /* Booleans default NO */
            case('cleanup') :
            case('disable') :
            case('minify_html') :
            case('save_gz') :
                return ($value === TRUE OR preg_match('/1|true|on|yes|y/i', $value)) ? 'yes' : 'no';
            break;

            /* Booleans default YES */
            case('combine_css') :
            case('combine_js') :
            case('css_prepend_mode') :
            case('minify_css') :
            case('minify_js') :
                return ($value === FALSE OR preg_match('/0|false|off|no|n/i', $value)) ? 'no' : 'yes';
            break;

            /* ENUM */
            case('hash_method') :
                return preg_match('/sha1|md5|sanitize|sanitise/i', $value) ? $value : 'sha1';
            break;

            case('remote_mode') :
                return preg_match('/auto|curl|fgc/i', $value) ? $value : 'auto';
            break;

            case('js_library') :
                return preg_match('/jsmin|jsminplus/i', $value) ? $value : 'jsmin';
            break;

            case('css_library') :
                return preg_match('/cssmin|minify/i', $value) ? $value : 'minify';
            break;

            case('minify_html_hook') :
                return preg_match('/template_post_parse|ce_cache_pre_save/i', $value) ? $value : 'template_post_parse';
            break;

            /* String - Paths */
            case('cache_path') :
            case('base_path') :
                return rtrim(Hop_minifizer_helper::remove_double_slashes($value), '/');
            break;

            /* String - URLs */
            case('cache_url') :
            case('base_url') :
            case('css_prepend_url') :
                return rtrim(Hop_minifizer_helper::remove_double_slashes($value, TRUE), '/');
            break;

            /* Default */
            default :
                return $value;
            break;

        endswitch;
    }
    // ------------------------------------------------------


    /**
     * Return array of all settings at runtime
     */
    public function to_array()
    {
        // merge with defaults first
        return array_merge($this->_default, $this->_runtime);
    }
    // ------------------------------------------------------


    /**
     * Look for settings in EE's config object
     */
    protected function _from_config()
    {
        $settings = FALSE;

        // check if Hop_minifizer is being set via config
        if (ee()->config->item('hop_minifizer'))
        {
            $settings = ee()->config->item('hop_minifizer');

            // better be an array!
            if (is_array($settings) && count($settings) > 0)
            {
                $this->location = 'config';

                Hop_minifizer_helper::log(lang('config_settings_from_config'), 3);
            }
            else
            {
                $settings = FALSE;

                Hop_minifizer_helper::log(lang('config_settings_config_array_empty'), 2);
            }
        }
        else
        {
            Hop_minifizer_helper::log(lang('config_settings_config_not_found'), 3);
        }

        return $settings;
    }
    // ------------------------------------------------------


    /**
     * See if person forgot to change config setup when upgrading from 1.x.
     */
    protected function _from_config_legacy()
    {
        $settings = array();

        // loop through entire config
        foreach(ee()->config->config as $key => $val)
        {
            if(strpos($key, 'hop_minifizer_') === 0)
            {
                $settings[substr($key, 8)] = $val;
            }
        }

        if (count($settings) > 0)
        {
            $this->location = 'config';

            Hop_minifizer_helper::log(lang('config_settings_legacy_warning'), 2);
            Hop_minifizer_helper::log('', 3);
        }
        else
        {
            $settings = FALSE;

            Hop_minifizer_helper::log(lang('config_settings_from_legacy'), 3);
        }

        return $settings;
    }
    // ------------------------------------------------------


    /**
     * Look for settings in database (set by our extension)
     *
     * @return void
     */
    protected function _from_db()
    {
        $settings = FALSE;

        if (ee()->config->item('allow_extensions') == 'y')
        {
            $query = ee()->db
                        ->select('settings')
                        ->from('extensions')
                        ->where(array( 'enabled' => 'y', 'class' => 'Hop_minifizer_ext' ))
                        ->limit(1)
                        ->get();

            if ($query->num_rows() > 0)
            {
                $settings = unserialize($query->row()->settings);

                $this->location = 'db';

                Hop_minifizer_helper::log(lang('config_settings_from_db'), 3);
            }
            else
            {
                Hop_minifizer_helper::log(lang('config_settings_db_not_found'), 3);
            }

            $query->free_result();

        }

        return $settings;
    }
    // ------------------------------------------------------


    /**
     * Allow 3rd parties to provide own configuration settings
     *
     * @return mixed    array of settings of FALSE
     */
    protected function _from_hook()
    {
        $settings = FALSE;

        if (ee()->extensions->active_hook('hop_minifizer_get_settings'))
        {
            // Must return FALSE or array()
            $settings = ee()->extensions->call('hop_minifizer_get_settings', $this);

            // Technically the hook has an opportunity to set location to whatever it wishes;
            // so only set to 'hook' if still false
            if (is_array($settings) && $this->location === FALSE)
            {
                $this->location = 'hook';
            }
        }

        return $settings;
    }
    // ------------------------------------------------------


    /**
     * See if person forgot to change config setup when upgrading from 1.x.
     */
    protected function _from_global_vars_legacy()
    {
        $settings = array();

        // loop through entire _global_vars array
        foreach(ee()->config->_global_vars as $key => $val)
        {
            if(strpos($key, 'hop_minifizer_') === 0)
            {
                $settings[substr($key, 8)] = $val;
            }
        }

        if (count($settings) > 0)
        {
            $this->location = 'global_vars';

            Hop_minifizer_helper::log(lang('config_settings_legacy_global_var_warning'), 2);
            Hop_minifizer_helper::log(lang('config_settings_from_legacy'), 3);
        }
        else
        {
            $settings = FALSE;

            Hop_minifizer_helper::log(lang('config_settings_legacy_not_found'), 3);
        }

        return $settings;
    }
    // ------------------------------------------------------
}