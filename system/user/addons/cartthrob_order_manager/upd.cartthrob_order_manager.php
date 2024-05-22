<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cartthrob_order_manager_upd
{
    public $module_name = 'cartthrob_order_manager';

    public $version;
    public $current;
    public $notification_events = [
        // "order_updated",
        // "order_completed",
        // "order_refunded",
        'tracking_added_to_order',
    ];

    private $mcp_actions = [
        'refund',
        'add_tracking_to_order',
        'delete_order',
        'update_order',
        'resend_email',
        'create_new_report',
        'run_report',
        'remove_report',
    ];

    private $hooks = [
        [
            'method' => 'cartthrob_boot',
            'hook' => 'cartthrob_boot',
            'settings' => '',
            'priority' => 10,
            'enabled' => 'y',
        ],
        [
            'method' => 'cp_menu_array',
            'hook' => 'cp_menu_array',
            'settings' => '',
            'priority' => 10,
            'enabled' => 'y',
        ],
    ];

    private $tables = [
        'cartthrob_order_manager_settings' => [
            'id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
                'primary_key' => true,
            ],
            'site_id' => [
                'type' => 'int',
                'constraint' => 4,
                'default' => '1',
            ],
            '`key`' => [
                'type' => 'varchar',
                'constraint' => 255,
            ],
            'value' => [
                'type' => 'text',
                'null' => true,
            ],
            'serialized' => [
                'type' => 'int',
                'constraint' => 1,
                'null' => true,
            ],
        ],
        'cartthrob_order_manager_table' => [
            'id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
                'primary_key' => true,
            ],
            'member_id' => [
                'type' => 'int',
                'constraint' => 10,
                'null' => true,
            ],
            'track_event' => [
                'type' => 'int',
                'constraint' => 10,
                'null' => true,
            ],
        ],
        'cartthrob_order_manager_reports' => [
            'id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
                'primary_key' => true,
            ],
            'report_title' => [
                'type' => 'varchar',
                'default' => 'Order Report',
                'constraint' => 255,
            ],
            'type' => [
                'type' => 'varchar',
                'defalt' => 'order',
                'constraint' => 32,
            ],
            'settings' => [
                'type' => 'text',
                'null' => true,
            ],
        ],
    ];

    /**
     * Cartthrob_order_manager_upd constructor.
     */
    public function __construct()
    {
        $config = ee('Addon')->get('cartthrob_order_manager');
        $this->version = $config->get('version');

        ee()->load->add_package_path(PATH_THIRD . 'cartthrob/');
        ee()->load->add_package_path(PATH_THIRD . 'cartthrob_order_manager/');
    }

    /**
     * @return mixed
     */
    public function install()
    {
        $data = [
            'module_name' => ucwords($this->module_name),
            'module_version' => $this->version,
            'has_cp_backend' => 'y',
            'has_publish_fields' => 'n',
        ];
        ee()->db->insert('modules', $data);

        ee()->load->dbforge();
        ee()->load->model('table_model');
        ee()->table_model->update_tables($this->tables);

        $existing_notifications = [];

        if (ee()->db->table_exists('cartthrob_notification_events')) {
            ee()->db->select('notification_event')
                ->like('application', ucwords($this->module_name), 'after');

            $query = ee()->db->get('cartthrob_notification_events');

            if ($query->result() && $query->num_rows() > 0) {
                foreach ($query->result() as $row) {
                    $existing_notifications[] = $row->notification_event;
                }
            }

            foreach ($this->notification_events as $event) {
                if (!empty($event)) {
                    if (!in_array($event, $existing_notifications)) {
                        ee()->db->insert(
                            'cartthrob_notification_events',
                            [
                                'application' => ucwords($this->module_name),
                                'notification_event' => $event,
                            ]
                        );
                    }
                }
            }
        }

        ee()->db->select('method')
            ->from('extensions')
            ->like('class', ucwords($this->module_name), 'after');

        $existing_extensions = [];

        foreach (ee()->db->get()->result() as $row) {
            $existing_extensions[] = $row->method;
        }

        foreach ($this->hooks as $hook) {
            $hook = array_merge(
                [
                    'class' => ucwords($this->module_name) . '_ext',
                    'version' => $this->version,
                ],
                $hook
            );

            if (!in_array($hook['method'], $existing_extensions)) {
                ee()->db->insert('extensions', $hook);
            }
        }

        ee()->db->select('method')
            ->from('actions')
            ->like('class', ucwords($this->module_name), 'after');

        $existing_methods = [];

        foreach (ee()->db->get()->result() as $row) {
            $existing_methods[] = $row->method;
        }

        foreach ($this->mcp_actions as $action) {
            if (!in_array($action, $existing_methods)) {
                ee()->db->insert('actions',
                    [
                        'class' => ucwords($this->module_name) . '_mcp',
                        'method' => $action,
                    ]
                );
            }
        }

        return true;
    }

    /**
     * @param string $current
     * @return bool
     */
    public function update($current = '')
    {
        $this->current = $current;

        if ($this->current == $this->version) {
            return false;
        }

        $this->version_5_5_0();
        $this->version_6_0_0();
        $this->version_7_2_1();

        ee()->db->where('class', __CLASS__);
        ee()->db->update('extensions', ['version' => $this->version]);
    }

    /**
     * @return mixed
     */
    public function uninstall()
    {
        ee()->load->dbforge();

        ee()->db->delete('modules', ['module_name' => 'Cartthrob_order_manager']);

        ee()->db->like('class', 'Cartthrob_order_manager', 'after')->delete('actions');

        ee()->db->delete('extensions', ['class' => 'Cartthrob_order_manager_ext']);

        if (ee()->db->table_exists('cartthrob_notification_events')) {
            ee()->dbforge->drop_table('cartthrob_notification_events');
        }

        return true;
    }

    /**
     * Version 5.5.0 Update
     */
    private function version_5_5_0()
    {
        if (version_compare($this->current, '5.5.0', '>=')) {
            return;
        }

        ee()->load->dbforge();

        // Remove legacy cartthrob_products table
        if (ee()->db->table_exists('cartthrob_products')) {
            ee()->dbforge->drop_table('cartthrob_products');
        }

        // Add primary key to cartthrob_order_manager_settings table
        if (!ee()->db->field_exists('id', 'cartthrob_order_manager_settings')) {
            $table = ee()->db->dbprefix('cartthrob_order_manager_settings');

            ee()->db->query("ALTER TABLE {$table} ADD id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
        }

        // Update settings table to use serialized composite data type
        if (ee()->db->field_exists('serialized', 'cartthrob_order_manager_settings')) {
            $settings = ee()->db->select('*')->from('cartthrob_order_manager_settings')->get();

            if ($settings->num_rows() > 0) {
                foreach ($settings->result() as $setting) {
                    if ($setting->serialized == 0) {
                        $data = [
                            'value' => serialize($setting->value),
                        ];

                        ee()->db->update('cartthrob_order_manager_settings', $data, ['id' => $setting->id]);
                    }
                }
            }

            ee()->dbforge->drop_column('cartthrob_order_manager_settings', 'serialized');
        }

        // Remove cp_menu_array extension hook call. Extension is no longer available.
        ee()->db->delete('extensions',
            [
                'class' => $this->module_name,
                'hook' => 'cp_menu_array',
                'method' => 'cp_menu_array',
            ]
        );

        // Move CartThrob reports to Order Manager
        ee()->load->model('cartthrob_settings_model');
        $settings = ee()->cartthrob_settings_model->get_settings();
        $oldReports = !is_array($settings['reports']) ? unserialize($settings['reports']) : $settings['reports'];

        $omReports = ee('Model')->get('cartthrob_order_manager:Setting')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('key', 'reports')
            ->first();

        if ($omReports) {
            if (is_array($omReports->value)) {
                $data = array_merge(
                    $omReports->value,
                    $oldReports
                );
            } else {
                $omReportsValue = unserialize($omReports->value);
                $data = array_merge(
                    $omReportsValue,
                    $oldReports
                );
            }

            $omReports->value = $data;
            $omReports->save();
        } else {
            $omReports = ee('Model')->make(
                'cartthrob_order_manager:Setting',
                [
                    'site_id' => ee()->config->item('site_id'),
                    'key' => 'reports',
                    'value' => $oldReports,
                ]
            );
        }
    }

    /**
     * Version 6.0 update
     * @return bool
     */
    private function version_6_0_0()
    {
        if (version_compare($this->current, '6.0.0', '>=')) {
            return;
        }
    }

    /**
     * Version 6.0 update
     * @return bool
     */
    private function version_7_2_1()
    {
        if (version_compare($this->current, '7.2.1', '>=')) {
            return;
        }

        ee()->db->delete('extensions', ['class' => 'Cartthrob_order_manager_ext']);
        foreach ($this->hooks as $hook) {
            $hook = array_merge(
                [
                    'class' => ucwords($this->module_name) . '_ext',
                    'version' => $this->version,
                ],
                $hook
            );
            ee()->db->insert('extensions', $hook);
        }
    }
}
