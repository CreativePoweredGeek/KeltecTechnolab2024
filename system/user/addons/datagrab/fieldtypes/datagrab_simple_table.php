<?php

use BoldMinded\DataGrab\Dependency\Litzinger\Basee\App;
use BoldMinded\DataGrab\Traits\FileUploadDestinations;

/**
 * DataGrab Simple Table fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_simple_table extends AbstractFieldType
{
    use FileUploadDestinations;

    /**
     * Register a setting so it can be saved
     *
     * @param string $field_name
     * @return array
     */
    public function register_setting(string $field_name): array
    {
        return [
            $field_name . '_columns',
            $field_name . '_unique',
            $field_name . '_extra1',
            $field_name . '_extra2',
        ];
    }

    private function getFieldSettings(string|int $fieldIdentifier): array
    {
        $channelField = ee('Model')->get('ChannelField');

        if (is_numeric($fieldIdentifier)) {
            $channelField->filter('field_id', $fieldIdentifier);
        } else {
            $channelField->filter('field_name', $fieldIdentifier);
        }

        $field = $channelField->first();

        if (!$field) {
            // @todo
        }

        return $field->field_settings ?? [];
    }

    public function display_configuration(
        Datagrab_model $DG,
        string $fieldName,
        string $fieldLabel,
        string $fieldType,
        bool $fieldRequired = false,
        array $data = []
    ): array
    {
        $config = [];
        $config['label'] = form_label($fieldLabel) . NBS .
            anchor("https://docs.boldminded.com/datagrab/docs/field-types/simple-table", "(?)", 'class="datagrab_help"');
        if ($fieldRequired) {
            $config['label'] .= ' <span class="datagrab_required">*</span>';
        }
        $config["label"] .= '<div class="datagrab_subtext">' . $fieldType . "</div>";
        $config['value'] = '';

        $default = [];

        // Get current saved setting
        if (isset($data['default_settings']['cf'][$fieldName . '_columns'])) {
            $default = $data['default_settings']['cf'][$fieldName . '_columns'];
        }

        $fieldSettings = $this->getFieldSettings($fieldName);
        $fieldColumns = array_fill(1, $fieldSettings['max_columns'], 'Column %d');

        foreach ($fieldColumns as $colId => $label) {
            $config['value'] .= '<p>' . sprintf($label, $colId) . NBS . ':' . NBS;
            $config['value'] .= form_dropdown(
                $fieldName . '_columns[' . $colId . ']',
                $data['data_fields'],
                $default[$colId] ?? ''
            );
            $config['value'] .= '</p>';
        }

        $column_options = array();
        $column_options['0'] = 'Keep existing rows and append new';
        $column_options['-1'] = 'Delete all existing rows';
        $sub_options = array();
        foreach ($fieldColumns as $colId => $label) {
            $sub_options[$colId] = sprintf($label, $colId);
        }
        $column_options['Update the row if this column matches:'] = $sub_options;

        $config['value'] .= '<p>' .
            'Action to take when an entry is updated: ' .
            form_dropdown(
                $fieldName . '_unique',
                $column_options,
                $data['default_settings']['cf'][$fieldName . '_unique'] ?? ''
            ) .
            '</p>';

        return $config;
    }

    public function save_configuration(Datagrab_model $DG, string $fieldName = '', array $customFieldSettings = [])
    {
        // If no columns are defined for accepting import data, don't remove any existing data on the entry
        // when performing an import and updating existing entries, and don't remove any rows from Grid fields
        // that we don't want to import data into.
        if (
            array_key_exists($fieldName . '_columns', $customFieldSettings) &&
            empty(array_filter($customFieldSettings[$fieldName . '_columns']))
        ) {
            return 0;
        }

        return 1;
    }

    public function final_post_data(
        Datagrab_model $DG,
        array $item = [],
        int $fieldId = 0,
        string $fieldName = '',
        array &$data = [],
        int $updateEntryId = 0
    ) {
        // $fields contains a list of grid columns mapped to data elements
        // eg, $fields[3] => 5 means map data element 5 to grid column 3
        $fields = array_filter($DG->settings['cf'][$fieldName . '_columns']);
        $grid = [];

        // Loop over columns
        foreach ($fields as $colId => $column) {
            if (preg_match('/\/(\d+)\/(\d+)\//', $column, $matches)) {
                $DG->logger->log(sprintf(
                    'Your data structure appears to be too deeply nested to import: %s',
                    $column
                ));
            }

            // Loop over data items
            if (
                $column &&
                $DG->dataType->initialise_sub_item($item, $column, $DG->settings, $fieldName)
            ) {
                $subItem = $DG->dataType->get_sub_item($item, $column, $DG->settings, $fieldName);
                $rowNum = 1;
                $rowId = 'new_row_' . $rowNum;

                while ($subItem !== false) {
                    if (!isset($grid[$rowId])) {
                        $grid[$rowId] = [];
                    }

//                    $key = array_search($subItem, $item);

//                    if ($key && array_key_exists($key.'@heading_row', $item)) {
//                        $grid[$rowId]['col_heading_row'] = $subItem;
//                    } else {
//                        $grid[$rowId]['col_id_' . $colId] = $subItem;
//                    }

                    $subItem = $DG->dataType->get_sub_item($item, $column, $DG->settings, $fieldName);

                    $rowNum++;
                    $rowId = 'new_row_' . $rowNum;
                }
            }
        }

        // Remove empty rows
        $newgrid = array();
        foreach ($grid as $idx => $row) {
            $empty = true;
            foreach ($row as $col) {
                if ($col != '') {
                    $empty = false;
                    continue;
                }
            }
            if (!$empty) {
                $newgrid[$idx] = $row;
            }
        }
        $grid = $newgrid;

        if ($updateEntryId) {
            // Find out what to do with existing data (delete or keep?)
            $unique = 0;
            if (isset($DG->settings['cf'][$fieldName . '_unique'])) {
                $unique = $DG->settings['cf'][$fieldName . '_unique'];
            }

            // Is this the first time this entry has been updated during this import?
            if (!in_array($updateEntryId, $DG->entries)) {
                // This is the first import, so delete existing rows if required
                if ($unique == -1) {
                    // Delete existing rows
                    //$DG->logger->log('Remove existing rows from the Grid field, if any exist.');
                    $old = array();
                } else {
                    // Keep existing rows
                    // Fetch existing data
                    //$DG->logger->log('Keep existing rows from the Grid field.');
                    $old = $this->_rebuild_grid_data($updateEntryId, $DG, $fieldId);
                }
            } else {
                // Fetch existing data
                $old = $this->_rebuild_grid_data($updateEntryId, $DG, $fieldId);
            }

            // "Action to take when an entry is updated" - If $unique is set to a positive int value, then it's a
            // col_id from the config array to only update the row if the new column value does not match
            // the existing, column value.
            if ($unique > 0) {
                $indexedGrid = array_values($grid);
                $indexedOld = array_values($old);
                foreach ($indexedGrid as $index => $rowData) {
                    $currentRow = $indexedOld[$index];
                    if (
                        isset($currentRow['col_id_' . $unique]) &&
                        $currentRow['col_id_' . $unique] !== $rowData['col_id_' . $unique]
                    ) {
                        $DG->logger->log(sprintf(
                            '"%s" does not match "%s", appending Grid row.',
                            $currentRow['col_id_' . $unique],
                            $rowData['col_id_' . $unique]
                        ));
                        $grid = array_merge($old, $grid);
                    }
                }
            } elseif (!empty($old)) {
                $DG->logger->log('Appending new row(s) to Grid');
                $grid = array_merge($old, $grid);
            }
        }

        $data['field_id_' . $fieldId] = ['rows' => $grid];
    }

    private function _rebuild_grid_data($entry_id, $DG, $field_id)
    {
        $where = [
            'entry_id' => $entry_id,
        ];

        // -------------------------------------------
        //  'ajw_datagrab_rebuild_simple_table_query' hook
        //
        if ($DG->extensions->active_hook('ajw_datagrab_rebuild_simple_table_query')) {
            $DG->logger->log('Calling ajw_datagrab_rebuild_simple_table_query() hook.');
            $rows = $DG->extensions->call('ajw_datagrab_rebuild_simple_table_query', $where, $field_id);
        } else {
            $entry = ee('Model')->get('ChannelEntry', $entry_id)->first();

            if ($entry->{'field_id_' . $field_id}) {
                $rows = json_decode($entry->{'field_id_' . $field_id}, true);
            }
        }

        return $rows;
    }
}
