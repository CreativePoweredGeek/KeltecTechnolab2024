<?php

namespace CartThrob\Controllers\Cp\Routes;

use CartThrob\Controllers\Cp\AbstractRoute;

class PackageFilter extends AbstractRoute
{
    /**
     * @var string
     */
    protected $route_path = 'package_filter';

    public function process($id = false): AbstractRoute
    {
        if (!AJAX_REQUEST) {
            show_error(ee()->lang->line('unauthorized_access'));
        }

        $channels = ee()->config->item('cartthrob:product_channels');

        ee()->load->model('search_model');

        if (ee()->input->get_post('channel_id') && ee()->input->get_post('channel_id') != 'null') {
            $channels = ee()->input->get_post('channel_id');
        }

        $keywords = ee()->input->get_post('keywords');

        ee()->load->model('cartthrob_entries_model');

        // typed in an entry_id
        if (is_numeric($keywords)) {
            $entry = [];

            if ($entry = ee()->cartthrob_entries_model->entry($keywords)) {
                $entries[] = $entry;
            }

            $entries = $entry;
        } else {
            ee()->load->helper('text');

            /** @var CI_DB_result $query */
            $query = ee()->db
                ->select('entry_id')
                ->distinct()
                ->from('exp_channel_titles')
                ->where('site_id', ee()->config->item('site_id'))
                ->where_in('channel_id', $channels)
                ->get();

            ee()->load->library('data_filter');

            $entryIds = ee()->data_filter->key_values($query->result_array(), 'entry_id');
            $entries = ee()->cartthrob_entries_model->entries($entryIds);
        }

        ee()->load->model(['product_model', 'cartthrob_field_model']);

        foreach ($entries as &$entry) {
            $entry['price_modifiers'] = ee()->product_model->get_all_price_modifiers($entry['entry_id']);

            foreach ($entry['price_modifiers'] as $price_modifier => $options) {
                $entry['price_modifiers'][$price_modifier]['label'] = ee()->cartthrob_field_model->get_field_label(ee()->cartthrob_field_model->get_field_id($price_modifier));
            }
        }

        ee()->output->send_ajax_response([
            'entries' => $entries,
            'id' => ee()->input->get_post('filter_id'),
        ]);

        exit;
    }
}
