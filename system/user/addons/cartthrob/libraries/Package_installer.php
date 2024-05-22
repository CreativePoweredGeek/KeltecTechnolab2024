<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

if (!class_exists(basename(__FILE__, '.php'))) {
    class Package_installer
    {
        private $packages = [];
        private $packageTypes = ['channel', 'template_group'];
        private $errors = [];
        private $installed = [];
        private $templatePath;
        private $fieldOrder = 1;
        private $configXmlPath;

        /**
         * Package_installer constructor.
         * @param array $params
         */
        public function __construct($params = [])
        {
            if (isset($params['xml'])) {
                $this->loadConfig($params['xml']);
            }
        }

        /**
         * @param $rowId
         */
        public function removePackage($rowId)
        {
            unset($this->packages[$rowId]);
        }

        /**
         * @param $package
         */
        public function addPackage($package)
        {
            if (!$package) {
                return;
            }

            if (is_array($package)) {
                $this->packages = array_merge($this->packages, $package);
            } else {
                $this->packages[] = $package;
            }
        }

        /**
         * @return array
         */
        public function packages()
        {
            return $this->packages;
        }

        /**
         * Clean up XML attributes before parsing
         *
         * @param $data
         * @return mixed
         */
        private function cleanData($data)
        {
            return str_replace(['\n', '\r'], ["\n", "\r"], $data);
        }

        /**
         * Remove data from array if the key is not a field in the specified table
         *
         * @param $tableName
         * @param array $data
         * @return array
         */
        private function cleanFields($tableName, array $data): array
        {
            $fields = ee()->db->list_fields($tableName);

            foreach ($data as $key => $value) {
                if (!in_array($key, $fields)) {
                    unset($data[$key]);
                }
            }

            return $data;
        }

        /**
         * Create a category from an XML object
         *
         * @param $category
         * @param $group_id
         * @return string|void
         */
        private function createCategory($category, $group_id)
        {
            if (count($category) > 1) {
                foreach ($category as $cat) {
                    $this->createCategory($cat, $group_id);
                }

                return;
            }

            $category_data = $this->getAttributes($category);
            $category_data['site_id'] = ee()->config->item('site_id');
            $category_data['group_id'] = $group_id;

            $original_name = $category_data['category_name'];

            $category_data['category_name'] = $this->rename('categories', $original_name, 'category_name',
                ['group_id' => $group_id]);

            if ($category_data['category_name'] === false) {
                return $this->logError('category_exists', $original_name);
            }

            $this->insert('categories', $category_data);

            $this->logInstall('category', $category_data['category_name']);
        }

        /**
         * Create a custom field group from an XML object
         * Note: Called dynamically
         *
         * @param $fieldGroup
         * @return int $group_id
         * @noinspection PhpUnusedPrivateMethodInspection
         */
        private function createFieldGroup($fieldGroup)
        {
            $field_group_data = $this->getAttributes($fieldGroup);
            $field_group_data['site_id'] = ee()->config->item('site_id');
            $original_name = $field_group_data['group_name'];

            if (($field_group_data['group_name'] = $this->rename('field_groups', $original_name, 'group_name')) === false) {
                return $this->logError('field_group_exists', $original_name);
            }

            $group_id = $this->insert('field_groups', $field_group_data);

            $this->logInstall('field_group', $field_group_data['group_name']);

            if (isset($fieldGroup->field)) {
                $this->fieldOrder = 1;

                foreach ($fieldGroup->field as $field) {
                    $this->createField($field, $group_id);
                }
            }

            return $group_id;
        }

        /**
         * @param $statuses
         */
        private function createStatuses($statuses)
        {
            $ids = [];

            foreach ($statuses->status as $status) {
                $status = ee('Model')->make('Status', $this->getAttributes($status));

                $status->save();

                $ids[] = $status->status_id;
            }

            return $ids;
        }

        /**
         * @param $fieldGroup
         * @param $channelId
         */
        private function createGroupFields($fieldGroup, $channelId)
        {
            $select_fields_by_group = ee()->db
                ->select('field_id')
                ->from('channel_field_groups_fields')
                ->where('group_id', $fieldGroup)
                ->get();

            if ($select_fields_by_group->num_rows() > 0) {
                foreach ($select_fields_by_group->result_array() as $selected_fields) {
                    $channels_channel_data['channel_id'] = $channelId;
                    $channels_channel_data['field_id'] = $selected_fields['field_id'];
                    $this->insert('channels_channel_fields', $channels_channel_data);
                }
            }
        }

        /**
         * Create a template from an XML object
         *
         * @param $template XML object
         * @param $groupId custom field group id
         * @param $groupData
         * @return bool|string
         */
        private function createTemplate($template, $groupId, $groupData)
        {
            $templateData = $this->getAttributes($template);
            $templateData['site_id'] = ee()->config->item('site_id');
            $templateData['group_id'] = $groupId;
            $ext = '.html';

            if (isset($templateData['template_type'])) {
                ee()->load->library('api');

                ee()->legacy_api->instantiate('template_structure');

                $ext = ee()->api_template_structure->file_extensions($templateData['template_type']);
            }

            $templateFile = $this->templatePath . $groupData['group_name'] . '.group' . DIRECTORY_SEPARATOR . $templateData['template_name'] . $ext;

            if ($this->templatePath && file_exists($templateFile)) {
                $templateData['template_data'] = file_get_contents($templateFile);
            } else {
                $templateData['template_data'] = trim((string)$template);
            }

            $templateData['edit_date'] = ee()->localize->now;

            if ($this->exists('templates', ['group_id' => $groupId, 'template_name' => $templateData['template_name']])) {
                return $this->logError('template_exists', $templateData['template_name']);
            }

            if ($templateData['template_name'] === false) {
                /* @todo $original_name is not set */
                return $this->logError('template_exists', $original_name);
            }

            if (!isset($templateData['protect_javascript'])) {
                $templateData['protect_javascript'] = 'n';
            }

            ee()->load->model('template_model');

            $templateData = $this->cleanFields('templates', $templateData);

            $template_model = ee('Model')->make('Template', $templateData);
            $template_model->group_id = $groupId;
            $template_model->Roles = ee('Model')->get('Role')->all();
            $template_model->save();

            $this->logInstall('template', $templateData['template_name']);
        }

        /**
         * Create a template group from an XML object
         *
         * @param $templateGroup
         * @return int $group_id
         */
        private function createTemplateGroup($templateGroup)
        {
            $templateGroupData = $this->getAttributes($templateGroup);

            if ($this->exists('template_groups', ['group_name' => $templateGroupData['group_name']])) {
                return $this->logError('template_group_exists', $templateGroupData['group_name']);
            }

            $templateGroupData['site_id'] = ee()->config->item('site_id');

            if (@$templateGroupData['is_site_default'] === 'y') {
                ee()->db->where('is_site_default', 'y');

                $templateGroupData['is_site_default'] = 'n';
            }

            $groupId = $this->insert('template_groups', $templateGroupData);

            $this->logInstall('template_group', $templateGroupData['group_name']);

            if (isset($templateGroup->template)) {
                foreach ($templateGroup->template as $template) {
                    $this->createTemplate($template, $groupId, $templateGroupData);
                }
            }
        }

        /**
         * @param $node
         * @return array
         */
        private function getAttributes($node)
        {
            $attr = [];

            foreach ($node->attributes() as $key => $value) {
                $attr[$key] = $this->cleanData($value);
            }

            return $attr;
        }

        /**
         * Create a custom field from an XML object
         *
         * @param stdClass $field XML object
         * @param int $group_id custom field group id
         * @return string
         */
        private function createField($field, $group_id)
        {
            ee()->load->dbforge();

            $fieldData = $this->getAttributes($field);

            $originalName = $fieldData['field_name'];

            $fieldData['site_id'] = ee()->config->item('site_id');
            $fieldData['field_name'] = $this->rename('channel_fields', $originalName, 'field_name');

            if ($fieldData['field_name'] === false) {
                return $this->logError('field_exists', $originalName);
            }

            $fieldGroupFields['group_id'] = $group_id;
            $fieldData['field_order'] = $this->fieldOrder++;
            $fieldId = $this->insert('channel_fields', $fieldData);
            $fieldGroupFields['field_id'] = $fieldId;

            $this->insert('channel_field_groups_fields', $fieldGroupFields);

            if ($fieldData['field_type'] === 'date') {
                $fields2 = [
                    'id' => ['type' => 'int', 'constraint' => '11', 'unsigned' => true, 'auto_increment' => true],
                    'entry_id' => ['type' => 'int', 'constraint' => '10', 'null' => false],
                    'field_id_' . $fieldId => [
                        'type' => 'int',
                        'constraint' => '10',
                        'null' => true,
                        'default' => '0',
                    ],
                    'field_dt_' . $fieldId => ['type' => 'varchar', 'constraint' => '50'],
                    'field_ft_' . $fieldId => ['type' => 'tinytext', 'null' => true],
                ];
            } elseif ($fieldData['field_type'] === 'rel') {
                $fields2 = [
                    'id' => ['type' => 'int', 'constraint' => '11', 'unsigned' => true, 'auto_increment' => true],
                    'entry_id' => ['type' => 'int', 'constraint' => '10', 'null' => false],
                    'field_id_' . $fieldId => ['type' => 'int', 'constraint' => '10', 'null' => true],
                    'field_ft_' . $fieldId => ['type' => 'tinytext', 'null' => true],
                ];
            } else {
                $fields2 = [
                    'id' => ['type' => 'int', 'constraint' => '11', 'unsigned' => true, 'auto_increment' => true],
                    'entry_id' => ['type' => 'int', 'constraint' => '10', 'null' => false],
                    'field_id_' . $fieldId => ['type' => 'text', 'constraint' => '255', 'null' => true],
                    'field_ft_' . $fieldId => ['type' => 'tinytext', 'null' => true],
                ];
            }

            ee()->dbforge->add_key('id', true);
            ee()->dbforge->add_field($fields2);
            ee()->dbforge->create_table('channel_data_field_' . $fieldId);
            ee()->db->query('ALTER TABLE ' . ee()->db->dbprefix . 'channel_data_field_' . $fieldId . ' ADD INDEX(`entry_id`)');
            unset($fields2);

            $this->logInstall('field', $fieldData['field_name']);
        }

        /**
         * Create a channel from an XML object
         *
         * @param $channel
         * @return string
         */
        private function createChannel($channel)
        {
            $channelData = $this->getAttributes($channel);

            if ($this->exists('channels', ['channel_name' => $channelData['channel_name']])) {
                return $this->logError('channel_exists', $channelData['channel_name']);
            }

            $catGroup = [];

            foreach (['field_group', 'cat_group'] as $child) {
                if (isset($channel->$child)) {
                    $methodName = 'create' . ucfirst(camelize($child));
                    $channelData[$child] = $this->{$methodName}($channel->$child);
                }
            }

            $channelData['cat_group'] = (isset($channelData['cat_group']) && !count($catGroup)) ? $channelData['cat_group'] : implode('|', $catGroup);
            $channelData['site_id'] = ee()->config->item('site_id');
            $channelData['channel_lang'] = ee()->config->item('xml_lang');
            $channelData['channel_encoding'] = ee()->config->item('charset');

            $channelId = $this->insert('channels', $channelData);

            $this->createGroupFields($channelData['field_group'], $channelId);

            // insert in the exp_channels_channel_field_groups to match the channel id with the field group
            $channelFieldGroup['channel_id'] = $channelId;
            $channelFieldGroup['group_id'] = $channelData['field_group'];

            $this->insert('channels_channel_field_groups', $channelFieldGroup);

            if (!empty($channelData['channel_member_groups'])) {
                if (strtolower($channelData['channel_member_groups']) === 'all') {
                    $query = ee('Model')->get('MemberGroup')->filter('group_id', '>=', '4')->all();

                    foreach ($query as $row) {
                        ee()->db->insert('channel_member_groups', ['group_id' => $row->group_id, 'channel_id' => $channelId]);
                    }

                    unset($query);
                } else {
                    foreach (explode('|', $channelData['channel_member_groups']) as $group_id) {
                        ee()->db->insert('channel_member_groups', ['group_id' => $group_id, 'channel_id' => $channelId]);
                    }
                }
            }

            // Create new statuses and attach to channel
            if (isset($channel->statuses)) {
                $status_ids = $this->createStatuses($channel->statuses);

                $statuses = ee('Model')->get('Status', $status_ids)->all();

                $channel = ee('Model')->get('Channel', $channelId)->first();

                $channel->Statuses = $statuses;
                $channel->save();
            }

            $this->logInstall('channel', $channelData['channel_name']);
        }

        /**
         * Check to see if a database record exists in the specified table
         * Will return the id if $id_field is specified
         *
         * @param string $table name of table to check
         * @param array $data key=>value pairs of which columns to check for match
         * @param string|bool $idField name of id column
         * @return bool|int $id_field
         */
        private function exists($table, $data, $idField = false)
        {
            if (ee()->db->field_exists('site_id', $table)) {
                $data['site_id'] = ee()->config->item('site_id');
            }

            ee()->db->select($idField ?: '*');
            ee()->db->where($data);

            $query = ee()->db->get($table);

            return $idField ? $query->row($idField) : (bool)$query->num_rows();
        }

        /**
         * Insert a record into the database
         *
         * @param string $table the database table name
         * @param array $data a keyed array of the data to insert
         * @return int $DB->insert_id
         */
        private function insert($table, $data)
        {
            $data = $this->cleanFields($table, $data);

            ee()->db->insert($table, $data);

            return ee()->db->insert_id();
        }

        /**
         * Log an error to be displayed on process
         *
         * @param string $error the error code
         * @return string $xml
         */
        private function logError($error)
        {
            $args = func_get_args();

            array_shift($args);

            $this->errors[] = vsprintf(lang('error_' . $error), $args);

            return false;
        }

        /**
         * @return array
         */
        public function errors()
        {
            return $this->errors;
        }

        /**
         * Logs a successful "Auto-Install" action
         *
         * @param string $type the type (channel, template, etc) installed
         */
        private function logInstall($type)
        {
            $args = func_get_args();

            array_shift($args);

            $this->installed[] = vsprintf(lang('installed_' . $type), $args);
        }

        /**
         * @return array
         */
        public function installed()
        {
            return $this->installed;
        }

        /**
         * If using flat files w/ templates, you can set the dir where they're stored
         *
         * @param $templatePath
         * @return $this
         */
        public function setTemplatePath($templatePath)
        {
            if (is_dir($templatePath)) {
                $this->templatePath = rtrim($templatePath, '/') . '/';
            }

            return $this;
        }

        /**
         * Parse through and install the submitted XML
         * @param bool $xml
         * @return array|string
         */
        private function parseXml($xml = false)
        {
            if (!function_exists('simplexml_load_string')) {
                return $this->logError('no_simplexml');
            }

            if (!$xml) {
                return $this->logError('blank_xml');
            }

            $xml = file_exists($xml) ? simplexml_load_file($xml) : simplexml_load_string($xml);

            if ($xml === false) {
                return $this->logError('xml_error');
            }

            $packages = [];

            foreach ($this->packageTypes as $type) {
                if (empty($xml->$type)) {
                    continue;
                }

                foreach ($xml->$type as $package) {
                    $packages[] = $package;
                }
            }

            return $packages;
        }

        /**
         * Parse through and install the submitted XML
         *
         * @param array $params additional parameters to be passed
         */
        public function install($params = [])
        {
            foreach ($this->packages as $package) {
                if (in_array($package->getName(), $this->packageTypes)) {
                    $methodName = 'create' . ucfirst(camelize($package->getName()));
                    $this->{$methodName}($package, $params);
                }
            }
        }

        public function installTemplates()
        {
            foreach ($this->packages as $package) {
                $this->createTemplateGroup($package);
            }
        }

        public function installChannels()
        {
            foreach ($this->packages as $package) {
                $this->createChannel($package);
            }
        }

        /**
         * Checks to see if a record exists for a certain name,
         * and if so, it will append an integer to the end of the
         * name in an attempt to generate a unique name.
         * If the set limit $this->rename_limit is reached it will
         * return FALSE.
         *
         * @param string $table the database table to check
         * @param string $name the name to check
         * @param string $field the name of the name database column
         * @param array $data additional data to check against
         * @param int $rename_limit
         * @return string|bool
         */
        private function rename($table, $name, $field, $data = [], $rename_limit = 25)
        {
            $original_name = $name;

            $count = '';

            do {
                $name = $original_name . $count;

                $count++;

                $exists = $this->exists($table, array_merge([$field => $name], $data));
            } while ($count < $rename_limit && $exists);

            return ($count == $rename_limit && $exists) ? false : $name;
        }

        /**
         * @param $xmlPath
         */
        public function loadConfig($xmlPath): void
        {
            $this->configXmlPath = $xmlPath;

            $this->reloadConfig();
        }

        /**
         * Reload the package installer xml
         */
        public function reloadConfig(): void
        {
            $this->packages = [];
            $this->addPackage(
                $this->parseXml($this->configXmlPath)
            );
        }
    }
}
