<?php
class Keltec
{
    public $return_data = '';

    public function __construct()
    {
		//$parameter = ee()->TMPL->fetch_param('type');
		$this->return_data = ee()->TMPL->tagdata;
	}

  	public function cleandescription ()
	{
		//This script attempts to scrub the description of a product for a cleaner display
		$item = trim(ee()->TMPL->tagdata);
    	if($item != "")
		{
			$list = explode('^', $item);
			if (isset($list[1])) {
				$data = trim($list[1]);
			} else {
				$data = $item;
			}
		}
		return $data;
	}

	public function get_item_value()
	{
		
		//{exp:keltec:get_item_value field="" entry_id=""}
		$field_id = $this->get_field_id(ee()->TMPL->fetch_param('field'));
		$entry_id=ee()->TMPL->fetch_param('entry_id');
		$table = 'exp_channel_data_field_'.$field_id;
		$field = 'field_id_'.$field_id;
		$sql = "SELECT * FROM $table WHERE entry_id = ?";
	    $query = ee()->db->query($sql, array($entry_id));
	    $row = $query->row();

	    return $row->$field;
	}

	public function inventoryLessIntransit()
	{
		$val1 = ee()->TMPL->fetch_param('val1');
		$val2 = ee()->TMPL->fetch_param('val2');
		$val_a = (int)$val1; 
		$val_b = (int)$val2; 
		
		return $val_a-$val_b;
	}

	public function catbyclass()
	{
		$itemClass = ee()->TMPL->fetch_param('class');
		$seperatorArray = array('AS','CS','SEP-PROD','SEP-PURCH');
		$refSeperatorArray = array('KG','REF');
		$afArray = array('KA-PROD','KA-PURCH','KC-PROD','KC-PURCH','KS-PROD','KS-PURCH');
		$cfArray = array('CAH-PURCH','KP','WS','KF-PURCH','KIT-PURCH');


		if(in_array($itemClass, $seperatorArray)) {
			$a='Seperator';
		} elseif(in_array($itemClass, $refSeperatorArray)) {
			$a='Refrigeration Separator';
		} elseif(in_array($itemClass, $afArray)) {
			$a='Air Filter';
		} elseif(in_array($itemClass, $cfArray)) {
			$a='Coalescing Filters & Housings';
		} elseif($itemClass == 'OI') {
			$a='Oil Filter';
		} elseif($itemClass == '06') {
			$a='Compressor Oil/Lube';
		} elseif($itemClass == 'WS') {
			$a='Water Separator Bags';
		} elseif($itemClass == 'DC') {
			$a='Desiccant';
		} elseif($itemClass == 'DR') {
			$a='Dryers';
		} elseif($itemClass == 'MF') {
			$a='Mufflers';
		} elseif($itemClass == 'FM') {
			$a='Filter Mats/Pads';
		} elseif($itemClass == 'PF') {
			$a='Panel Filters';
		} else {
			$a='';
		}

		return $a;
	}

	function check_user_logged_in()
	{
		//{exp:keltec:check_user_logged_in}{logged_in_member_id}{/exp:keltec:check_user_logged_in}
		$user = ee()->TMPL->tagdata;
		
		$sql = "SELECT * FROM exp_sessions WHERE member_id = ?";
		$query = ee()->db->query($sql, array($user));

			if($query->num_rows() > 0) {
				$row = $query->row();
				$message =  '<div class="alert alert-warning">There are currently '.$query->num_rows().' people logged in under this id.</div>'  ;
			} else {
				$message = "";
			}
			return $message;
	}

	private function get_field_id($channel_shortname)
	{
		$shortname = $channel_shortname;
		$sql="SELECT field_id FROM exp_channel_fields WHERE field_name = ?";
		$query= ee()->db->query($sql, array($channel_shortname));
		$row = $query->row();
		return $row->field_id;
	}

	public function increment_sold_quantity_field()
	{
		$field_id = $this->get_field_id('total_sold'); // get the id of the field
		$table = 'exp_channel_data_field_'.$field_id;
		$field = 'field_id_'.$field_id;
		$entry = ee()->TMPL->fetch_param('entry_id');
		$qty = ee()->TMPL->fetch_param('qty');
		//
		$sql="SELECT $field FROM $table WHERE entry_id = ? LIMIT 1";
		$query = ee()->db->query($sql, array($entry));
		if ($query->num_rows()==0) { 
			$total_sold = $qty;
			
			$data = array(
				$field => $total_sold,
				'entry_id' => $entry
			);
			ee()->db->insert($table, $data);
			
		 } else {
			$row = $query->row();
			$total_sold = $row->$field;
			$total_sold = ($total_sold + $qty);

			$data = array(
				$field => $total_sold
			);
		
			ee()->db->where('entry_id', $entry);
			ee()->db->update($table, $data);
		 }


	}

}
?>