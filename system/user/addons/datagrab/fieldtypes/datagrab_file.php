<?php

use BoldMinded\DataGrab\Dependency\Litzinger\Basee\App;
use BoldMinded\DataGrab\Traits\FileUploadDestinations;

/**
 * DataGrab File fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_file extends AbstractFieldType
{
    use FileUploadDestinations;

    public function register_setting(string $field_name): array
    {
        return [
            $field_name . '_filedir',
            $field_name . '_fetch',
            $field_name . '_makesubdir',
        ];
    }

    public function display_configuration(Datagrab_model $DG, string $fieldName, string $fieldLabel, string $fieldType, bool $fieldRequired = false, array $data = []): array
    {
        $default = [];

        // Get current saved setting
        if (isset($data["default_settings"]["cf"])) {
            $default = $data["default_settings"]["cf"];
        }

        // Build config form
        $config = array();
        $config["label"] = form_label($fieldLabel);
        if ($fieldRequired) {
            $config["label"] .= ' <span class="datagrab_required">*</span>';
        }
        $config["label"] .= anchor("https://docs.boldminded.com/datagrab/docs/field-types/file", "(?)", 'class="datagrab_help"');
        $config["label"] .= '<div class="datagrab_subtext">' . $fieldType . "</div>";

        $defaultValue = $default[$fieldName . "_filedir"] ?? 0;
        $uploadFolderOptions = $this->buildFileUploadDropdown($fieldName, $defaultValue);

        $config["value"] = "<p>" .
            form_dropdown(
                $fieldName,
                $data['data_fields'],
                $default[$fieldName] ?? ''
            ) .
            "</p>
            " . $uploadFolderOptions . "
            <p>Fetch files from urls: " . NBS .
            form_dropdown(
                $fieldName . '_fetch',
                ['No', 'Yes'],
                $default[$fieldName . '_fetch'] ?? ''
            ) .
            "</p>
            <p>Create sub-directories: " . NBS .
            form_dropdown(
                $fieldName . '_makesubdir',
                ['No', 'Yes'],
                $default[$fieldName . '_makesubdir'] ?? ''
            ) .
            "</p>";

        return $config;
    }

    public function prepare_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        $data["field_id_" . $fieldId] = "";

        // Fetch file from data
        if ($DG->dataType->get_item($item, $DG->settings["cf"][$fieldName]) != "") {

            $filename = $DG->getFile(
                $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName]),
                $DG->settings["cf"][$fieldName . '_filedir'],
                $DG->settings["cf"][$fieldName . '_fetch'] == 1,
                $DG->settings["cf"][$fieldName . '_makesubdir'] == 1
            );

            if ($filename) {
                $data[sprintf('field_id_%d', $fieldId)] = $filename;
            }
        }
    }
}
