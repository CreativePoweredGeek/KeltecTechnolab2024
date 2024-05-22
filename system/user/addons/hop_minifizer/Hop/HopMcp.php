<?php

if (!trait_exists('HopMcp')) {
    trait HopMcp
    {
        /*
         * License setting page
         */
        public function license()
        {
            $this->buildNav();

            $vars = [];
            $vars['action_url'] = ee('CP/URL', 'addons/settings/' . $this->short_name . '/save_license');

            $license_setting = ee('Model')->get($this->short_name . ':Config')->filter('setting_name', 'license')->first();
            $vars['license_key'] = $license_setting->value != 'n/a' ? $license_setting->value : '';
            $vars['license_setting_id'] = !empty($license_setting->setting_id) ? $license_setting->setting_id : '';

            // Check if license is saved as valid
            $vars['license_valid'] = $this->checkLicenseValid();

            $vars['license_agreement'] = 'https://www.hopstudios.com/software/' . $this->short_name . '/license';

            return [
                'heading'       => lang('license'),
                'body'          => ee('View')->make('' . $this->short_name . ':license')->render($vars),
                'breadcrumb'    => [
                    ee('CP/URL', 'addons/settings/' . $this->short_name . '')->compile() => $this->name
                ]
            ];
        }

        /*
         * Save license action
         */
        public function save_license()
        {
            $license_key = ee()->input->post('license_key');
            $license_setting_id = ee()->input->post('license_setting_id');

            if ($license_setting_id) {
                $license_setting = ee('Model')->get($this->short_name . ':Config')->filter('setting_id', $license_setting_id)->first();
            } else {
                $license_setting = ee('Model')->make($this->short_name . ':Config');
            }

            $license_setting->setting_name = 'license';
            $license_setting->value = $license_key;
            $license_setting->save();

            // Check if license is valid from hop license
            $is_valid = $this->checkLicense($license_key);

            if ($is_valid == 'valid') {
                $license_valid = ee('Model')->get($this->short_name . ':Config')->filter('setting_name', 'license_valid')->first();
                if (empty($license_valid)) {
                    $license_valid = ee('Model')->make($this->short_name . ':Config');
                    $license_valid->setting_name = 'license_valid';
                    $license_valid->value = 'valid license';
                    $license_valid->save();
                }
            } else {
                $license_valid = ee('Model')->get($this->short_name . ':Config')->filter('setting_name', 'license_valid')->first();
                if (!empty($license_valid)) {
                    $license_valid->delete();
                }
            }

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/' . $this->short_name . '/license'));
        }

        /*
         * Check if license is valid
         * Connect with hop studios license api
         */
        private function checkLicense($license_key)
        {
            $url = 'https://license.hopstudios.com/check/' . $this->short_name . '/' . $license_key;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($ch);

            // if (!$result) {
            //  echo 'Curl error: ' . curl_error($ch);
            // }
            curl_close($ch);

            return $result;
        }

        /*
         * Quick check in the db to see if license is valid
         */
        private function checkLicenseValid()
        {
            try {
                $license_valid = ee('Model')->get($this->short_name . ':Config')->filter('setting_name', 'license_valid')->first();
                if (!empty($license_valid)) {
                    return $license_valid->value == 'valid license';
                }
            } catch (Exception $e) {
                // Make sure Hop License table is configured properly
                ee('CP/Alert')->makeInline('shared-form')
                    ->asWarning()
                    ->withTitle('Error')
                    ->addToBody('Please update ' . $this->module_name)
                    ->defer();
                ee()->functions->redirect(ee('CP/URL')->make('addons'));
            }
            return false;
        }
    }
}