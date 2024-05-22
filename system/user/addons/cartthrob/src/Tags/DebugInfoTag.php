<?php

namespace CartThrob\Tags;

use EE_Session;

class DebugInfoTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('javascript');
        ee()->load->model('cartthrob_field_model');
    }

    /**
     * Outputs all data related to CartThrob
     */
    public function process()
    {
        if (!ee()->cartthrob->store->config('show_debug')) {
            return;
        }

        if (ee()->cartthrob->store->config('show_debug') == 'super_admins' && $this->getGroupId() !== 1) {
            return;
        }

        $debug['session'] = ee()->cartthrob_session->toArray();
        $debug = array_merge($debug, ee()->cartthrob->cart->toArray());

        uksort($debug, 'strnatcasecmp');

        if ($this->param('console')) {
            return '<script type="text/javascript">(function(data) { if (typeof(window.console) == "undefined") return; window.console.log(data) })(' . json_encode($debug) . ')</script>';
        }

        $output = '<fieldset id="ct_debug_info" style="border:1px solid #000;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#ffbc9f ">';
        $output .= '<legend style="color:#000;">&nbsp;&nbsp;' . ee()->lang->line('cartthrob_profiler_data') . '  </legend>';

        $output .= $this->format_debug($debug);

        $output .= '</table>';
        $output .= '</fieldset>';

        return $output;
    }

    /**
     * format_debug
     * Formats debug arrays into tables
     *
     * @param $data
     * @param null $parent_key
     * @return string
     */
    private function format_debug($data, $parent_key = null)
    {
        $output = '';

        if (is_array($data)) {
            uksort($data, 'strnatcasecmp');
            $output = "<table style='width:100%;'>";

            foreach ($data as $key => $value) {
                $content = '';
                $output_key = $key;

                if (is_numeric($key)) {
                    $output_key = 'Row ID: ' . $key;
                }

                if (is_array($value)) {
                    $content .= $this->format_debug($value, $key);
                } else {
                    if ($key == 'inventory' && $value == PHP_INT_MAX) {
                        $value = 'unlimited';
                    }

                    if ($key == 'price') {
                        if ($value == '' && $parent_key !== null) {
                            $item = ee()->cartthrob->cart->item($parent_key);

                            if ($item) {
                                $field_id = ee()->cartthrob->store->config('product_channel_fields', $item->meta('channel_id'), $key);
                                $field_name = 'channel entry';

                                if (ee()->cartthrob->store->config('product_channel_fields', $item->meta('channel_id'), 'global_price')) {
                                    $field_name = 'globally set';
                                } elseif ($field_id) {
                                    $field_name = ee()->cartthrob_field_model->get_field_name($field_id) . ' field';
                                }

                                $value = $item->price() . ' (uses ' . $field_name . ' price)';
                            }
                        } else {
                            $value = $value . ' (uses customer price)';
                        }
                    }

                    if ($key == 'entry_id' && empty($value)) {
                        $value = '(dynamic item)';
                    }

                    if ($value) {
                        $content .= htmlspecialchars($value);
                    }
                }

                $output .= "<tr><td style='padding:5px; vertical-align: top;color:#900;background-color:#ddd;'>" . $output_key . "&nbsp;&nbsp;</td><td style='padding:5px; color:#000;background-color:#ddd;'>" . $content . "</td></tr>\n";
            }

            $output .= '</table>';
        } else {
            $output = htmlspecialchars($data);
        }

        return $output;
    }
}
