<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @property CI_Controller $EE
 */
class Cartthrob_upd
{
    public $module_name;
    public $version;
    public $current;
    private $mod_actions = [
        'delete_from_cart_action',
        'cart_action',
        'download_file_action',
        'add_to_cart_action',
        'update_cart_action',
        'add_coupon_action',
        'multi_add_to_cart_action',
        'update_live_rates_action',
        'delete_recurrent_billing_action',
        'update_recurrent_billing_action',
        'save_customer_info_action',
        'checkout_action',
        'payment_return_action',
        'update_subscription_action',
        'change_gateway_fields_action',
        'consume_async_job_action',
        'extload_action',
        'add_vault_action',
        'delete_vault_action',
        'update_vault_action',
    ];
    private $mcp_actions = [
        'save_price_modifier_presets_action',
        'email_test',
        'crontabulous_get_pending_subscriptions',
        'crontabulous_process_subscription',
        'configurator_ajax',
    ];
    /**
     * Tables
     *
     * List of custom tables to be used with table_model->update_tables() on install/update
     *
     * Notes about field attributes:
     * -use an int, not a string, for constraint
     * -use custom attributes, key, index and primary_key set to TRUE
     * -don't set null => false unneccessarily
     * -default values MUST be strings
     *
     * But really, use the console and run the table model table_to_array() method
     *
     * @var array
     */
    private $tables = [
        'cartthrob_sessions' => [
            'session_id' => [
                'type' => 'varchar',
                'constraint' => 32,
                'primary_key' => true,
            ],
            'cart_id' => [
                'type' => 'int',
                'constraint' => 10,
                'index' => true,
            ],
            'fingerprint' => [
                'type' => 'varchar',
                'constraint' => 40,
                'default' => '',
                'index' => true,
            ],
            'expires' => [
                'type' => 'int',
                'constraint' => 11,
                'default' => '0',
                'index' => true,
            ],
            'member_id' => [
                'type' => 'int',
                'constraint' => 10,
                'index' => true,
            ],
            'sess_key' => [
                'type' => 'varchar',
                'constraint' => 40,
                'default' => '',
            ],
            'sess_expiration' => [
                'type' => 'int',
                'constraint' => 11,
                'default' => 0,
            ],
        ],
        'cartthrob_settings' => [
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
        'cartthrob_cart' => [
            'id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
                'primary_key' => true,
            ],
            'cart' => [
                'type' => 'longtext',
                'null' => true,
            ],
            'timestamp' => [
                'type' => 'int',
                'default' => '0',
            ],
            'url' => [
                'type' => 'text',
                'null' => true,
            ],
        ],
        'cartthrob_permissions' => [
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
            'sub_id' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'order_id' => [
                'type' => 'int',
                'constraint' => 10,
                'null' => true,
            ],
            'item_id' => [
                'type' => 'int',
                'constraint' => 10,
                'null' => true,
            ],
            'permission' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
        ],
        'cartthrob_tax' => [
            'id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
                'primary_key' => true,
            ],
            'tax_name' => [
                'type' => 'text',
                'null' => true,
            ],
            'percent' => [
                'type' => 'varchar',
                'constraint' => 6,
                'null' => true,
            ],
            'shipping_is_taxable' => [
                'type' => 'tinyint',
                'constraint' => 1,
                'default' => '0',
            ],
            'special' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'state' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'zip' => [
                'type' => 'varchar',
                'constraint' => 10,
                'null' => true,
            ],
            'country' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
        ],
        'cartthrob_vault' => [
            'id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
                'primary_key' => true,
            ],
            'name' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'member_id' => [
                'type' => 'int',
                'constraint' => 10,
                'null' => true,
            ],
            'order_id' => [
                'type' => 'int',
                'constraint' => 10,
                'null' => true,
            ],
            'token' => [
                'type' => 'text',
                'null' => true,
            ],
            'gateway' => [
                'type' => 'varchar',
                'constraint' => 32,
                'null' => true,
            ],
            'customer_id' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ], // this is a value that may or may not be assigned by the merchant account provider.
            'last_four' => [
                'type' => 'varchar',
                'constraint' => 4,
                'null' => true,
            ],
            'exp_month' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'exp_year' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'first_name' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'last_name' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'address' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'address2' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'city' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'state' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'zip' => [
                'type' => 'varchar',
                'constraint' => 10,
                'null' => true,
            ],
            'country' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'shipping_first_name' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'shipping_last_name' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'shipping_address' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'shipping_address2' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'shipping_city' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'shipping_state' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'shipping_zip' => [
                'type' => 'varchar',
                'constraint' => 10,
                'null' => true,
            ],
            'shipping_country' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'primary' => [
                'type' => 'tinyint',
                'constraint' => 1,
                'default' => '0',
            ],
            'created_date' => [
                'type' => 'int',
                'contraint' => 11,
                'default' => '0',
            ],
            'modified' => [
                'type' => 'int',
                'contraint' => 11,
                'default' => '0',
            ],
            'sub_id' => false,
            'vault_id' => false,
            'timestamp' => false,
            'expires' => false,
            'status' => false,
            'description' => false,
            'total_occurrences' => false,
            'trial_occurrences' => false,
            'total_intervals' => false,
            'interval_units' => false,
            'allow_modification' => false,
            'price' => false,
            'trial_price' => false,
            'error_message' => false,
        ],
        'cartthrob_subscriptions' => [
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
            'order_id' => [
                'type' => 'int',
                'constraint' => 10,
                'null' => true,
            ],
            // A serialized version of the subscription item, used to create an order entry when the sub is rebilled
            'serialized_item' => [
                'type' => 'text',
                'null' => false,
            ],
            'vault_id' => [
                'type' => 'int',
                'constraint' => 10,
                'null' => true,
            ],
            // The created date
            'start_date' => [
                'type' => 'int',
                'contraint' => 11,
                'default' => '0',
            ],
            'modified' => [
                'type' => 'int',
                'contraint' => 11,
                'default' => '0',
            ],
            // The date this sub was last rebilled, used for determining whether a sub needs rebilling
            'last_bill_date' => [
                'type' => 'int',
                'constraint' => 11,
            ],
            // An explicit expiration date, in unix time
            'end_date' => [
                'type' => 'int',
                'constraint' => 11,
                'default' => '0',
            ],
            'status' => [
                'type' => 'varchar',
                'constraint' => 10,
                'default' => 'closed',
            ],
            'name' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => true,
            ],
            'description' => [
                'type' => 'text',
                'null' => true,
            ],
            // how many times to rebill
            'total_occurrences' => [
                'type' => 'int',
                'constraint' => 5,
            ],
            // how many times this has been rebilled
            'used_total_occurrences' => [
                'type' => 'int',
                'constraint' => 5,
            ],
            // how many times to rebill with trial price
            'trial_occurrences' => [
                'type' => 'int',
                'constraint' => 5,
            ],
            // how many times this trial has been rebilled
            'used_trial_occurrences' => [
                'type' => 'int',
                'constraint' => 5,
            ],
            // how many of each interval unit to wait between rebillings, ie. this would be "7" if
            // interval units was "days" and rebilling every 7 days
            'interval_length' => [
                'type' => 'int',
                'constraint' => 4,
            ],
            'trial_interval_length' => [
                'type' => 'int',
                'constraint' => 4,
            ],
            // days, weeks, or months
            'interval_units' => [
                'type' => 'varchar',
                'constraint' => 32,
                'null' => true,
            ],
            'trial_interval_units' => [
                'type' => 'varchar',
                'constraint' => 32,
                'null' => true,
            ],
            'allow_modification' => [
                'type' => 'tinyint',
                'constraint' => 1,
                'null' => true,
                'default' => '1',
            ],
            'price' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'trial_price' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'error_message' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            // this is a value that may or may not be assigned by the merchant account provider
            'sub_id' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'token' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'plan_id' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'rebill_attempts' => [
                'type' => 'int',
                'constraint' => 5,
            ],
            // the date this sub will be rebilled again, used for reference
            'next_bill_date' => [
                'type' => 'int',
                'constraint' => 11,
            ],
        ],
        // snapshot data
        'cartthrob_status' => [
            'id' => [
                'type' => 'int',
                'constraint' => 10,
                'primary_key' => true,
                'auto_increment' => true,
            ],
            'entry_id' => [
                'type' => 'int',
                'constraint' => 10,
                'index' => true,
            ],
            'session_id' => [
                'type' => 'varchar',
                'constraint' => 32,
                'null' => true,
                'index' => true,
            ],
            'status' => [
                'type' => 'varchar',
                'constraint' => 10,
                'default' => 'processing',
            ],
            'inventory_processed' => [
                'type' => 'int',
                'constraint' => 2,
                'default' => '0',
            ],
            'discounts_processed' => [
                'type' => 'int',
                'constraint' => 2,
                'default' => '0',
            ],
            'error_message' => [
                'type' => 'text',
            ],
            'transaction_id' => [
                'type' => 'varchar',
                'constraint' => 255,
            ],
            'cart' => [
                'type' => 'text',
                'null' => true,
            ],
            'cart_id' => [
                'type' => 'int',
                'constraint' => 10,
            ],
        ],
        'cartthrob_notification_events' => [
            // the name of the registering application
            'application' => [
                'type' => 'varchar',
                'constraint' => 255,
            ],
            // the event being added
            'notification_event' => [
                'type' => 'varchar',
                'constraint' => 255,
            ],
        ],
        'cartthrob_order_items' => [
            'row_id' => [
                'type' => 'int',
                'constraint' => 10,
                'auto_increment' => true,
                'primary_key' => true,
            ],
            'row_order' => [
                'type' => 'int',
                'constraint' => 10,
            ],
            'order_id' => [
                'type' => 'int',
                'constraint' => 10,
                'index' => true,
            ],
            'entry_id' => [
                'type' => 'int',
                'constraint' => 10,
                'null' => true,
                'index' => true,
            ],
            'title' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => true,
            ],
            'site_id' => [
                'type' => 'int',
                'constraint' => 10,
            ],
            'quantity' => [
                'type' => 'varchar',
                'constraint' => 10,
                'null' => true,
            ],
            'price' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'price_plus_tax' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'weight' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'shipping' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'no_tax' => [
                'type' => 'tinyint',
                'constraint' => 1,
                'null' => true,
                'default' => '0',
            ],
            'no_shipping' => [
                'type' => 'tinyint',
                'constraint' => 1,
                'null' => true,
                'default' => '0',
            ],
            'extra' => [
                'type' => 'text',
                'null' => true,
            ],
            'entry_date' => [
                'type' => 'int',
                'contraint' => 11,
                'default' => '0',
            ],
        ],
        'cartthrob_email_log' => [
            'id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
                'primary_key' => true,
            ],
            'from' => [
                'type' => 'text',
                'null' => true,
            ],
            'from_name' => [
                'type' => 'text',
                'null' => true,
            ],
            'to' => [
                'type' => 'text',
                'null' => true,
            ],
            'message_template' => [
                'type' => 'text',
                'null' => true,
            ],
            'subject' => [
                'type' => 'text',
                'null' => true,
            ],
            'email_event' => [
                'type' => 'text',
                'null' => true,
            ],
            'message' => [
                'type' => 'text',
                'null' => true,
            ],
            'send_date' => [
                'type' => 'int',
                'constraint' => 11,
                'default' => '0',
            ],
        ],
        'cartthrob_notification_log' => [
            'id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
                'primary_key' => true,
            ],
            'title' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => true,
            ],
            'event' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => true,
            ],
            'type' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => true,
            ],
            'template' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => true,
            ],
            'status_start' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => true,
            ],
            'status_end' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => true,
            ],
            'settings' => [
                'type' => 'text',
                'null' => true,
            ],
            'variables' => [
                'type' => 'text',
                'null' => true,
            ],
            'member_id' => [
                'type' => 'int',
                'constraint' => 10,
                'null' => true,
            ],
            'order_id' => [
                'type' => 'int',
                'constraint' => 10,
                'null' => true,
            ],
            'send_date' => [
                'type' => 'int',
                'constraint' => 11,
                'default' => '0',
            ],
        ],
        'cartthrob_async_jobs' => [
            'id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
                'primary_key' => true,
            ],
            'order_id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'null' => false,
            ],
            'state' => [
                'type' => 'text',
                'null' => false,
            ],
            'payload' => [
                'type' => 'mediumtext',
                'null' => false,
            ],
            'post' => [
                'type' => 'mediumtext',
                'null' => false,
            ],
            'failure_timestamp' => [
                'type' => 'int',
                'contraint' => 11,
                'default' => 0,
            ],
            'failure_count' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
                'null' => false,
            ],
            'failure_message' => [
                'type' => 'text',
                'null' => true,
            ],
        ],
        'cartthrob_idempotency' => [
            'id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
                'primary_key' => true,
            ],
            'guid' => [
                'type' => 'text',
                'null' => false,
            ],
            'member_id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
                'null' => false,
            ],
            'status' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => null,
                'null' => true,
            ],
            'return_path' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ],
            'payload' => [
                'type' => 'text',
                'null' => true,
            ],
            'create_date' => [
                'type' => 'int',
                'constraint' => 11,
                'default' => '0',
            ],
        ],
    ];
    private $fieldtypes = [
        'cartthrob_discount',
        'cartthrob_order_items',
        'cartthrob_package',
        'cartthrob_price_by_member_group',
        'cartthrob_price_modifiers',
        'cartthrob_price_modifiers_configurator',
        'cartthrob_price_quantity_thresholds',
        'cartthrob_price_simple',
    ];
    private $hooks = [
        ['member_member_logout'],
        ['member_member_login', 'member_member_login_multi', null, 1],
        ['member_member_login', 'member_member_login_single', null, 1],
        ['member_member_login', 'cp_member_login', null, 1],
        ['before_member_delete'],
        ['before_member_bulk_delete'],
        ['cp_menu_array', 'cp_menu_array'],
        ['cp_custom_menu', 'cp_custom_menu'],
        ['entry_submission_end', 'entry_submission_end'],
        ['publish_form_entry_data', 'publish_form_entry_data'],
        ['channel_form_submit_entry_start', 'channel_form_submit_entry_start'],
        ['channel_form_submit_entry_end', 'channel_form_submit_entry_end'],
        ['before_channel_entry_update', 'before_channel_entry_update'],
        ['after_channel_entry_update', 'after_channel_entry_update'],
        ['core_boot', 'core_boot'],
    ];
    private $sites = [];
    private $settings = [];

    public function __construct()
    {
        loadCartThrobPath();
        ee()->load->dbforge();

        $this->module_name = strtolower(str_replace(['_ext', '_mcp', '_upd'], '', __CLASS__));
        $this->version = CARTTHROB_VERSION;

        /*
         * Get Site IDs
         */
        $query = ee()->db->select('site_id')->get('sites');

        foreach ($query->result() as $row) {
            $this->sites[] = $row->site_id;
        }

        $query->free_result();

        /*
         * Get Settings
         */
        if (ee()->db->table_exists('cartthrob_settings')) {
            $query = ee()->db->get('cartthrob_settings');

            foreach ($query->result() as $row) {
                $this->settings[$row->site_id][$row->key] = $row->serialized ? @unserialize($row->value) : $row->value;
            }

            $query->free_result();
        }
    }

    public function install()
    {
        ee()->db->insert('modules', [
            'module_name' => 'Cartthrob',
            'module_version' => $this->version,
            'has_cp_backend' => 'y',
            'has_publish_fields' => 'n',
        ]);

        $this->sync();

        return true;
    }

    /**
     * Installs hooks and actions that aren't already installed and updates the tables
     *
     * @return bool
     */
    public function sync()
    {
        $existingModActions = [];
        $existingMcpActions = [];
        $existingHooks = [];

        $query = ee()->db->select('method')
            ->where('class', 'Cartthrob_ext')
            ->get('extensions');

        foreach ($query->result() as $row) {
            $existingHooks[] = $row->method;
        }

        // install extension
        foreach ($this->hooks as $row) {
            if (!in_array($row[0], $existingHooks)) {
                ee()->db->insert(
                    'extensions',
                    [
                        'class' => 'Cartthrob_ext',
                        'method' => $row[0],
                        'hook' => (!isset($row[1])) ? $row[0] : $row[1],
                        'settings' => (!isset($row[2])) ? '' : $row[2],
                        'priority' => (!isset($row[3])) ? 10 : $row[3],
                        'version' => $this->version,
                        'enabled' => 'y',
                    ]
                );
            }
        }

        ee()->db->update('extensions', ['version' => $this->version], ['class' => 'Cartthrob_ext']);

        // check for CartThrob actions in the database
        // so we don't get duplicates
        $query = ee()->db->select('method, class')
            ->where_in('class', ['Cartthrob', 'Cartthrob_mcp'])
            ->get('actions');

        foreach ($query->result() as $row) {
            if ($row->class === 'Cartthrob') {
                $existingModActions[] = $row->method;
            } else {
                $existingMcpActions[] = $row->method;
            }
        }

        ee()->load->model('table_model');

        ee()->table_model->update_tables($this->tables);

        // install the module actions from $this->mod_actions
        foreach ($this->mod_actions as $method) {
            if (!in_array($method, $existingModActions)) {
                ee()->db->insert('actions', ['class' => 'Cartthrob', 'method' => $method]);
            }
        }

        ee()->db->update('actions', ['csrf_exempt' => 1], ['class' => 'Cartthrob', 'method' => 'payment_return_action']);
        ee()->db->update('actions', ['csrf_exempt' => 1], ['class' => 'Cartthrob', 'method' => 'extload_action']);

        // install the module actions from $this->mcp_actions
        foreach ($this->mcp_actions as $method) {
            if (!in_array($method, $existingMcpActions)) {
                ee()->db->insert('actions', ['class' => 'Cartthrob_mcp', 'method' => $method]);
            }
        }

        $this->install_fieldtypes();

        return true;
    }

    /**
     * @return bool
     */
    protected function install_fieldtypes()
    {
        require_once APPPATH . 'fieldtypes/EE_Fieldtype.php';

        foreach ($this->fieldtypes as $fieldtype) {
            // check if already installed
            if (ee()->db->where('name', $fieldtype)->count_all_results('fieldtypes') > 0) {
                ee()->db->update('fieldtypes', ['version' => $this->version], ['name' => $fieldtype]);

                continue;
            }

            $class = ucwords($fieldtype . '_ft');
            $fieldTypeClassVars = get_class_vars($class);

            ee()->db->insert('fieldtypes', [
                'name' => $fieldtype,
                'version' => $fieldTypeClassVars['info']['version'],
                'settings' => base64_encode(serialize([])),
                'has_global_settings' => method_exists($class, 'display_global_settings') ? 'y' : 'n',
            ]);
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

        /** @var array $config */
        include PATH_THIRD . 'cartthrob/config/config.php';

        foreach ($this->sites as $site_id) {
            // added support for more detailed gateway code, don't want to screw up existing installs
            if ($this->olderVersion('2.2') || empty($settings[$site_id]['gateways_format'])) {
                $config['cartthrob_default_settings']['gateways_format'] = 'default';
            }

            $settings[$site_id] = isset($settings[$site_id])
                ? array_merge($config['cartthrob_default_settings'], $this->settings[$site_id])
                : $config['cartthrob_default_settings'];
        }

        $this->version_2_0271();
        $this->version_2_0318();
        $this->version_2_0323();
        $this->version_2_0325();
        $this->version_2_0378();
        $this->version_2_0387();
        $this->version_2_0400();
        $this->version_2_0413();
        $this->version_2_0433();
        $this->version_2_0512();
        $this->version_2_0517();
        $this->version_2_5();
        $this->version_2_502();
        $this->pre_ee_version_2_7_0();
        $this->version_4_6_0();
        $this->version_5_1_0();
        $this->version_5_1_1();
        $this->version_5_2_0();
        $this->version_5_3_2();
        $this->version_5_4_2();
        $this->version_5_5_0();
        $this->version_6_0_0();
        $this->version_8_0_0();

        return $this->sync();
    }

    /**
     * Test if $versionA is older than $versionB
     *
     * @param $versionA
     * @param string|null $versionB
     * @return bool
     */
    private function olderVersion($versionA, $versionB = null)
    {
        // if it only has one point, it's a beta version
        if (substr_count($versionA, '.') === 1 && substr_count($this->current, '.') === 2) {
            return false;
        }

        if (!$versionB) {
            $versionB = $this->current;
        }

        list($c1, $c2, $c3) = array_merge(explode('.', $versionB), [0, 0, 0]);
        list($v1, $v2, $v3) = array_merge(explode('.', $versionA), [0, 0, 0]);

        $c2 = str_pad($c2, 3, 0);
        $c3 = str_pad($c3, 3, 0);
        $v2 = str_pad($v2, 3, 0);
        $v3 = str_pad($v3, 3, 0);

        $current = $c1 . '.' . $c2 . '.' . $c3;
        $versionA = $v1 . '.' . $v2 . '.' . $v3;

        return strnatcasecmp($current, $versionA) < 0;
    }

    /**
     * Version 2.0271 Update
     * Remove the member_member_login hook
     * Update sessions database
     */
    private function version_2_0271()
    {
        if (!$this->olderVersion('2.0271')) {
            return;
        }
        ee()->db->delete('extensions', ['method' => 'member_member_login']);

        if (ee()->db->table_exists('cartthrob_sessions')) {
            foreach (['last_activity', 'ip_address', 'user_agent'] as $column) {
                if (ee()->db->field_exists($column, 'cartthrob_sessions')) {
                    ee()->dbforge->drop_column('cartthrob_sessions', $column);
                }
            }

            $fields = [
                'sess_key' => [
                    'type' => 'varchar',
                    'constraint' => 40,
                    'default' => '',
                ],
                'sess_expiration' => [
                    'type' => 'int',
                    'constraint' => 11,
                    'default' => 0,
                ],
            ];

            ee()->dbforge->add_column('cartthrob_sessions', $fields);
        }
    }

    /**
     * Version 2.0318 Update
     */
    private function version_2_0318()
    {
        if (!$this->olderVersion('2.0318')) {
            return;
        }

        ee()->dbforge->add_field($this->tables['cartthrob_order_items']);

        ee()->dbforge->add_key('row_id', true);

        ee()->dbforge->create_table('cartthrob_order_items', true);

        $fields = ee()->db->select('field_id, group_id')
            ->where('field_type', 'cartthrob_order_items')
            ->get('channel_fields')
            ->result();

        loadCartThrobPath();

        foreach ($fields as $field) {
            $entries = ee()->db->select('entry_id, field_id_' . $field->field_id)
                ->join('channels', 'channels.channel_id = channel_data.channel_id')
                ->where('field_group', $field->group_id)
                ->where('field_id_' . $field->field_id . ' !=', '')
                ->get('channel_data')
                ->result();

            foreach ($entries as $entry) {
                $data = _unserialize($entry->{'field_id_' . $field->field_id}, true);

                foreach ($data as $row_id => $row) {
                    $insert = [
                        'order_id' => $entry->entry_id,
                        'row_order' => $row_id,
                    ];

                    foreach (['entry_id', 'title', 'quantity', 'price'] as $key) {
                        $insert[$key] = (isset($row[$key])) ? $row[$key] : '';
                        unset($row[$key]);
                    }

                    $insert['extra'] = (count($row) > 0) ? base64_encode(serialize($row)) : '';

                    ee()->db->insert('cartthrob_order_items', $insert);
                }

                ee()->db->update('channel_data', ['field_id_' . $field->field_id => 1],
                    ['entry_id' => $entry->entry_id]);
            }
        }
    }

    /**
     * Version 2.0323 Update
     */
    private function version_2_0323()
    {
        if (!$this->olderVersion('2.0323')) {
            return;
        }

        $field = (ee()->db->field_exists('order_id', 'cartthrob_order_items')) ? 'order_id' : 'parent_id';

        $parents = ee()->db->select($field)
            ->distinct()
            ->get('cartthrob_order_items')
            ->result();

        $updated_channels = [];

        $order_items_fields = ee()->db->select('site_id, value')
            ->where('`key`', 'orders_items_field')
            ->get('cartthrob_settings')
            ->result();

        foreach ($parents as $parent) {
            $site_id = ee()->db->select('site_id')
                ->where('entry_id', $parent->{$field})
                ->get('channel_titles')
                ->row('site_id');

            foreach ($order_items_fields as $row) {
                if ($site_id == $row->site_id && $row->value) {
                    ee()->db->update('channel_data', ['field_id_' . $row->value => 1],
                        ['entry_id' => $parent->{$field}]);

                    break;
                }
            }
        }
    }

    /**
     * Version 2.0325 Update
     */
    private function version_2_0325()
    {
        if (!$this->olderVersion('2.0325')) {
            return;
        }

        if (ee()->db->field_exists('parent_id', 'cartthrob_order_items')) {
            ee()->dbforge->modify_column(
                'cartthrob_order_items',
                [
                    'parent_id' => [
                        'name' => 'order_id',
                        'type' => 'int',
                        'constraint' => 10,
                    ],
                ]
            );
        }
    }

    /**
     * Version 2.0378 Update
     */
    private function version_2_0378()
    {
        if (!$this->olderVersion('2.0378')) {
            return;
        }

        ee()->db->insert('extensions', [
            'class' => 'Cartthrob_ext',
            'method' => 'cp_menu_array',
            'hook' => 'cp_menu_array',
            'settings' => '',
            'priority' => 10,
            'version' => $this->version,
            'enabled' => 'y',
        ]);
    }

    /**
     * Version 2.0387 Update
     * Adding status (specifically for use in PayPal and other non real-time payment systems. Will allow us to check
     * existing status before sending notification)
     */
    private function version_2_0387()
    {
        if (!$this->olderVersion('2.0387')) {
            return;
        }

        $query = ee()->db->where('`key`', 'encrypted_sessions')->get('cartthrob_settings');

        foreach ($query->result_array() as $row) {
            unset($row['key']);

            $row['`key`'] = 'session_use_fingerprint';

            ee()->db->insert('cartthrob_settings', $row);
        }
    }

    /**
     * Version 2.0400 Update
     */
    private function version_2_0400()
    {
        if (!$this->olderVersion('2.0400')) {
            return;
        }

        foreach (['cp_member_login', 'member_member_login_single', 'member_member_login_multi'] as $hook) {
            ee()->db->insert(
                'extensions',
                [
                    'class' => 'Cartthrob_ext',
                    'method' => 'member_member_login',
                    'hook' => $hook,
                    'settings' => '',
                    'priority' => 10,
                    'version' => $this->version,
                    'enabled' => 'y',
                ]
            );
        }
    }

    /**
     * Version 2.0413 Update
     */
    private function version_2_0413()
    {
        if (!$this->olderVersion('2.0413')) {
            return;
        }

        ee()->db->update(
            'extensions',
            ['method' => 'sessions_end', 'hook' => 'sessions_end'],
            ['class' => 'Cartthrob_ext', 'hook' => 'sessions_start']
        );
    }

    /**
     * Version 2.0433 Update
     */
    private function version_2_0433()
    {
        if (!$this->olderVersion('2.0433')) {
            return;
        }

        ee()->db->delete('cartthrob_settings', ['`key`' => 'use_session_start_hook']);
        ee()->db->delete('extensions', ['class' => 'Cartthrob_ext', 'method' => 'sessions_end']);
        ee()->db->delete('extensions', ['class' => 'Cartthrob_ext', 'method' => 'sessions_start']);
    }

    /**
     * Version 2.0512 Update
     */
    private function version_2_0512()
    {
        if (!$this->olderVersion('2.0512')) {
            return;
        }

        ee()->load->model('table_model');

        foreach ($this->tables as $table_name => $fields) {
            ee()->table_model->update_table($table_name, $fields);

            if (!ee()->db->table_exists($table_name)) {
                continue;
            }

            $indexes = ee()->table_model->indexes($table_name);

            foreach ($fields as $field_name => $field) {
                if (!empty($field['index']) && !isset($indexes[$field_name])) {// don't create index if it already exists
                    ee()->table_model->create_index($table_name, $field_name, $field['index']);
                }
            }
        }
    }

    /**
     * Version 2.0517 Update
     * @return mixed
     */
    private function version_2_0517()
    {
        if (!$this->olderVersion('2.0517')) {
            return;
        }

        foreach ($this->sites as $siteId) {
            $lastOrderNumber = 0;

            if ($this->settings[$siteId]['orders_channel']) {
                $query = ee()->db->select('title')
                    ->from('channel_titles')
                    ->where('channel_id', $this->settings[$siteId]['orders_channel'])
                    ->where('site_id', $siteId)
                    ->like('title', $this->settings[$siteId]['orders_title_prefix'], 'after')
                    ->like('title', $this->settings[$siteId]['orders_title_suffix'], 'before')
                    ->order_by('entry_date', 'desc')
                    ->limit(1)
                    ->get();

                if ($query->num_rows()) {
                    $lastOrderNumber = str_replace([
                        $this->settings[$siteId]['orders_title_prefix'],
                        $this->settings[$siteId]['orders_title_suffix'],
                    ], '', $query->row('title'));
                }
            }

            ee()->db->insert('cartthrob_settings', [
                '`key`' => 'last_order_number',
                'value' => $lastOrderNumber,
                'site_id' => $siteId,
                'serialized' => 0,
            ]);
        }

        ee()->load->helper('array');

        $updatedSettings = [];

        foreach ($this->sites as $siteId) {
            $templates = [];
            $templateGroups = [];

            $query = ee()->db
                ->select('template_groups.group_id, group_name, template_name')
                ->where('template_groups.site_id', $siteId)
                ->join('template_groups', 'template_groups.group_id = templates.group_id')
                ->order_by('is_site_default', 'desc')
                ->get('templates');

            foreach ($query->result() as $row) {
                if (!array_key_exists($row->group_id, $templateGroups)) {
                    $templateGroups[$row->group_id] = $row->group_name;
                }

                $templates[] = $row->group_name . '/' . $row->template_name;
            }

            if (!$templates) {
                continue;
            }

            $groupId = $query->row('group_id');
            $groupName = $query->row('group_name');

            foreach ($templateGroups as $template_group_id => $template_group_name) {
                if ($template_group_name === 'cart') {
                    $groupId = $template_group_id;
                    $groupName = 'cart';
                    break;
                }
            }

            $emails = [
                'cart/email_customer' => [
                    'enabled' => 'send_confirmation_email',
                    'email_template' => 'email_order_confirmation',
                    'email_subject' => 'email_order_confirmation_subject',
                    'email_from_name' => 'email_order_confirmation_from_name',
                    'email_from' => 'email_order_confirmation_from',
                    'email_type' => 'email_order_confirmation_plaintext',
                ],
                'cart/email_admin' => [
                    'enabled' => 'send_email',
                    'email_template' => 'email_admin_notification',
                    'email_subject' => 'email_admin_notification_subject',
                    'email_from_name' => 'email_admin_notification_from_name',
                    'email_from' => 'email_admin_notification_from',
                    'email_type' => 'email_admin_notification_plaintext',
                ],
                'cart/email_low_stock' => [
                    'enabled' => 'send_inventory_email',
                    'email_template' => 'email_inventory_notification',
                    'email_subject' => 'email_inventory_notification_subject',
                    'email_from_name' => 'email_inventory_notification_from_name',
                    'email_from' => 'email_inventory_notification_from',
                    'email_type' => 'email_low_stock_notification_plaintext',
                ],
            ];

            $updatedSettings[$siteId]['notifications'] = $this->settings[$siteId]['notifications'];

            $i = 0;

            foreach ($emails as $templateName => $emailSettingsMap) {
                $enabled = element($emailSettingsMap['enabled'], $this->settings[$siteId]);

                if (!$enabled) {
                    unset($updatedSettings[$siteId]['notifications'][$i]);

                    continue;
                }

                foreach ($emailSettingsMap as $emailSettingName => $settingName) {
                    if ($emailSettingName !== 'email_template' && $emailSettingName !== 'enabled') {
                        $updatedSettings[$siteId]['notifications'][$i][$emailSettingName] = element($settingName, $this->settings[$siteId]);
                    }
                }

                // rename if already exists
                if (in_array($templateName, $templates)) {
                    $templateName .= '_custom';
                }

                $templateData = element($emailSettingsMap['email_template'], $this->settings[$siteId]);

                if ($templateData) {
                    if (preg_match('/{embed=([\042\047])?(.*?)\\1}/', $this->settings[$siteId][$emailSettingsMap['email_template']], $match)) {
                        $templateName = $match[2];
                    } else {
                        $parts = explode('/', $templateName);

                        // create the template
                        ee()->db->insert('templates', [
                            'site_id' => $siteId,
                            'group_id' => $groupId,
                            'template_name' => $parts[1],
                            'template_type' => 'webpage',
                            'template_data' => $templateData,
                            'template_notes' => '',
                            'last_author_id' => '1',
                            'cache' => 'n',
                            'refresh' => '0',
                            'no_auth_bounce' => '',
                            'enable_http_auth' => 'n',
                            'allow_php' => 'n',
                            'php_parse_location' => 'o',
                            'hits' => '0',
                        ]);

                        $templateName = $groupName . '/' . $parts[1];
                    }
                }

                $updatedSettings[$siteId]['notifications'][$i]['email_template'] = $templateName;

                $i++;
            }
        }

        if ($updatedSettings) {
            foreach ($updatedSettings as $siteId => $settings) {
                foreach ($settings as $key => $value) {
                    $data = [
                        '`key`' => $key,
                        'value' => $value,
                        'site_id' => $siteId,
                        'serialized' => 0,
                    ];

                    if (is_array($value)) {
                        $data['value'] = serialize($value);
                        $data['serialized'] = 1;
                    }

                    ee()->db->where(['`key`' => $key, 'site_id' => $siteId]);

                    if (ee()->db->count_all_results('cartthrob_settings') === 0) {
                        ee()->db->insert('cartthrob_settings', $data);
                    } else {
                        ee()->db->update('cartthrob_settings', [
                            'value' => $data['value'],
                            'serialized' => $data['serialized'],
                        ], [
                            '`key`' => $key,
                            'site_id' => $siteId,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Version 2.5 Update
     */
    private function version_2_5()
    {
        if (!$this->olderVersion('2.5')) {
            return;
        }

        $query = ee()->db->select()
            ->where('`key`', 'description')
            ->get('cartthrob_settings');

        if ($query->result() && $query->num_rows() > 0) {
            foreach ($this->sites as $siteId) {
                ee()->db->limit(1)->delete(
                    'cartthrob_settings',
                    ['`key`' => 'description', 'site_id' => $siteId]
                );
            }
        }
    }

    /**
     * Version 2.5.02 Update
     */
    private function version_2_502()
    {
        if (!$this->olderVersion('2.502')) {
            return;
        }

        if (ee()->db->table_exists('cartthrob_subscriptions')) {
            foreach (['token'] as $column) {
                if (ee()->db->field_exists($column, 'cartthrob_subscriptions')) {
                    ee()->dbforge->drop_column('cartthrob_subscriptions', $column);
                }
            }
        }
    }

    /**
     * Version 2.7.0 Update
     * Remove the safecracker_submit_entry_end & safecracker_submit_entry_start hook
     */
    private function pre_ee_version_2_7_0()
    {
        if ($this->olderVersion('2.7', APP_VER)) {
            return;
        }

        $query = ee()->db->select()
            ->where('class', 'Cartthrob_ext')
            ->where('method', 'safecracker_submit_entry_start')
            ->get('extensions');

        if ($query->result() && $query->num_rows() > 0) {
            ee()->db->limit(1)->delete(
                'extensions',
                ['class' => 'Cartthrob_ext', 'method' => 'safecracker_submit_entry_start']
            );
        }

        $query = ee()->db->select()
            ->where('class', 'Cartthrob_ext')
            ->where('method', 'safecracker_submit_entry_end')
            ->get('extensions');

        if ($query->result() && $query->num_rows() > 0) {
            ee()->db->limit(1)->delete(
                'extensions',
                ['class' => 'Cartthrob_ext', 'method' => 'safecracker_submit_entry_end']
            );
        }
    }

    /**
     * Version 4.6.0 Update
     * Remove unused `entry_submission_ready` ans `sessions_end` hook
     */
    private function version_4_6_0()
    {
        if (!$this->olderVersion('4.6')) {
            return;
        }

        ee()->db
            ->where('class', 'Cartthrob_ext')
            ->where('method', 'entry_submission_ready')
            ->delete('extensions');

        ee()->db
            ->where('class', 'Cartthrob_ext')
            ->where('method', 'sessions_end')
            ->delete('extensions');
    }

    /**
     * Version 5.1.0 Update
     * Remove unsupported category settings
     */
    private function version_5_1_0()
    {
        if (!$this->olderVersion('5.1.0')) {
            return;
        }

        $gatewaySettingsKeys = [
            'Cartthrob_anz_egate_settings', 'Cartthrob_authorize_net_settings', 'Cartthrob_authorize_net_sim_settings',
            'Cartthrob_beanstream_direct_settings', 'Cartthrob_thirdparty_nab_transact_settings',
            'Cartthrob_ct_pay_by_account_settings', 'Cartthrob_cardsave_server_settings',
            'Cartthrob_cartthrob_direct_settings', 'Cartthrob_quantum_settings', 'Cartthrob_dev_template_settings',
            'Cartthrob_echo_nvp_settings', 'Cartthrob_eway_settings', 'Cartthrob_linkpoint_settings',
            'Cartthrob_gocardless_settings', 'Cartthrob_moneris_direct_settings', 'Cartthrob_ogone_alias_settings',
            'Cartthrob_ogone_direct_settings', 'Cartthrob_ct_pay_by_check_settings',
            'Cartthrob_ct_pay_by_phone_settings', 'Cartthrob_paypal_pro_settings', 'Cartthrob_pivotal_direct_settings',
            'Cartthrob_thirdparty_psigate_settings', 'Cartthrob_worldpay_redirect_settings',
            'Cartthrob_realex_remote_settings', 'Cartthrob_sage_us_settings', 'Cartthrob_samurai_settings',
            'Cartthrob_ct_save_order_settings', 'Cartthrob_transaction_central_settings',
            'Cartthrob_cartthrob_direct_settings', 'Cartthrob_cardsave_server_settings',
            'Cartthrob_beanstream_direct_settings', 'Cartthrob_authorize_net_sim_settings',
            'Cartthrob_authorize_net_settings', 'Cartthrob_anz_egate_settings', 'Cartthrob_ct_offline_payments_settings',
            'Cartthrob_dummy_gateway_settings', 'Cartthrob_mollie_settings', 'Cartthrob_paypal_express_settings',
            'Cartthrob_sage_settings', 'Cartthrob_sage_s_settings', 'Cartthrob_stripe_settings',
        ];

        foreach ($gatewaySettingsKeys as $gatewayKey) {
            $className = substr($gatewayKey, 0, -9);

            if (class_exists($className)) {
                continue;
            }

            ee()->db
                ->where('key', $gatewayKey)
                ->delete('cartthrob_settings');
        }
    }

    /**
     * Version 5.1.1 Update
     * Remove set payment gateway and last edited gateway if not supported
     */
    private function version_5_1_1()
    {
        if (!$this->olderVersion('5.1.1')) {
            return;
        }

        // Clear bad payment gateway settings
        $query = ee()->db
            ->where('key', 'payment_gateway')
            ->get('cartthrob_settings');

        if ($query->num_rows() > 0) {
            foreach ($query->result() as $result) {
                $className = $result->value;

                if (class_exists($className)) {
                    continue;
                }

                ee()->db
                    ->where([
                        'key' => 'payment_gateway',
                        'site_id' => $result->site_id,
                    ])
                    ->delete('cartthrob_settings');
            }
        }

        // Clear bad last edited payment gateway settings
        $query = ee()->db
            ->where('key', 'last_edited_gateway')
            ->get('cartthrob_settings');

        if ($query->num_rows() > 0) {
            foreach ($query->result() as $result) {
                $className = $result->value;

                if (class_exists($className)) {
                    continue;
                }

                ee()->db
                    ->where([
                        'key' => 'last_edited_gateway',
                        'site_id' => $result->site_id,
                    ])
                    ->delete('cartthrob_settings');
            }
        }

        // Clear bad available gateways setting
        $query = ee()->db
            ->where('key', 'available_gateways')
            ->get('cartthrob_settings');

        if ($query->num_rows() > 0) {
            foreach ($query->result() as $result) {
                $gateways = unserialize($result->value);

                foreach ($gateways as $gateway => $ignored) {
                    if (class_exists($gateway)) {
                        continue;
                    }

                    unset($gateways[$gateway]);
                }

                ee()->db
                    ->where([
                        'key' => 'available_gateways',
                        'site_id' => $result->site_id,
                    ])
                    ->set('value', serialize($gateways))
                    ->update('cartthrob_settings');
            }
        }
    }

    /**
     * Version 5.2.0 Update
     */
    private function version_5_2_0()
    {
        if (!$this->olderVersion('5.2.0')) {
            return;
        }

        // Replace 'Round Up Extra Precision' with 'Round Up'
        ee()->db->update(
            'cartthrob_settings',
            ['value' => 'round_up'],
            [
                'key' => 'rounding_default',
                'value' => 'round_up_extra_precision',
            ]
        );

        // Add 'Round Nearest Value' setting
        ee()->db->insert('cartthrob_settings', ['key' => 'rounding_nearest_value', 'serialized' => 0]);

        // Replace 'Round - Swedish' with 'Round Nearest / 1.00'
        // Source: https://en.wikipedia.org/wiki/Cash_rounding
        $found = ee()->db->where(['key' => 'rounding_default', 'value' => 'swedish'])->count_all_results('cartthrob_settings');

        if ($found) {
            ee()->db->update(
                'cartthrob_settings',
                ['value' => 'round_nearest'],
                [
                    'key' => 'rounding_default',
                    'value' => 'swedish',
                ]
            );

            ee()->db->update(
                'cartthrob_settings',
                ['value' => '1.00'],
                ['key' => 'rounding_nearest_value']
            );
        }

        // Replace 'Round - New Zealand' with 'Round Nearest / 0.10'
        // Source: https://en.wikipedia.org/wiki/Cash_rounding
        $found = ee()->db->where(['key' => 'rounding_default', 'value' => 'new_zealand'])->count_all_results('cartthrob_settings');

        if ($found) {
            ee()->db->update(
                'cartthrob_settings',
                ['value' => 'round_nearest'],
                [
                    'key' => 'rounding_default',
                    'value' => 'new_zealand',
                ]
            );

            ee()->db->update(
                'cartthrob_settings',
                ['value' => '0.10'],
                ['key' => 'rounding_nearest_value']
            );
        }

        // Replace `consume_async_job` with `consume_async_job_action` in exp_actions
        ee()->db->update(
            'actions',
            ['method' => 'consume_async_job_action'],
            [
                'class' => 'Cartthrob',
                'method' => 'consume_async_job',
            ]
        );

        // Delete `update_item_action`  in exp_actions
        ee()->db->delete('actions', ['method' => 'update_item_action']);
    }

    /**
     * Version 5.3.2 Update
     */
    private function version_5_3_2()
    {
        if (!$this->olderVersion('5.3.2')) {
            return;
        }

        /** @var array $config */
        include PATH_THIRD . 'cartthrob/config/config.php';

        // if the following required keys exist, then the user has setup authorize.net correctly
        $requiredKeys = ['api_transaction_key_test', 'api_public_client_key_test', 'api_transaction_key_live', 'api_public_client_key_live'];
        $existingSettingsMap = [
            'api_login' => 'api_login_id_live',
            'transaction_key' => 'api_transaction_key_live',
            'dev_api_login' => 'api_login_id_test',
            'dev_transaction_key' => 'api_transaction_key_test',
            'mode' => 'mode',
        ];

        foreach ($this->sites as $siteId) {
            $query = ee()->db->select('value')
                ->from('exp_cartthrob_settings')
                ->where('key', 'Cartthrob_authorize_net_settings')
                ->where('site_id', $siteId)
                ->limit(1)
                ->get();

            if ($query->num_rows()) {
                foreach ($query->result_array() as $row) {
                    $existingSettings = unserialize($row['value']);

                    if (count(array_intersect_key(array_flip($requiredKeys), $existingSettings)) !== count($requiredKeys)) {
                        ee()->db
                            ->where([
                                'key' => 'Cartthrob_authorize_net_settings',
                                'site_id' => $siteId,
                            ])
                            ->delete('cartthrob_settings');

                        $newSettings = $config['cartthrob_default_settings']['Cartthrob_authorize_net_settings'];

                        // if there are usable values from the old Authorize.Net settings, map them here
                        foreach ($existingSettingsMap as $key => $map) {
                            if (isset($existingSettings[$key])) {
                                $newSettings[$map] = $existingSettings[$key];
                            }
                        }

                        ee()->db->insert(
                            'cartthrob_settings',
                            [
                                '`key`' => 'Cartthrob_authorize_net_settings',
                                'value' => serialize($newSettings),
                                'site_id' => $siteId,
                                'serialized' => 1,
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * Version 5.4.2 Update
     */
    private function version_5_4_2()
    {
        if (!$this->olderVersion('5.4.2')) {
            return;
        }

        if (ee()->db->table_exists('cartthrob_status')) {
            if (ee()->db->field_exists('error_message', 'cartthrob_status')) {
                $tableName = ee()->db->dbprefix('cartthrob_status');
                ee()->db->query("ALTER TABLE $tableName MODIFY COLUMN error_message text COLLATE utf8mb4_unicode_ci DEFAULT NULL");
            }
        }
    }

    /**
     * Version 5.5.0 Database Updates
     */
    private function version_5_5_0()
    {
        if (!$this->olderVersion('5.5.0')) {
            return;
        }

        // Add primary key to cartthrob_notification_events table
        if (!ee()->db->field_exists('id', 'cartthrob_notification_events')) {
            $table = ee()->db->dbprefix('cartthrob_notification_events');

            ee()->db->query("ALTER TABLE {$table} ADD id INT NOT NULL AUTO_INCREMENT PRIMARY KEY");
        }

        // Add id key to cartthrob_status table
        if (!ee()->db->field_exists('id', 'cartthrob_status')) {
            $table = ee()->db->dbprefix('cartthrob_status');

            ee()->db->query("ALTER TABLE {$table} DROP PRIMARY KEY");
            ee()->db->query("ALTER TABLE {$table} ADD KEY entry_id (entry_id)");
            ee()->db->query("ALTER TABLE {$table} ADD id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
        }
    }

    private function version_6_0_0()
    {
        if (version_compare($this->current, '6.0.0', '>=')) {
            return;
        }
    }

    private function version_8_0_0()
    {
        if (version_compare($this->current, '8.0.0', '>=')) {
            return;
        }

        loadCartThrobPath();

        // update legacy emails and merge into notifications
        $notifications = ee('cartthrob:SettingsService')->get('cartthrob', 'notifications');
        if (is_array($notifications)) {
            foreach ($notifications as $key => $notification) {
                $notifications[$key]['type'] = 'email';
                $notifications[$key]['title'] = $notification['email_subject'];
                $notifications[$key]['template'] = $notification['email_template'];
                $notifications[$key]['event'] = $notification['email_event'];
                if ($notifications[$key]['event'] == '') {
                    $notifications[$key]['event'] = 'status_change';
                }
            }

            $settings['notifications'] = $notifications;

            // move log_email setting to log_notifications
            $settings['log_notifications'] = ee('cartthrob:SettingsService')->get('cartthrob', 'log_email');
            ee('cartthrob:SettingsService')->save('cartthrob', $settings);
        }
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        ee()->db->delete('modules', ['module_name' => 'Cartthrob']);
        ee()->db->like('class', 'Cartthrob', 'after')->delete('actions');
        ee()->db->delete('extensions', ['class' => 'Cartthrob_ext']);

        return true;
    }
}
