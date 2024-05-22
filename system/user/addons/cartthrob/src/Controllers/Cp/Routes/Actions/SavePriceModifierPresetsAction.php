<?php

namespace CartThrob\Controllers\Cp\Routes\Actions;

use CartThrob\Controllers\Cp\AbstractActionRoute;
use CartThrob\Controllers\Cp\AbstractRoute;

class SavePriceModifierPresetsAction extends AbstractActionRoute
{
    /**
     * @var string
     */
    protected $route_path = 'actions/save-price-modifier-presets-action';

    /**
     * @param $id
     * @return AbstractRoute
     */
    public function process($id = false): AbstractRoute
    {
        if (!AJAX_REQUEST) {
            exit;
        }

        if (REQ !== 'CP' && !ee()->security->secure_forms_check(ee()->input->post('csrf_token'))) {
            exit;
        }

        ee()->db
            ->from('cartthrob_settings')
            ->where('`key`', 'price_modifier_presets')
            ->where('site_id', ee()->config->item('site_id'));

        $presets = (ee()->input->post('price_modifier_presets')) ? ee()->input->post('price_modifier_presets', true) : [];
        $value = [];

        foreach ($presets as $preset) {
            if (!is_array($preset['values'])) {
                continue;
            }

            $value[$preset['name']] = $preset['values'];
        }

        $data = [
            'value' => serialize($value),
            'serialized' => 1,
        ];

        if (ee()->db->count_all_results() == 0) {
            $data['site_id'] = ee()->config->item('site_id');
            $data['`key`'] = 'price_modifier_presets';

            ee()->db->insert('cartthrob_settings', $data);
        } else {
            ee()->db->update(
                'cartthrob_settings',
                $data,
                [
                    'site_id' => ee()->config->item('site_id'),
                    '`key`' => 'price_modifier_presets',
                ]
            );
        }

        // forces json output
        ee()->output->send_ajax_response(['CSRF_TOKEN' => ee()->functions->add_form_security_hash('{csrf_token}')]);
        exit;

        return $this;
    }
}
