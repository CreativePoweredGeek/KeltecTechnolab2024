<?php

use BoldMinded\DataGrab\Dependency\Litzinger\Basee\App;

class DataGrabStructure extends AbstractModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'structure';
    }

    public function getDisplayName(): string
    {
        return 'Structure';
    }

    public function displayConfiguration(Datagrab_model $DG, array $data = []): array
    {
        $options = [
            '' => 'Create or Update', // Default, also backwards compatible
            'create' => 'Create Only',
            'update' => 'Update Only',
        ];

        return [
            [
                form_label('Execution Time') . '<div class="datagrab_subtext">When should DataGrab perform Structure updates?</div>',
                form_dropdown($this->getName() .'[execute_on_action]', $options, $this->getSettingValue('execute_on_action'))
            ],
            [
                form_label('URL') . '<div class="datagrab_subtext">Leave blank to generate the Struture URL automatically</div>',
                form_dropdown($this->getName() .'[url]', $data['data_fields'], $this->getSettingValue('url'))
            ],
            [
                form_label('Template') . '<div class="datagrab_subtext">Leave blank to use the channel\'s default template</div>',
                form_dropdown($this->getName() .'[template]', $data['data_fields'], $this->getSettingValue('template'))
            ],
            [
                form_label('Parent Entry ID') . '<div class="datagrab_subtext">Leave blank to not set a parent Entry ID.</div>',
                form_dropdown($this->getName() .'[parent_id]', $data['data_fields'], $this->getSettingValue('parent_id'))
            ],
            [
                form_label('Parent Entry Title') . '<div class="datagrab_subtext">Leave blank to not set a parent Entry ID. If set this will override the Parent Entry ID setting, and if the value of this field matches an existing entry, that entry will be set to the parent of the imported item.</div>',
                form_dropdown($this->getName() .'[parent_title]', $data['data_fields'], $this->getSettingValue('parent_title'))
            ],
        ];
    }

    public function saveConfiguration(Datagrab_model $DG): array
    {
        $data = ee()->input->post($this->getName());

        return [
            'execute_on_action' => $data['execute_on_action'] ?? '',
            'url' => $data['url'] ?? '',
            'template' => $data['template'] ?? '',
            'parent_id' => $data['parent_id'] ?? '',
            'parent_title' => $data['parent_title'] ?? '',
        ];
    }

    public function handle(Datagrab_model $DG, array &$data = [], array $item = [], array $custom_fields = [], string $action = '')
    {
        $onAction = $this->getSettingValue('execute_on_action');

        // We have a specific execution time, and now is not the time.
        if ($onAction !== $action && $onAction !== '') {
            return;
        }

        if ($DG->db->table_exists('exp_structure_channels')) {
            // If the structure module tables exists, try and get template id
            $DG->db->select('template_id');
            $DG->db->from('exp_structure_channels');
            $DG->db->where('channel_id', $DG->channelDefaults['channel_id']);
            $DG->db->where('type !=', 'unmanaged');

            /** @var CI_DB_result $query */
            $query = $DG->db->get();

            if ($query->num_rows() > 0) {
                $row = $query->row_array();

                $data['cp_call'] = true;

                $parentTitle = $DG->dataType->get_item($item, $this->getSettingValue('parent_title'));
                $parentId = $DG->dataType->get_item($item, $this->getSettingValue('parent_id'));
                $templateId = $DG->dataType->get_item($item, $this->getSettingValue('template_id'));
                $url = $DG->dataType->get_item($item, $this->getSettingValue('url'));

                if ($templateId) {
                    $data['structure__template_id'] = $templateId;
                } else {
                    $data['structure__template_id'] = $row['template_id'];
                }

                if ($url) {
                    $data['structure__uri'] = ee('Format')
                        ->make('Text', $url)
                        ->urlSlug()
                        ->compile();
                } else {
                    $data['structure__uri'] = ee('Format')->make('Text', $data['title'])
                        ->urlSlug()
                        ->compile();
                }

                if ($parentTitle) {
                    $entry = ee('Model')->get('ChannelEntry')
                        ->filter('title', $parentTitle)
                        ->first();

                    if ($entry) {
                        $data['structure__parent_id'] = $entry->entry_id;
                    }
                } elseif ($parentId) {
                    $data['structure__parent_id'] = $parentId;
                } else {
                    $data['structure__parent_id'] = App::isGteEE7() ? '' : 0;
                }

                // Eek! Workaround Structure 'bug' that expects post data
                $_POST['channel_id'] = $DG->channelDefaults['channel_id'];
                $_POST['template_id'] = $data['structure__template_id'];
                $_POST['parent_id'] = $data['structure__parent_id'];

                // Structure uses config variable to get site_pages
                // This only gets updated on page load (ie, once at the start
                // of the import) so we have to keep updating it here...
                $DG->db->select('site_pages');
                $DG->db->where('site_id', $DG->config->item('site_id'));
                $query = $DG->db->get('sites');
                $site_pages = unserialize(base64_decode($query->row('site_pages')));
                $DG->config->config['site_pages'] = $site_pages;
            }
        }
    }
}
