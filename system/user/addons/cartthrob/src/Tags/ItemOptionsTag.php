<?php

namespace CartThrob\Tags;

use CartThrob\Cache\Cache;
use CartThrob\Dependency\Illuminate\Support\Arr;
use EE_Session;

class ItemOptionsTag extends Tag
{
    /** @var Cache */
    private $cache;

    public function __construct(EE_Session $session, Cache $cache)
    {
        parent::__construct($session);

        $this->cache = $cache;

        ee()->load->library('api');
        ee()->load->helper(['inflector', 'array']);
    }

    public function process()
    {
        $entryId = $this->param('entry_id');
        $rowId = $this->param('row_id');
        $fields = $this->explodeParam('field');
        $item = false;
        $parentId = false;
        $itemRowId = false;
        $selected = false;
        $count = 0;
        $returnData = '';

        if ($entryId === false && $rowId === false) {
            return ee()->TMPL->no_results();
        }

        // Convert a row ID into an entry ID
        if ($rowId !== false) {
            list($item, $parentId, $itemRowId) = $this->getItem($rowId);

            if ($item && $item->product_id()) {
                $entryId = $item->product_id();
            }
        }

        $priceModifiers = ee()->product_model->get_all_price_modifiers($entryId);

        // Clear all price modifiers that are in all_item_options
        if ($rowId === false && $allItemOptions = ee()->cartthrob->cart->meta('all_item_options')) {
            foreach ($priceModifiers as $key => $value) {
                if (in_array($key, $allItemOptions)) {
                    unset($priceModifiers[$key]);
                }
            }
        }

        $itemOptions = [];

        // Default all price modifiers to false
        foreach (array_keys($priceModifiers) as $key) {
            $itemOptions[$key] = false;
        }

        // Load item options
        if ($item && is_array($item->item_options())) {
            $conf = $item->meta('configuration');

            foreach (array_keys($item->item_options()) as $key) {
                // Default all unset item options to true
                if (!isset($itemOptions[$key])) {
                    $itemOptions[$key] = true;
                }

                // Clear all item options that are in the item configuration
                if ($conf && is_array($conf)) {
                    foreach ($conf as $k => $v) {
                        if (array_key_exists($k, $itemOptions)) {
                            unset($itemOptions[$k]);
                            continue;
                        }
                    }
                }
            }
        }

        $this->clearSelectedTagFromTagData();
        $coilpack = [];
        foreach ($itemOptions as $fieldName => $dynamic) {
            $entry = null;

            if ($fields && !in_array($fieldName, $fields)) {
                continue;
            }

            ++$count;

            // add this line for dynamic options
            $optionValue = ($item) ? $item->item_options($fieldName) : '';

            if ($item && $item->is_sub_item() && $entry = ee()->cartthrob_entries_model->entry($item->parent_item()->product_id())) {
                // already in the cart
                $itemRowId = $item->row_id();
                $optionValue = $item->item_options($fieldName);
            } elseif ($parentId) {
                $entry = ee()->cartthrob_entries_model->entry($parentId);
            }

            $vars = [];
            $vars['allow_selection'] = 1;

            if ($entry && $itemRowId !== false && $fieldId = ee()->cartthrob_field_model->channel_has_fieldtype($entry['channel_id'], 'cartthrob_package', true)) {
                ee()->legacy_api->instantiate('channel_fields');

                if (empty(ee()->api_channel_fields->field_types)) {
                    ee()->api_channel_fields->fetch_installed_fieldtypes();
                }

                if (ee()->api_channel_fields->setup_handler('cartthrob_package')) {
                    $cacheKey = "cartthrob_package.{$entry['entry_id']}.{$fieldId}";

                    if (!$this->cache->has($cacheKey)) {
                        $this->cache->set($cacheKey, ee()->api_channel_fields->apply('pre_process', [$entry['field_id_' . $fieldId]]));
                    }

                    $fieldData = $this->cache->get($cacheKey);

                    if (isset($fieldData[$itemRowId]) && empty($fieldData[$itemRowId]['allow_selection'][$fieldName])) {
                        $vars['allow_selection'] = 0;
                    }

                    if (!$item && Arr::has($fieldData, "{$itemRowId}.option_presets.{$fieldName}") &&
                        !in_array($fieldData[$itemRowId]['option_presets'][$fieldName], [null, ''])
                    ) {
                        $optionValue = $fieldData[$itemRowId]['option_presets'][$fieldName];
                        $selected = $optionValue;
                    }
                }
            }

            $vars = array_merge($this->itemOptionVars($entryId, $rowId, $fieldName, $selected), $vars);
            $vars['option_field'] = $fieldName;
            $vars['option_label'] = $vars['item_options:option_label'] = ee()->cartthrob_field_model->get_field_label(ee()->cartthrob_field_model->get_field_id($fieldName));
            $vars['field_type'] = ee()->cartthrob_field_model->get_field_type(ee()->cartthrob_field_model->get_field_id($fieldName));
            $vars['item_options_total_results'] = count($itemOptions);
            $vars['item_options_count'] = $count;
            $vars['dynamic'] = $dynamic;
            $vars['option_value'] = $optionValue;
            $vars['options_exist'] = isset($priceModifiers[$fieldName]) && count($priceModifiers[$fieldName]) > 0;

            if ($vars['field_type'] == 'cartthrob_price_modifiers_configurator' && strpos($vars['option_field'], ':') === false) {
                $vars['configuration_label'] = $vars['option_label'];
            } else {
                $vars['configuration_label'] = null;
            }

            if (empty($vars['option_label'])) {
                $labels = ee()->cartthrob->cart->meta('item_option_labels');

                if (isset($labels[$vars['option_field']])) {
                    $vars['option_label'] = $vars['item_options:option_label'] = $labels[$vars['option_field']];
                } else {
                    $vars['option_label'] = $vars['item_options:option_label'] = humanize($fieldName);
                }
            }

            if (ee()->has('coilpack')) {
                $vars['options'] = $priceModifiers[$vars['option_field']] ?? [];
            }

            $coilpack[] = $vars;
            $returnData .= $this->parseVariablesRow($vars);
        }

        ee()->TMPL->set_data($coilpack);

        return $returnData;
    }

    /**
     * @param $rowId
     * @return array
     */
    private function getItem($rowId): array
    {
        $item = false;
        $parentId = false;
        $itemRowId = false;

        if (strpos($rowId, 'configurator:') !== false) {
            $item = ee()->cartthrob->cart->item($rowId);
        } elseif (strpos($rowId, ':') !== false) {
            list($parentId, $itemRowId) = explode(':', $rowId);
            if ($parentItem = ee()->cartthrob->cart->item($parentId)) {
                $item = $parentItem->sub_item($itemRowId);
            }
        } else {
            $item = ee()->cartthrob->cart->item($rowId);
        }

        return [$item, $parentId, $itemRowId];
    }

    /**
     * If we leave {selected} in there, assign_variables output is wrong
     */
    private function clearSelectedTagFromTagData()
    {
        $this->setTagdata(str_replace('{selected}', '8bdb34edd2d86eff7aa60be77e3002f5', $this->tagdata()));

        $variables = ee('Variables/Parser')->extractVariables($this->tagdata());

        $this->setVarSingle($variables['var_single'])
            ->setVarPair($variables['var_pair'])
            ->setTagdata(str_replace('8bdb34edd2d86eff7aa60be77e3002f5', '{selected}', $this->tagdata()));
    }
}
