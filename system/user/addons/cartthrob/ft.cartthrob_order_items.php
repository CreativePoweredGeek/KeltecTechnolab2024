<?php

use CartThrob\Dependency\Illuminate\Support\Arr;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cartthrob_order_items_ft extends Cartthrob_matrix_ft
{
    public $info = [
        'name' => 'CartThrob Order Items',
        'version' => CARTTHROB_VERSION,
    ];
    public $variable_prefix = 'item:';
    public $row_nomenclature = 'item';
    public $default_row = [
        'entry_id' => '',
        'title' => '',
        'quantity' => '',
        'price' => '',
    ];
    private $defaultColumns = [
        'row_id',
        'row_order',
        'order_id',
        'entry_id',
        'title',
        'quantity',
        'price',
        'price_plus_tax',
        'weight',
        'shipping',
        'no_tax',
        'no_shipping',
        'extra',
    ];
    private $subItems;

    /**
     * @param $data
     * @return array|mixed
     */
    public function pre_process($data)
    {
        loadCartThrobPath();
        ee()->load->library('number');
        ee()->load->model('order_model');

        if ($entryId = $this->getEntryId()) {
            $data = ee()->order_model->getOrderItems($entryId);
        }

        if (!is_array($data) || empty($data)) {
            unloadCartThrobPath();

            return $data;
        }

        foreach ($data as $i => $row) {
            // this is to set blank columns that arent in all rows
            if (!isset(ee()->session->cache['cartthrob_order_items']['extra_columns'][$row['order_id']])) {
                ee()->session->cache['cartthrob_order_items']['extra_columns'][$row['order_id']] = [];
            }

            ee()->session->cache['cartthrob_order_items']['original_columns'][$row['order_id']][$i] = array_keys($row);

            foreach (array_keys($row) as $key) {
                if (in_array($key, $this->defaultColumns)) {
                    continue;
                }

                if (!in_array($key, ee()->session->cache['cartthrob_order_items']['extra_columns'][$row['order_id']])) {
                    ee()->session->cache['cartthrob_order_items']['extra_columns'][$row['order_id']][] = $key;
                }
            }

            foreach (ee()->session->cache['cartthrob_order_items']['extra_columns'][$row['order_id']] as $key) {
                if (!isset($row[$key])) {
                    $data[$i][$key] = '';
                }
            }
        }

        unloadCartThrobPath();

        return $data;
    }

    /**
     * @param $data
     * @param array $params
     * @param bool $tagdata
     * @return bool|string|string[]
     */
    public function replace_tag($data, $params = [], $tagdata = false)
    {
        // @TODO add packages sub tag pair
        $regex = '/' . LD . 'if ' . $this->variable_prefix . 'no_results' . RD . '(.*?)' . LD . '\/if' . RD . '/s';
        if (count($data) === 0 && preg_match($regex, $tagdata, $match)) {
            $tagdata = str_replace($match[0], '', $tagdata);

            ee()->TMPL->no_results = $match[1];
        }

        if (!$data) {
            return ee()->TMPL->no_results();
        }

        loadCartThrobPath();
        ee()->load->helper('array');
        ee()->load->library(['data_filter', 'number']);
        ee()->load->model(['product_model', 'cartthrob_entries_model', 'cartthrob_field_model']);

        $totalResults = count($data);

        // looking for row-id parameter
        if (isset($params['row_id'])) {
            $row_ids = explode('|', $params['row_id']);
            ee()->data_filter->filter($data, 'row_id', $row_ids, 'in_array', true);
        }

        $orderBy = Arr::get($params, 'orderby', false);
        $sort = Arr::get($params, 'sort', false);
        $limit = Arr::get($params, 'limit', false);
        $offset = Arr::get($params, 'offset', false);

        ee()->data_filter->sort($data, $orderBy, $sort);
        ee()->data_filter->limit($data, $limit, $offset);

        $count = 1;

        if (preg_match_all('#{packages?(.*?)}(.*?){/packages?}#s', ee()->TMPL->tagdata, $matches)) {
            $packageTagdata = [];

            foreach ($matches[0] as $i => $fullMatch) {
                $packageTagdata[substr($fullMatch, 1, -1)] = $matches[2][$i];
            }
        }

        foreach ($data as $i => $row) {
            $itemOptions = [];
            $subItems = [];
            $rawItemOptions = [];

            foreach ($row as $key => $value) {
                if (is_array($value)) {
                    if ($key === 'sub_items') {
                        $subItems = $value;
                        continue;
                    } else {
                        $row[$key] = $value = implode('|', $value);
                    }
                }

                if (!in_array($key, $this->defaultColumns)
                    && isset(ee()->session->cache['cartthrob_order_items']['original_columns'][$row['order_id']][$i])
                    && in_array($key, ee()->session->cache['cartthrob_order_items']['original_columns'][$row['order_id']][$i])) {
                    $rawItemOptions[$key] = $value;
                }
            }

            if (isset($packageTagdata)) {
                foreach ($packageTagdata as $fullMatch => $_packageTagdata) {
                    $row[$fullMatch] = '';

                    foreach ($subItems as &$subItem) {
                        $subItemVars = array_merge($subItem, array_key_prefix($subItem, 'sub:'));

                        if (isset($subItem['entry_id']) && $sub_product = ee()->product_model->get_product($subItem['entry_id'])) {
                            $subItemVars = array_merge(ee()->cartthrob_entries_model->entry_vars($sub_product, $_packageTagdata, 'sub:'), $subItemVars);
                        }

                        $row[$fullMatch] .= ee()->TMPL->parse_variables($_packageTagdata, [$subItemVars]);
                    }
                }
            }

            $row['is_package'] = count($subItems) > 0 ? 1 : 0;
            $row['count'] = $count;
            $row['total_results'] = $totalResults;
            $row['first_' . $this->row_nomenclature] = (int)($count === 1);
            $row['last_' . $this->row_nomenclature] = (int)($count === $totalResults);
            $row[$this->row_nomenclature . '_count'] = $row['count'];
            $row['total_' . $this->row_nomenclature . 's'] = $row['total_results'];

            $regex = '/' . LD . '(' . preg_quote($this->variable_prefix) . '|row_)?' . 'switch=([\042\047])(.+)\\2' . RD . '/';

            if (preg_match_all($regex, $tagdata, $matches)) {
                foreach ($matches[0] as $j => $v) {
                    $values = explode('|', $matches[3][$j]);
                    $row[substr($matches[0][$j], 1, -1)] = $values[($count - 1) % count($values)];
                }
            }

            if (isset($row['price']) && $row['price'] !== '') {
                $row['price_numeric'] = $row['price'];
                $row['price'] = ee()->number->format($row['price']);
            } else {
                $row['price_numeric'] = 0;
            }

            if (!isset($row['price_plus_tax'])) {
                $row['price_plus_tax'] = 0;
            }

            $row['price_numeric:plus_tax'] = $row['price_plus_tax_numeric'] = $row['price_plus_tax'];
            $row['price:plus_tax'] = $row['price_plus_tax'] = ee()->number->format($row['price_plus_tax']);

            if (isset($row['quantity']) && isset($row['price'])) {
                $row['subtotal'] = ee()->number->format($row['quantity'] * $row['price_numeric']);
                $row['subtotal_plus_tax'] = ee()->number->format($row['quantity'] * $row['price_plus_tax_numeric']);
                $row['subtotal_plus_tax_numeric'] = $row['quantity'] * $row['price_plus_tax_numeric'];
            }

            $row = array_merge($row, array_key_prefix($row, $this->variable_prefix));

            if (isset($row['entry_id']) && $product = ee()->product_model->get_product($row['entry_id'])) {
                $row = array_merge(ee()->cartthrob_entries_model->entry_vars($product, $tagdata, $this->variable_prefix), $row);

                foreach (ee()->product_model->get_all_price_modifiers($row['entry_id']) as $fieldName => $options) {
                    if (isset($rawItemOptions[$fieldName])) {
                        if (!$optionLabel = ee()->cartthrob_field_model->get_field_label(ee()->cartthrob_field_model->get_field_id($fieldName))) {
                            $optionLabel = $fieldName;
                        }

                        $subLabel = null;
                        $optionPrice = null;

                        foreach ($options as $optionRow) {
                            if ($rawItemOptions[$fieldName] == element('price', $optionRow)) {
                                $optionPrice = $optionRow['price'];
                                break;
                            }
                        }

                        $optionName = $rawItemOptions[$fieldName];

                        foreach ($options as $optionRow) {
                            if ($rawItemOptions[$fieldName] == element('option_value', $optionRow)) {
                                $optionName = $optionRow['option_name'];
                                break;
                            }
                        }

                        $itemOptions[] = [
                            'option_value' => $rawItemOptions[$fieldName],
                            'option_name' => $optionName,
                            'option_label' => $optionLabel,
                            'sub_label' => $subLabel,
                            'configuration_label' => $subLabel,
                            'option_price' => $optionPrice,
                            'dynamic' => false,
                        ];

                        // later on we'll add in the dynamic options
                        unset($rawItemOptions[$fieldName]);
                    }

                    if (!isset($row[$fieldName])) {
                        continue;
                    }

                    foreach ($options as $optionRow) {
                        if ($row[$fieldName] == element('option_value', $optionRow)) {
                            foreach ($optionRow as $key => $value) {
                                $row[$fieldName . ':' . $key] = $value;
                            }

                            break;
                        }
                    }
                }
            } else {
                if (!$optionLabel = ee()->cartthrob_field_model->get_field_label(ee()->cartthrob_field_model->get_field_id($i))) {
                    $optionLabel = $key;
                }

                $subLabel = null;
                $itemOptions[] = [
                    'option_value' => $value,
                    'option_name' => $value,
                    'option_label' => $optionLabel,
                    'sub_label' => $subLabel,
                    'configuration_label' => $subLabel,
                    'option_price' => null,
                    'dynamic' => true,
                ];
            }

            if (isset($rawItemOptions)) {
                foreach ($rawItemOptions as $key => $value) {
                    if (!$optionLabel = ee()->cartthrob_field_model->get_field_label(ee()->cartthrob_field_model->get_field_id($key))) {
                        $optionLabel = $key;
                    }

                    $subLabel = null;
                    $optionLabel = ucwords(str_replace('_', ' ', $optionLabel));

                    if (strstr($optionLabel, ':') !== false) {
                        // get last item after breaking at : so... item_option_x:Color becomes "Color"
                        $optionLabel = explode(':', $optionLabel);
                        $subLabel = array_pop($optionLabel);

                        // if for some reason it has underscores and stuff... replace them.
                        $subLabel = ucwords(str_replace(['_', '-'], ' ', $subLabel));
                    }

                    $itemOptions[] = [
                        'option_value' => $value,
                        'option_name' => $value,
                        'option_label' => $optionLabel,
                        'option_price' => null,
                        'sub_label' => $subLabel,
                        'configuration_label' => $subLabel,
                        'dynamic' => true,
                    ];
                }
            }

            $row['item_options'] = (count($itemOptions) > 0) ? $itemOptions : [[]];
            $data[$i] = $row;

            $count++;
        }

        $tagdata = ee()->TMPL->parse_variables($tagdata, $data);

        // removed unparsed tags
        if ($this->variable_prefix && preg_match_all('/{' . preg_quote($this->variable_prefix) . '(.*?)}/', $tagdata, $matches)) {
            foreach ($matches[0] as $match) {
                $tagdata = str_replace($match, '', $tagdata);
            }
        }

        unloadCartThrobPath();

        return $tagdata;
    }

    /**
     * @param $data
     * @param bool $replace_tag
     * @return string
     */
    public function display_field($data, $replace_tag = false)
    {
        loadCartThrobPath();
        ee()->load->model('order_model');

        if (!$replace_tag) {
            $data = $this->content_id ? ee()->order_model->getOrderItems($this->content_id) : [];
        }

        $hideFields = ['weight', 'shipping', 'no_tax', 'no_shipping'];
        $this->subItems = [];
        $data1 = $data;

        foreach ($data as $rowId => $row) {
            if (isset($row['sub_items'])) {
                $this->subItems[$rowId] = $row['sub_items'];

                foreach ($this->subItems[$rowId] as $subItem) {
                    foreach ($hideFields as $key) {
                        if (!empty($subItem[$key]) && $subItem[$key] != 0) {
                            unset($hideFields[array_search($key, $hideFields)]);
                        }
                    }
                }
            }

            unset($data[$rowId]['row_order'], $data[$rowId]['order_id'], $data[$rowId]['extra'], $data[$rowId]['sub_items']);

            foreach ($hideFields as $key) {
                if (!empty($row[$key]) && $row[$key] != 0) {
                    unset($hideFields[array_search($key, $hideFields)]);
                }
            }
        }

        $this->hiddenColumns = $hideFields;
        $this->hiddenColumns[] = 'row_id';

        $output = parent::display_field($data, $replace_tag);

        foreach ($data1 as $rowId => $row1) {
            if (isset($row1['sub_items'])) {
                // print_r($row1['sub_items']);
                foreach ($row1['sub_items'] as $item_id => $row_item) {
                    foreach ($row_item as $key => $val) {
                        $output = $output . form_hidden($this->field_name . '[' . $rowId . '][sub_items][' . $item_id . '][' . $key . ']',
                            (isset($val)) ? $val : '');
                    }
                }
            }
        }

        if (!$replace_tag && empty(ee()->session->cache['cartthrob_order_items']['head'])) {
            ee()->load->library('javascript');

            ee()->session->cache['cartthrob_order_items']['head'] = true;

            $lang = [
                'show_package_details' => lang('show_package_details'),
                'hide_package_details' => lang('hide_package_details'),
            ];

            ee()->cp->add_to_head('<style type="text/css">.cartthrobOrderItems tbody tr.packageHeader td, .cartthrobOrderItems tbody tr.package td { background-color: #fafafa; }</style>');
            ee()->cp->add_to_foot('
                <script type="text/javascript">
                    $.extend($.cartthrobMatrix.lang, ' . json_encode($lang) . ');
                    $.cartthrobMatrix.togglePackage = function(e){
                        if ($(e).text() == $.cartthrobMatrix.lang.show_package_details) {
                            $(e).html($.cartthrobMatrix.lang.hide_package_details);
                        } else {
                            $(e).html($.cartthrobMatrix.lang.show_package_details);
                        }
                        var next = $(e).parents("tr").next();
                        while(next.hasClass("package")) {
                            next = next.toggle().next();
                        }
                    };
                </script>
            ');

            ee()->javascript->output('
                $("table.cartthrobOrderItems").bind("sortstart", function(e, ui) {
                    var pkg = [];
                    var row = $(ui.item);
                    var next = row.next().next();
                    if (next.hasClass("packageHeader")) {
                        pkg.push(next);
                        next = next.hide().next();
                        while(next.hasClass("package")) {
                            pkg.push(next);
                            next = next.hide().next();
                        }
                    }
                    $(e.target).sortable("option", "package", pkg);
                }).bind("sortstop", function(e, ui) {
                    var current = $(ui.item);
                    $.each($(e.target).sortable("option", "package"), function(i, row) {
                        row.insertAfter(current);
                        if (row.hasClass("packageHeader")) {
                            row.show();
                        }
                        current = row;
                    });
                });
                $(document).on("change", "input.cartthrobOrderItemsEntryId", function(){
                    $(this).parents("tr").find("a.view_product_button").attr("href", (EE.BASE+"/publish/edit/entry/"+$(this).val()).replace("?S=0", "?").replace(/(S=[\w\d]+)?&D=cp(.*?)$/, "$2&$1"));
                });
            ');
        }

        unloadCartThrobPath();

        return $output;
    }

    /**
     * @param $data
     * @return array|mixed|void
     */
    public function save_settings($data)
    {
        // order items field can't be named items
        if (ee()->input->get_post('field_name') == 'items') {
            return show_error(lang('order_items_field_must_not_be_named_items'));
        }

        return parent::save_settings($data);
    }

    /**
     * @param $data
     * @return int|string
     */
    public function save($data)
    {
        ee()->session->cache['cartthrob_order_items'][$this->field_id] = null;

        if (is_array($data)) {
            // if there's just one empty row
            if (count($data) === 1 && count(array_filter(current($data))) === 0) {
                ee()->session->cache['cartthrob_order_items'][$this->field_id] = [];

                return '';
            }

            $data1 = [];
            foreach ($data as $key => $row) {
                if (is_numeric($key)) {
                    $data1[$key] = $row;
                }
            }

            $data = $data1;

            ee()->session->cache['cartthrob_order_items'][$this->field_id] = $data;

            return 1;
        }

        return '';
    }

    /**
     * @param $data
     */
    public function post_save($data)
    {
        // if it's not set, it means save() was never called, which means it's most likely a channel_form not currently editing this field
        if (isset(ee()->session->cache['cartthrob_order_items'][$this->field_id])) {
            $data = ee()->session->cache['cartthrob_order_items'][$this->field_id];

            unset(ee()->session->cache['cartthrob_order_items'][$this->field_id]);

            loadCartThrobPath();

            ee()->load->model('order_model');

            ee()->order_model->updateOrderItems($this->content_id, $data);
            unloadCartThrobPath();
        }
    }

    /**
     * @param $entry_ids
     */
    public function delete($entry_ids)
    {
        loadCartThrobPath();

        ee()->load->model('order_model');

        ee()->order_model->delete_order_items($entry_ids);

        unloadCartThrobPath();
    }

    /**
     * @param $name
     * @param $value
     * @param $row
     * @param $index
     * @param bool $blank
     * @return string
     */
    public function display_field_entry_id($name, $value, $row, $index, $blank = false)
    {
        return form_input([
            'name' => $name,
            'value' => $value,
            'class' => 'cartthrobOrderItemsEntryId',
        ]);
    }

    /**
     * @param $name
     * @param $value
     * @param $row
     * @param $index
     * @param bool $blank
     * @return string
     */
    public function display_field_view_product_button($name, $value, $row, $index, $blank = false)
    {
        ee()->load->helper('html');

        return anchor(ee('CP/URL')->make('publish/edit/entry/' . $row['entry_id']), lang('view'),
            'target="_blank" class="view_product_button"');
    }

    /**
     * @param $data
     * @param bool $replace_tag
     */
    protected function compile_table_rows($data, $replace_tag = false)
    {
        $this->table_rows = [];

        foreach ($data as $rowId => $row) {
            $this->table_rows[] = $this->compileTableRow($row, $rowId, $replace_tag);

            if (isset($this->subItems[$rowId])) {
                $this->table_rows[] = [
                    'data' => [
                        [
                            'data' => $this->js_anchor(lang('hide_package_details'),
                                '$.cartthrobMatrix.togglePackage(this);'),
                            'class' => 'center',
                        ],
                    ],
                    'class' => 'notSortable packageHeader',
                ];

                foreach ($this->subItems[$rowId] as $i => $subItem) {
                    $this->table_rows[] = $this->compileTableRow(
                        $subItem,
                        $rowId . ':' . $i,
                        $replace_tag,
                        $sortable = false,
                        $removable = false,
                        $class = 'package js_hide'
                    );
                }
            }
        }
    }

    /**
     * @param $replace_tag
     * @return array
     */
    protected function view_vars($replace_tag)
    {
        $vars = parent::view_vars($replace_tag);

        foreach ($vars['table_headers'] as $key => $header) {
            if ($key === $this->variable_prefix . 'view_product_button') {
                $vars['table_headers'][$key] = '';
            }
        }

        return $vars;
    }

    /**
     * @param $header
     * @return bool
     */
    protected function is_column_removable($header)
    {
        if ($header === $this->variable_prefix . 'view_product_button') {
            return false;
        }

        return !isset($this->default_row[$header]);
    }

    /**
     * @param $data
     */
    protected function compile_headers($data)
    {
        $this->headers = array_keys($this->default_row);

        foreach ($data as $rowId => $row) {
            foreach ($row as $key => $value) {
                if (!in_array($key, $this->headers) && !in_array($key, $this->hiddenColumns)) {
                    $this->headers[] = $key;
                }
            }

            if (isset($this->subItems[$rowId])) {
                foreach ($this->subItems[$rowId] as $sub_item) {
                    foreach (array_keys($sub_item) as $key) {
                        if (!in_array($key, $this->headers) && !in_array($key, $this->hiddenColumns)) {
                            $this->headers[] = $key;
                        }
                    }
                }
            }
        }

        if (REQ === 'CP') {
            $this->headers[] = 'view_product_button';
        }
    }

    /**
     * @return int
     */
    private function getEntryId()
    {
        $entryId = object_get($this, 'row.entry_id');

        if (!$entryId && isset($this->row)) {
            $entryId = Arr::get($this->row, 'entry_id');
        }

        return $entryId;
    }
}
