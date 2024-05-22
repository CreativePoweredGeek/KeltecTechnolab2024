<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cartthrob_settings_model extends CI_Model
{
    protected $default_settings = [];
    private $cache;

    public function __construct($params = [])
    {
        parent::__construct();

        include PATH_THIRD . 'cartthrob/config/config.php';

        /* @var array $config Loaded from config include() above */
        $this->default_settings = $config['cartthrob_default_settings'];

        if (!isset($params['settings'])) {
            $params['settings'] = $this->get_settings();
        }

        foreach ($params['settings'] as $key => $value) {
            $this->config->set_item('cartthrob:' . $key, $value);
        }
    }

    /**
     * get saved settings from the database and cache, and defaults where settings aren't defined
     *
     * @param null $site_id
     * @return array saved settings
     */
    public function &get_settings($site_id = null)
    {
        if (is_null($site_id)) {
            $site_id = $this->config->item('site_id');
        }

        if (isset($this->cache[$site_id])) {
            return $this->cache[$site_id];
        }

        $settings = $this->default_settings;

        // make sure the table exists first
        if ($this->db->table_exists('cartthrob_settings')) {
            $query = $this->db
                ->where('site_id', $site_id)
                ->get('cartthrob_settings');

            foreach ($query->result() as $row) {
                $data = $row->serialized ? @unserialize($row->value) : $row->value;
                $settings[$row->key] = $data;
            }

            $query->free_result();
        }

        $this->cache[$site_id] = $settings;

        return $this->cache[$site_id];
    }

    /**
     * Loads the settings into CI's config object
     *
     * @param null $settings
     */
    public function load_settings($settings = null)
    {
    }

    /**
     * Public access to the default settings
     *
     * @return array the default settings as defined in the default_settings property
     */
    public function default_settings()
    {
        return $this->default_settings;
    }

    /**
     * Sets both the cache (which is referred to by reference in Cartthrob_core_ee)
     * and the CI cache object's value (with a "cartthrob:" prefix)
     *
     * @param string $key
     * @param mixed $value
     */
    public function set_item($key, $value = false)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->cache[$this->config->item('site_id')][$k] = $v;
                $this->config->set_item('cartthrob:' . $k, $v);
            }
        } else {
            $this->cache[$this->config->item('site_id')][$key] = $value;
            $this->config->set_item('cartthrob:' . $key, $value);
        }
    }

    /**
     * @param $channel
     * @return bool
     */
    public function getStatusChannels($channel_id = null): array
    {
        if (is_null($channel_id)) {
            return [];
        }

        $channel = ee('Model')->get('Channel')
            ->fields('Channel.channel_id', 'Statuses.*')
            ->with('Statuses')
            ->filter('channel_id', $channel_id)
            ->order('Statuses.status_order', 'asc')
            ->all()
            ->first();

        $statuses = $channel->Statuses->toArray();

        return is_array($statuses) ? $statuses : [];
    }
}
