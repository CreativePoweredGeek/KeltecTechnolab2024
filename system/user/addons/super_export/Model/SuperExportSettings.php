<?php
namespace Mufi\Addons\SuperExport\Model;
use EllisLab\ExpressionEngine\Service\Model\Model;

class SuperExportSettings extends Model
{

	protected static $_primary_key = 'id';
	protected static $_table_name  = 'super_export_settings';

	protected $id;
	protected $site_id;
	protected $relationships_key;
	protected $encode;
	protected $date_format;
	protected $encode_html;
	protected $csv_export_key;
	protected $csv_separator_s_array;
	protected $csv_separator_m_array;
	protected $xml_root_name;
	protected $xml_element_name;
	protected $ob_clean;
	protected $ob_start;

	protected static $_validation_rules = array(
		'site_id' 				=> 'required|isNatural',
		'relationships_key' 	=> 'required|enum[entry_id,title,url_title]',
		'encode' 				=> 'required|enum[no,encode,decode]',
		// 'date_format' 			=> 'required',
		'encode_html' 			=> 'required|enum[0,1]',
		'csv_export_key' 		=> 'required|enum[field_name,field_label]',
		'csv_separator_s_array' => 'required',
		'csv_separator_m_array' => 'required|enum[json,serialize,json_base64,serialize_base64]',
		'xml_root_name'			=> 'required',
		'xml_element_name'		=> 'required',
	);

}