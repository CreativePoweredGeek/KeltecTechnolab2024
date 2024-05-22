<?php

/**
 * DataGrab Relationship fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_relationship extends AbstractFieldType
{
    public int $order = 1;

    public function register_setting(string $field_name): array
    {
        return [$field_name . "_relationship_field"];
    }

    public function display_configuration(Datagrab_model $DG, string $fieldName, string $fieldLabel, string $fieldType, bool $fieldRequired = false, array $data = []): array
    {
        $config = [];
        $config["label"] = form_label($fieldLabel) . NBS .
        anchor("https://docs.boldminded.com/datagrab/docs/field-types/relationships", "(?)", 'class="datagrab_help"');

        if ($fieldRequired) {
            $config["label"] .= ' <span class="datagrab_required">*</span>';
        }
        $config["label"] .= '<div class="datagrab_subtext">' . $fieldType . "</div>";

        $config["value"] = "<p>" . form_dropdown(
                $fieldName, $data["data_fields"],
                $data["default_settings"]["cf"][$fieldName] ?? ''
            )
            . "</p><p>Field to match: " . NBS .
            form_dropdown(
                $fieldName . "_relationship_field",
                $data["all_fields"],
                ($data["default_settings"]["cf"][$fieldName . "_relationship_field"] ?? '')
            ) . "</p>";

        return $config;
    }

    public function prepare_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        // Can the current datatype handle sub-loops (eg, XML)?
        if (
            $DG->dataType->datatype_info["allow_subloop"] &&
            $DG->dataType->initialise_sub_item($item, $DG->settings["cf"][$fieldName], $DG->settings, $fieldName)
        ) {
            $data["field_id_" . $fieldId] = array();
            $data["field_id_" . $fieldId]["sort"] = array();
            $data["field_id_" . $fieldId]["data"] = array();

            $this->order = 1;

            // Loop over sub items
            while ($subItem = $DG->dataType->get_sub_item($item, $DG->settings["cf"][$fieldName], $DG->settings, $fieldName)) {
                $sortedEntries = $this->getSortedRelationships(
                    $subItem,
                    $fieldName,
                    $data,
                    $fieldId,
                    $DG
                );

                foreach ($sortedEntries as $entryId => $entryTitle) {
                    $data["field_id_" . $fieldId]["data"][] = $entryId;
                    $data["field_id_" . $fieldId]["sort"][] = $this->order++;

                    // -------------------------------------------
                    //  'ajw_datagrab_prepare_post_data_relationships' hook
                    //      - Extensions implementing this hook must accept $data as a reference
                    //
                    if ($DG->extensions->active_hook('ajw_datagrab_prepare_post_data_relationships')) {
                        $DG->logger->log('Calling ajw_datagrab_prepare_post_data_relationships() hook.');
                        $DG->extensions->call('ajw_datagrab_prepare_post_data_relationships', $data, $subItem, $fieldName, $fieldId, $this->order, $DG);
                    }
                    //
                    // -------------------------------------------
                }
            }
        }
    }

    public function final_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        // In Relationships case, if the data element is empty, no relationships, then we need to unset the
        // field entirely, otherwise the rebuild_post_data event will not trigger, so it's impossible to assign
        // more than 1 relationship at a time. Without this it will create the relationships, but continually
        // reset their assignments to 0 if the entry is updated again in the same import routine.
        $fieldData = $data['field_id_' . $fieldId]['data'] ?? [];

        if (empty($fieldData)) {
            unset($data['field_id_' . $fieldId]);
        }

        return $data;
    }

    public function rebuild_post_data(Datagrab_model $DG, int $fieldId = 0, array &$data = [], array $existingData = [])
    {
        $where = [
            'parent_id' => $existingData["entry_id"],
            'field_id' => $fieldId
        ];

        // -------------------------------------------
        //  'ajw_datagrab_rebuild_relationships_query' hook
        //
        if ($DG->extensions->active_hook('ajw_datagrab_rebuild_relationships_query')) {
            $DG->logger->log('Calling ajw_datagrab_rebuild_relationships_query() hook.');
            $query = $DG->extensions->call('ajw_datagrab_rebuild_relationships_query', $where);
        } else {
            ee()->db->select("child_id, order");
            ee()->db->where($where);
            ee()->db->order_by("order");
            $query = ee()->db->get("exp_relationships");
        }
        //
        // -------------------------------------------

        $d = [];
        $sort = [];
        foreach ($query->result_array() as $row) {
            $d[] = $row["child_id"];
            $sort[] = $row["order"];
        }

        // Rebuild selections array
        $data["field_id_" . $fieldId] = [
            "data" => $d,
            "sort" => $sort
        ];
    }

    /**
     * @param mixed $subItem
     * @param mixed $fieldName
     * @param array $data
     * @param int $fieldId
     * @param Datagrab_model $DG
     * @param string $contentType
     * @return array
     */
    public function getSortedRelationships(
        mixed $subItem,
        string $fieldName,
        array $data,
        int $fieldId,
        Datagrab_model $DG,
        string $contentType = 'channel'
    ): array
    {
        // @todo cache this query?
        if ($contentType === 'grid') {
            $columnModel = ee('Model')->get('grid:GridColumn', $fieldId)->first();
            $settings = $columnModel->getProperty('col_settings');
        } else {
            $fieldModel = ee('Model')->get('ChannelField', $fieldId)->first();
            $settings = $fieldModel->getSettingsValues();
        }

        // Check whether item matches a valid entry and create a relationship
        // Check which field to compare
        if (!isset($DG->settings["cf"][$fieldName . "_relationship_field"])) {
            // If not set (usually old saved import) then default to title
            $relationshipFieldName = 'title';
        } else {
            // Custom field
            $relationshipFieldName = $DG->settings["cf"][$fieldName . "_relationship_field"];
        }

        $relatedValue = $subItem;
        $relationshipFieldName = str_replace("exp_channel_titles.", "", $relationshipFieldName);
        $allowMultiple = boolval($settings['field_settings']['allow_multiple'] ?? 0);

        if (strpos($subItem, ',') !== false) {
            $relatedValue = explode(',', $subItem);
        } elseif (strpos($subItem, '|') !== false) {
            $relatedValue = explode('|', $subItem);
        }

        if (is_array($relatedValue)) {
            $relatedValue = array_map('trim', $relatedValue);
        }

        // Make sure we have actual entry IDs
        if ($allowMultiple && is_array($relatedValue) && !empty($relatedValue)) {
            $entries = ee('Model')->get('ChannelEntry')
                ->filter($relationshipFieldName, 'IN', $relatedValue)
                ->all()
                ->getDictionary('entry_id', 'title');
        } else {
            $entries = ee('Model')->get('ChannelEntry')
                ->filter($relationshipFieldName, $relatedValue)
                ->all()
                ->getDictionary('entry_id', 'title');
        }

        if (!is_array($relatedValue)) {
            $relatedValue = [$relatedValue];
        }

        if ($relationshipFieldName === 'entry_id') {
            $sortedEntries = $this->sortArrayByArray($entries, $relatedValue);
        } else {
            $sortedEntries = array_flip($this->sortArrayByArray(array_flip($entries), $relatedValue));
        }

        return $sortedEntries;
    }

    /**
     * @param array $toSort
     * @param array $sortBy
     * @return array
     */
    public static function sortArrayByArray(array $toSort, array $sortBy): array
    {
        $ordered = [];

        foreach ($sortBy as $key) {
            if (array_key_exists($key, $toSort)) {
                $ordered[$key] = $toSort[$key];
                unset($toSort[$key]);
            }
        }

        return $ordered + $toSort;
    }
}
