<?php

namespace CartThrob\Tags;

use Cartthrob_item;
use EE_Session;

class CartItemsInfoTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        loadCartThrobPath();

        ee()->load->library(['number', 'typography', 'api', 'data_filter']);
        ee()->load->model(['product_model', 'cartthrob_field_model', 'subscription_model']);
        ee()->load->helper('array');
    }

    /**
     * Print out cart contents
     */
    public function process()
    {
        ee()->legacy_api->instantiate('channel_fields');

        $packageTagdata = [];
        $globalVars = $this->globalVariables();
        $entryIds = $this->explodeParam('entry_id', $default = []);
        $rowIds = $this->explodeParam('row_id', $default = []);
        $planIds = $this->explodeParam('plan_id', $default = []);
        $categories = strpos($this->tagdata(), '{categories') !== false ? ee()->product_model->get_categories() : false;

        if ($categories) {
            ee()->cartthrob_entries_model->load_categories_by_entry_id(ee()->cartthrob->cart->product_ids());
        }

        if (preg_match_all('#{packages?(.*?)}(.*?){/packages?}#s', $this->tagdata(), $matches)) {
            foreach ($matches[0] as $i => $full_match) {
                $packageTagdata[substr($full_match, 1, -1)] = $matches[2][$i];
            }
        }

        $items = collect(ee()->cartthrob->cart->items())
            ->filter(function (Cartthrob_item $item) use ($entryIds) {
                if (empty($entryIds)) {
                    return true;
                }

                return in_array($item->product_id(), $entryIds);
            })
            ->filter(function (Cartthrob_item $item) use ($rowIds) {
                if (empty($rowIds)) {
                    return true;
                }

                return in_array($item->row_id(), $rowIds);
            })
            ->filter(function (Cartthrob_item $item) use ($planIds) {
                if (empty($planIds)) {
                    return true;
                }

                return in_array($item->meta('plan_id'), $planIds);
            })
            ->map(function (Cartthrob_item $item) use ($globalVars, $packageTagdata) {
                $row = $this->itemVars($item, $globalVars);

                foreach ($packageTagdata as $fullPath => $tagData) {
                    $row[$fullPath] = '';

                    foreach ($this->subitemVars($item, $globalVars, $tagData) as $sub_row) {
                        $row[$fullPath] .= $this->parseVariables([$sub_row], $tagData);
                    }
                }

                foreach (ee()->subscription_model->option_keys() as $v) {
                    $row['subscription_' . $v] = $item->meta('subscription_options') ? element($v, $item->meta('subscription_options')) : null;
                }

                $row['is_subscription'] = ($item->meta('subscription')) ? 1 : 0;
                $row['is_package'] = ($item->sub_items()) ? 1 : 0;
                $row['item_options'] = $item->item_options() ? count($item->item_options()) : 0;

                $row['selected_options'] = $item->item_options();

                return $row;
            })
            ->sortBy(function ($item) {
                return $item[$this->param('orderby', 'title')];
            }, SORT_REGULAR, $this->param('sort', 'asc') === 'desc')
            ->slice($this->param('offset', 0), $this->param('limit', PHP_INT_MAX))
            ->toArray();

        ee()->template_helper->apply_search_filters($items);

        if (count($items) < 1) {
            return $this->noResults('no_items');
        }

        $count = 1;
        foreach ($items as &$row) {
            $row['cart_count'] = $count;
            $row['cart_total_results'] = count($items);
            $row['first_row'] = ($count === 1);
            $row['last_row'] = ($count === $row['cart_total_results']);

            $count++;
        }

        $items = array_merge($items);

        return ee()->template_helper->parse_files($this->parseVariables($items));
    }
}
