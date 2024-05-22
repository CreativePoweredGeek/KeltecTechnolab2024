<?php
class KC_datatools
{
    public $return_data = '';
    
    public function __construct()
    {
		//$parameter = ee()->TMPL->fetch_param('type');
		$this->return_data = ee()->TMPL->tagdata;
	}
	
	
	public function bulk_update_status()
	{
		//Paramaters
		//channel_id - Get the channel ID from the Developers -> Channels
		//status - Options open/closed
		
		//Use: {exp:kcdatatools:bulk_update_status channel_id="" status=""}
		
		$channel_id = ee()->TMPL->fetch_param('channel_id');
		$status = ee()->TMPL->fetch_param('status');
		$date = ee()->TMPL->fetch_param('date');
		if($status == 'open') {
			$status_id = '1';
		} elseif( $status == 'closed') {
			$status_id = '2';
		} else {
			echo 'Currently this only supports open/closed statuses.';
			exit;
		}
		 
		$data = array(
		    'status' => $status,
		    'status_id' => $status_id,
		    'edit_date' => $date
		);
		
		ee()->db->where('channel_id', $channel_id);
		ee()->db->update('exp_channel_titles', $data);
		return 'done';
	}


	
	public function bulk_update_paycode()
	{
		
		$current="1 NET30";
		$sql = "SELECT id, member_id FROM exp_member_data_field_16 WHERE m_field_id_16 = ?";
		$query = ee()->db->query($sql, array($current));
		//$new = ;
		foreach($query->result() as $row) {
			
			$data = array(
				    'm_field_id_16' => 'NET30'
				);
				ee()->db->where('id', $row->id);
				ee()->db->update('exp_member_data_field_16', $data);
		}
	}


}

