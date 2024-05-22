<?php

namespace CartThrob\Forms\Settings;

use CartThrob\Exceptions\Forms\AbstractFormExceptions;
use CartThrob\Forms\AbstractForm;

class Import extends AbstractForm
{
    protected $rules = [
        'settings' => 'required|validateSettingsFile',
    ];

    /**
     * @var string
     */
    protected $export_url = '';

    /**
     * @param $url
     * @return AbstractForm
     */
    public function setExportUrl($url): AbstractForm
    {
        $this->export_url = $url;

        return $this;
    }

    /**
     * @param string $name
     * @param $value
     * @param $params
     * @param $object
     * @return bool|string
     */
    public function validateSettingsFile(string $name, $value, $params, $object)
    {
        $error_number = element('error', $value);
        if ($error_number != 0) {
            return 'no_upload';
        }

        $mime = element('type', $value);
        if ($mime != 'text/plain') {
            return 'not_plain_text';
        }

        $tmp_name = element('tmp_name', $value);
        $new_settings = read_file($tmp_name);
        if (!$new_settings) {
            return 'no_data';
        }

        $settings = _unserialize($new_settings);
        if (!is_array($settings)) {
            return 'bad_file_content';
        }

        return true;
    }

    /**
     * @return string
     * @throws AbstractFormExceptions
     */
    protected function getExportUrl(): string
    {
        if (!$this->export_url) {
            throw new AbstractFormExceptions("Export Settings URL isn't set");
        }

        return $this->export_url;
    }

    /**
     * @return \array[][]
     * @throws AbstractFormExceptions
     */
    public function generate(): array
    {
        $form = [
            [
                'title' => 'import_settings_header',
                'desc' => 'import_settings_description',
                'fields' => [
                    'settings' => [
                        'name' => 'settings',
                        'type' => 'file',
                        'note' => lang('import_overwrite_settings'),
                    ],
                ],
            ],
            [
                'title' => 'export_settings_header',
                'desc' => 'export_settings_description',
                'fields' => [
                    'sub_id' => [
                        'type' => 'action_button',
                        'link' => $this->getExportUrl(),
                        'text' => 'export_settings_header',
                    ],
                ],
            ],
        ];

        return [$form];
    }
}
