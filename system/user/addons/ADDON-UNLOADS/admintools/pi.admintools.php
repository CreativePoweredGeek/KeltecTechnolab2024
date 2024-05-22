<?php

class Admintools {
    public $return_data = '';
    public function __construct()
    {
        //$parameter = ee()->TMPL->fetch_param('type');
		$this->return_data = ee()->TMPL->tagdata;
        ee()->load->helper('cookie');
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

	public function delete_chnl_data()
    {
		exit(); // Uncommet this to Run
	   //{exp:admintools:delete_chnl_data channel_id="" table_id=""}
		$channel_id = ee()->TMPL->fetch_param('channel_id');
	   	$table_id = ee()->TMPL->fetch_param('table_id');

	    $sql = "SELECT * FROM exp_channel_titles WHERE channel_id = ?";
	    $query = ee()->db->query($sql, array($channel_id));
	    foreach($query->result() as $row) {
		    echo '<p>'.$row->title.' - '.$row->entry_id.'</p>';


			$tables = array(
				'exp_channel_data_field_'.$table_id
				);
			ee()->db->where('entry_id', $row->entry_id);
			ee()->db->delete($tables);

		}


	}

	public function delete_channel_titles()
    {
	   exit();
	   //{exp:admintools:delete_channel_titles channel_id=""}
	   $channel_id = ee()->TMPL->fetch_param('channel_id');

			$tables = array(
				'exp_channel_titles'
				);
			ee()->db->where('channel_id', $channel_id);
			ee()->db->delete($tables);



	}

	public function x_close_products_price_0()
	{
		exit();
		$date = ee()->localize->human_time();
		//$date = ee()->TMPL->fetch_param('date');
		$channel = ee('Model')->get('Channel')->filter('channel_name', 'products')->first();

		$entries = ee('Model')->get('ChannelEntry')
			->filter('channel_id', $channel->channel_id)
			->filter('status', 'open')
			->filter('field_id_3', '0');   // price field

				if($entries != "") {
					//Close those $entries
						$data = array(
							'status' => 'closed',
							'status_id' => '2',
							'edit_date' => $date
						);

						ee()->db->where('entry_id', $entries->entry_id);
						ee()->db->update('exp_channel_titles', $data);
						return 'done';

				} else {}
	}

	public function close_products_price_0()
	{
		//THIS FOR NOW
		//exit();
		$date = ee()->localize->now;
		$sql = "SELECT exp_channel_titles.entry_id AS entry_id
			FROM exp_channel_titles
			JOIN exp_channel_data_field_3
			ON exp_channel_titles.entry_id=exp_channel_data_field_3.entry_id
			WHERE exp_channel_titles.channel_id = ?
			AND exp_channel_titles.status = ?
			AND exp_channel_data_field_3.field_id_3 = ?
			LIMIT 2000";

		$query = ee()->db->query($sql, array('37', 'open', '0'));

		foreach($query->result() as $row)
		{
			$data = array(
				'status' => 'closed',
				'status_id' => '2',
				'edit_date' => $date
			);

			ee()->db->where('entry_id', $row->entry_id);
			ee()->db->update('exp_channel_titles', $data);
			echo $row->entry_id . '<br>';

		}
	}


}
// END Class

// EOF