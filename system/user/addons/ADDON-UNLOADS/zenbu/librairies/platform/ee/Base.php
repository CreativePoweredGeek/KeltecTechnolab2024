<?php
namespace Zenbu\librairies\platform\ee;

use Zenbu\librairies\Settings as Settings;
use Zenbu\librairies\platform\ee\Lang;
use Zenbu\librairies\platform\ee\Session;
use Zenbu\librairies\platform\ee\Cache;
use Zenbu\librairies\platform\ee\Url;
use Zenbu\librairies\Fields;
use Zenbu\librairies\platform\ee\ArrayHelper;

class Base
{
	var $display_settings;
	var $general_settings;
	var $permissions;
	var $session;
	var $settings;
	var $user;

	public function __construct($child_class = FALSE)
	{
		$this->session = new Session();
		$this->user    = Session::user();
		$this->cache   = new Cache();
		$this->permission_keys = array(
            'can_admin',
            'can_copy_profile',
            'can_access_settings',
            'edit_replace',
            'can_view_group_searches',
            'can_admin_group_searches'
            );
        $this->general_settings_keys = array(
            'default_1st_filter',
            'default_limit',
            'default_sort',
            'default_order',
            'enable_hidden_field_search',
            'max_results_per_page'
            );
	}

	public function setFieldsObj($fields)
    {
        $this->fieldsObj = $fields;
    }

    public function getFieldsObj()
    {
        return $this->fieldsObj;
    }

    public function setAllFields($fields)
    {
        $this->allFields = $fields;
    }

    public function getAllFields()
    {
        if(isset($this->allFields))
        {
            return $this->allFields;
        }
    }

    public function setAllFieldsForSection($fields)
    {
        $this->allFieldsForSection = $fields;
    }

    public function getAllFieldsForSection()
    {
        if(isset($this->allFieldsForSection))
        {
            return $this->allFieldsForSection;
        }
    }

    public function setFieldtypes($fields)
    {
        $this->fieldtypes = $fields;
    }

    public function getFieldtypes()
    {
        return $this->fieldtypes;
    }

    public function setFieldSettings($fields)
    {
        $this->fieldSettings = $fields;
    }

    public function getFieldSettings()
    {
        return $this->fieldSettings;
    }

    public function setDefaultFields($fields)
    {
        $this->default_fields = $fields;
    }

    public function getDefaultFields()
    {
        return $this->default_fields;
    }

    public function setDisplaySettings($settings)
    {
        $this->display_settings = $settings;
    }

    public function getDisplaySettings()
    {
        return $this->display_settings;
    }

    public function setGeneralSettings($settings)
    {
        $this->general_settings = $settings;
    }

    public function getGeneralSettings()
    {
        return $this->general_settings;
    }

    public function setPermissions($settings)
    {
        $this->permissions = $settings;
    }

    public function getPermissions()
    {
        return $this->permissions;
    }

    public function setSectionsObj($sections)
    {
        $this->sectionsObj = $sections;
    }

    public function getSectionsObj()
    {
        return $this->sectionsObj;
    }

    public function setSections($sections)
    {
        $this->sections = $sections;
    }

    public function getSections()
    {
        return $this->sections;
    }

    public function setUploadPrefs($upload_prefs)
    {
        $this->upload_prefs = $upload_prefs;
    }

    public function getUploadPrefs()
    {
        return $this->upload_prefs;
    }

	public function init($init = '')
	{
        //
	}
}