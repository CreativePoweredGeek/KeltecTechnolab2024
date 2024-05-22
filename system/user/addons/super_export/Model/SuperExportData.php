<?php
namespace Mufi\Addons\SuperExport\Model;
use EllisLab\ExpressionEngine\Service\Model\Model;

class SuperExportData extends Model
{

	protected static $_primary_key = 'id';
	protected static $_table_name  = 'super_export_data';

	protected $id;
	protected $site_id;
	protected $member_id;
	protected $channel_id;
	protected $title;
	protected $created_date;
	protected $last_modified_date;
	protected $counter;
	protected $token;
	protected $format;
	protected $settings;

	protected static $_validation_rules = array(
		'channel_id' 	=> 'required|isNatural',
		'title' 		=> 'required',
		'format' 		=> 'required|enum[csv,xml,json]',
	);

	/*protected static $_events = array('beforeSave');
	public function onBeforeSave()
	{

	}*/

}