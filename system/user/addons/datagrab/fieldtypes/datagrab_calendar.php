<?php

/**
 * DataGrab Calendar fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_calendar extends AbstractFieldType
{
    public function register_setting(string $field_name): array
    {
        return [
            $field_name . "_calendar_start_time",
            $field_name . "_calendar_end_time",
            $field_name . "_calendar_field"
        ];
    }

    public function display_configuration(Datagrab_model $DG, string $fieldName, string $fieldLabel, string $fieldType, bool $fieldRequired = false, array $data = []): array
    {
        $config = [];

        ee()->db->select("id as calendar_id, name as title");
        ee()->db->from("exp_calendar_calendars");
        $query = ee()->db->get();
        $calendars = [];
        foreach ($query->result_array() as $row) {
            $calendars[$row["calendar_id"]] = $row["title"];
        }

        $config["label"] = form_label($fieldLabel);
        if ($fieldRequired) {
            $config["label"] .= ' <span class="datagrab_required">*</span>';
        }
        $config["label"] .= '<div class="datagrab_subtext">' . $fieldType . "</div>";

        //  . NBS . anchor("http://brandnewbox.co.uk/support/details/importing_into_calendar_fields_with_datagrab", "(?)", 'class="datagrab_help"');

        $config["value"] =
            "<p>Start time: " . NBS .
            form_dropdown(
                $fieldName . "_calendar_start_time",
                $data["data_fields"],
                isset($data["default_settings"]["cf"][$fieldName . "_calendar_start_time"]) ?
                    $data["default_settings"]["cf"][$fieldName . "_calendar_start_time"] : ''
            ) . NBS . "</p>"
            . "<p>End time: " . NBS
            . form_dropdown(
                $fieldName . "_calendar_end_time",
                $data["data_fields"],
                isset($data["default_settings"]["cf"][$fieldName . "_calendar_end_time"]) ?
                    $data["default_settings"]["cf"][$fieldName . "_calendar_end_time"] : ''
            )
            . "</p><p>Add to calendar: " . NBS .
            form_dropdown(
                $fieldName,
                $calendars,
                (isset($data["default_settings"]["cf"][$fieldName]) ?
                    $data["default_settings"]["cf"][$fieldName] : '')
            ) . "</p>";

        return $config;
    }

    public function prepare_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        // $data[ "field_id_" . $field_id ] = $DG->settings["cf"][ $field ];

        /* [field_id_33] => Array
        (
            [calendar_id] => 1
            [start_day] => 11/09/2017
            [start_time] => 12:00 am
            [all_day] =>
            [end_day] => 11/09/2017
            [end_time] => 2:00 am
            [repeats] =>
            [interval] => 1
            [freq] => daily
            [monthly] => Array
                (
                    [bymonthdayorbyday] => bymonthday
                    [bydayinterval] => 1
                )

            [yearly] => Array
                (
                    [bydayinterval] => 1
                )

            [until] =>
        )*/

        if ($DG->settings["cf"][$fieldName . "_calendar_start_time"])
        {
            $timestamp = $DG->parseDate($DG->dataType->get_item($item, $DG->settings["cf"][$fieldName . "_calendar_start_time"]));
            $start_time = date("h:i a", $timestamp);
            $start_date = date("d/m/Y", $timestamp);

            $timestamp = $DG->parseDate($DG->dataType->get_item($item, $DG->settings["cf"][$fieldName . "_calendar_end_time"]));
            $end_time = date("h:i a", $timestamp);
            $end_date = date("d/m/Y", $timestamp);

            $data["field_id_" . $fieldId] = array(
                "calendar_id" => $DG->settings["cf"][$fieldName],
                "start_day" => $start_date,
                "start_time" => $start_time,
                "all_day" => "",
                "end_day" => $end_date,
                "end_time" => $end_time,
                "repeats" => "",
                "interval" => 1,
                "freq" => "daily",
                "until" => ""
            );

            $_POST["field_id_" . $fieldId] = $data["field_id_" . $fieldId];
        }

        // $post["interval"] = "select_dates";
        // $post["calendar_id"] = $data[ "field_id_" . $field_id ];
        // $post["calendar_calendar_id"] = $data[ "field_id_" . $field_id ];
        // $post["type"] = "+";

        // $post["ampm"] = "pm";
        // $post["rule_id"] = array( "0" );
        // $post["type"] = "+";
        // $post["start_time"] = array();
        // $post["end_time"] = array();
        // $post["all_day"] = array( "" );
        // $post["rule_type"] = array( "+" );

        // $post["occurrences"] = array(
        // 	"date" => array(),
        // 	"start_time" => array(),
        // 	"end_time" => array(),
        // 	"all_day" => array(),
        // 	"rule_type" => array(),
        // );

        // $start_field = $DG->settings["cf"][$field . "_calendar_start_time"];
        // $first = true;
        // if( $DG->datatype->initialise_sub_item(
        // 	$item, $start_field, $DG->settings, $field ) ) {

        // 	while( $subitem = $DG->datatype->get_sub_item(
        // 		$item, $start_field, $DG->settings, $field ) ) {

        // 			$timestamp = $DG->_parse_date( $subitem );
        // 			$start_time = date("Hi", $timestamp);
        // 			$start_date = date("Ymd", $timestamp);

        // 			if( $first ) {

        // 				$post["ampm"] = "pm";
        // 				$post["start_time"] = array( $start_time );
        // 				$post["start_date"] = array( $start_date );
        // 				$post["all_day"] = array( "" );
        // 				$post["rule_type"] = array( "+" );

        // 				$first = false;
        // 			}

        // 			$post["occurrences"]["date"][] = $start_date;
        // 			$post["occurrences"]["start_time"][] = $start_time;
        // 			$post["occurrences"]["all_day"][] = "";
        // 			$post["occurrences"]["rule_type"][] = "+";


        // 	}
        // }

        // $end_field = $DG->settings["cf"][$field . "_calendar_end_time"];

        // if( $DG->datatype->initialise_sub_item(
        // 	$item, $end_field, $DG->settings, $field ) ) {

        // 	$first = true;

        // 	while( $subitem = $DG->datatype->get_sub_item(
        // 		$item, $end_field, $DG->settings, $field ) ) {

        // 			$timestamp = $DG->_parse_date( $subitem );
        // 			$end_time = date("Hi", $timestamp);
        // 			$end_date = date("Ymd", $timestamp);

        // 			if( $first ) {

        // 				$post["end_time"] = array( $end_time );
        // 				$post["end_date"] = array( $end_date );

        // 				$first = false;
        // 			}

        // 			$post["occurrences"]["end_time"][] = $end_time;

        // 	}
        // }

        // $_POST = array_merge( $_POST, $post );

        //print_r( $data ); exit;

    }
}
