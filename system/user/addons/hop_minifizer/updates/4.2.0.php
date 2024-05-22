<?php
    // Sync config from extension settings
    $query = ee()->db->select('settings')
        ->from('extensions')
        ->where(['enabled' => 'y', 'class' => 'Hop_minifizer_ext'])
        ->limit(1)
        ->get();

    if ($query->num_rows() > 0) {
        $settings = unserialize($query->row()->settings);

        foreach ($settings as $setting_name => $setting_value) {
            $setting = ee('Model')->get('hop_minifizer:Config')->filter('setting_name', $setting_name)->first();
            if (empty($setting)) {
                $setting = ee('Model')->make('hop_minifizer:Config', ['setting_name' => $setting_name, 'value' => $setting_value]);
            }

            $setting->value = $setting_value;
            $setting->save();
        }
    }
    $query->free_result();