<?php
    $table_name = 'hop_minifizer_settings';
    if (ee()->db->table_exists($table_name) && ee()->db->field_exists('values', $table_name)) {
        ee()->dbforge->modify_column($table_name, [
            'values' => [
                'name' => 'value',
                'type' => 'TEXT',
            ]
        ]);
    }