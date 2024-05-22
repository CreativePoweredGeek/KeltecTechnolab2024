<?php
class Keltec_shipping
{
    public $return_data = '';

    public function __construct()
    {
		//parent::__construct();
		//$parameter = ee()->TMPL->fetch_param('type');
		$this->return_data = ee()->TMPL->tagdata;
		$this->buyerArray = array('STOCK','MTO');
		$this->classArray =  array("CAH-PURCH","DC","DR","G","HVF","KA‐PURCH","KC‐PURCH","KF‐PURCH","KP","KS‐PURCH","OI","PF","SCK‐PURCH","SEP-PURCH","AS", "SS", "CS","REF","SEP-PROD","KG");
		$this->today = $this->create_date();
		$this->tomorrow = date('m/d/Y', strtotime($this->today.' + 1 days'));
		$this->currentTime = $this->create_time();
		//$this->currentTime = '17:00:00'; //FOR TESTING ONLY

		// sets our plugin parameters
		$this->warehouse = ee()->TMPL->fetch_param('warehouse');
		$this->entry_id = ee()->TMPL->fetch_param('entry_id');
		$this->display = ee()->TMPL->fetch_param('display');
		//$this->qty = ee()->TMPL->fetch_param('qty');
		//$this->billing_country = ee()->TMPL->fetch_param('country');
	}



	public function estimated_date_alt()
	{
		$this->today = ee()->localize->human_time();
		//$this->tomorrow = strtotime($this->today.' + 1 days');
		//echo $this->warehouse;
		//use: {exp:keltec_shipping:estimated_date warehouse="" entry_id = ""}
		//This switch statement defines the field ID for these items
		$whse = trim($this->warehouse);
		switch ($whse) {
		  case 'CA':
		    $inventoryId = '231';
		    $on_order_po_id = '250';
		    $in_transit_qty_id = '253';
		    $po_req_date = '256';
			$intransit_adjustment = '4'; //number of days

		    break;
		  case 'TX':
		    $inventoryId = '172';
		    $on_order_po_id = '249';
		    $in_transit_qty_id = '252';
		    $po_req_date = '254';
			$intransit_adjustment = '4'; //number of days
		    break;
		  case 'NC':
		    $inventoryId = '173';
		    $on_order_po_id = '248';
		    $in_transit_qty_id = '251';
		    $po_req_date = '255';
			$intransit_adjustment = '4'; //number of days
		    break;
		  default:
		    //main
		    $inventoryId = '171';
		    $on_order_po_id = '189';
		    $in_transit_qty_id = '187';
		    $po_req_date = '246';
			$intransit_adjustment = '4'; //number of days
		}
		$entry_id = $this->entry_id;
		$class = $this->get_product_class($entry_id);
		$buyer = $this->get_buyer($entry_id);
		$onorderpo = $this->get_onorderpo($on_order_po_id, $entry_id);
		$intransit = $this->get_intransitqty($in_transit_qty_id, $entry_id);  // added this on 6/13/21
		$inventory = $this->get_inventory($inventoryId, $entry_id);
		if($inventory > 0) // We have inventory CASE1, CASE4, CASE6
		{
			if($this->currentTime < "15:00:00") {

				//-- In Transit numbers have been baked into the Inventory, however this gives us faulty Ship dates.
				//-- So what we do is check the intransit vs the inventory and add 3 days to the Ship date to allow for
				//-- arrival at the warehouse - Change made 6/13/21

				if($intransit >= $inventory) {
					$shipDate = strtotime($this->today.' + '.$intransit_adjustment.' days');
				} else {
					//$shipDate = strtotime($this->today);// added this on 6/13/21
					$shipDate = strtotime($this->today.' + 1 days'); //changed 1/14/23 per Ed Kaiser
				}

			} else {

				//-- In Transit numbers have been baked into the Inventory, however this gives us faulty Ship dates.
				//-- So what we do is check the intransit vs the inventory and add 3 days to the Ship date to allow for
				//-- arrival at the warehouse - Change made 6/13/21

				if($intransit >= $inventory) {
					$shipDate = strtotime($this->today.' + '.$intransit_adjustment.' days');
				} else {
					//$shipDate = strtotime($this->today.' + 1 days'); // added this on 6/13/21
					$shipDate = strtotime($this->today.' + 2 days'); //changed 1/14/23 per Ed Kaiser
				}

			}
		} else { // if we have no inventory

			if((in_array($class, $this->classArray)) && ($onorderpo > '0'))  //CASE2
			{
				$d = date('m/d/Y', $this->get_po_req_date($po_req_date, $entry_id));
				$shipDate = strtotime($d.' + 2 days');
			} elseif((in_array($class, $this->classArray)) && ($onorderpo == '0'))  //CASE3
			{
				$lead = $this->get_mfg_lead_time($entry_id).' days';
				$shipDate = strtotime($this->today.' +'.$lead);
				//$shipDate = $this->convert_to_weekday($shipDate);

			} elseif(in_array($buyer, $this->buyerArray))  //CASE5 & CASE 7
			{
				$lead = $this->get_mfg_lead_time($entry_id).' days';
				$shipDate = strtotime($this->today.' + '.$lead);
			} else { //CASE8
				//$shipDate = $this->today;
				$shipDate = strtotime($this->today.' + 14 days');
			}

			$member_country = $this->get_member_billing_country();
			$shipDate = $this->international_date_adjustment($member_country, $shipDate);

		}

		// if the shipdate is a saturday or Sunday, this advances it to the next weekday.
		$shipDate = $this->convert_to_weekday($shipDate);

		// if the shipdate is a defined Holiday, this advances it to the next weekday.
		$shipDate = $this->skip_holidays($shipDate);

		// This ensures the Plugin outputs the correct format for either Web Display or Database insertion.
		if($this->display == 'yes') {
			$shipDate = date('m/d/Y', $shipDate);
		} else {
			$shipDate = $shipDate;
		}
		//echo date('m/d/Y', $shipDate);
		return $shipDate;

	} //EOF estimated_date_alt


	public function tracking_links()
	{



		$shipvia = ee()->TMPL->fetch_param('shipvia');
		$trackingnum = ee()->TMPL->fetch_param('tracking');





		if( $trackingnum == '')
		{
			$link = "";
		} else {
			$entryid = $this->get_shipvia_id($shipvia);
			$sql = "SELECT field_id_238 as carrier FROM exp_channel_data_field_238 WHERE entry_id = ?";
			$query = ee()->db->query($sql, array($entryid));
			$row = $query->row();
			$link = '';
			$numbers = explode('|',$trackingnum);
			foreach($numbers as $num){
				$num = trim($num);

				if($row->carrier == "FEDEX")
				{
					$link .= '<a href="https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber='.$num.'" target="_blank"><i class="fas fa-link"></i> '.$num.'</a><br>';
				} elseif($row->carrier == "UPS")
				{
					$link .= '<a href="https://www.ups.com/track?loc=en_US&requester=ST&tracknum='.$num.'" target="_blank"><i class="fas fa-link"></i> '.$num.'</a><br>';

				} else { $link .= "No Link Available"; }
			}
		}
		return $link;
	}

	public function international_date_adjustment($member_country, $shipDate)
	{
		//$member_country = $this->get_member_billing_country();
		//$member_country = 'ARG';
		if($member_country == 'USA')
		{
			$shipDate = $shipDate;

		} elseif($member_country == '') {

			$shipDate = $shipDate;

		}else {

			$tmpDate = date('m/d/Y', $shipDate);
			$shipDate = strtotime($tmpDate.' + 5 weeks');

		}

		return $shipDate;

	}

	public function get_shipvia_id($shipvia)
	{
		$shipvia = trim($shipvia);
		$sql = "SELECT entry_id FROM exp_channel_data_field_237 WHERE field_id_237 = ?";
		$query = ee()->db->query($sql, array($shipvia));
		$row = $query->row();
		$id = $row->entry_id;
		return $id;

	}

	public function skip_holidays_old($shipDate)
	{

		//$sql = "SELECT DATE_FORMAT(field_id_258,'%Y-%m-%d') AS date FROM exp_channel_data_field_258";
		//$query = ee()->db->query($sql);
		//$holidays = array();
		//foreach($query->result as $row) {
			//$holidays = $row->date.",";
		//}

		$shipDateR = date('Y-m-d', $shipDate);
		$holidays=array("2020-11-26","2020-11-27","2020-12-24","2020-12-25","2020-12-26","2020-12-27","2020-12-28","2020-12-29","2020-12-30","2020-12-31","2021-01-01","2021-01-02","2021-01-03");
		if(in_array($shipDateR, $holidays))
		{
			$d = strtotime($shipDateR . '+1 Weekdays');
	    }
	    else
		{
	        $d = $shipDate;
	    }
		return $d;
	}

	public function skip_holidays($shipDate) // updated to use database values 11/13/20
	{
		$shipDateR = date('Y-m-d', $shipDate);

		$sql = "SELECT field_id_258 AS date FROM exp_channel_data_field_258";
		$holidays = ee()->db->query($sql);
		$holidays2[] = "";
		foreach($holidays->result() as $row) { $holidays2[] .= date('Y-m-d', $row->date); }

			do {
				if(in_array($shipDateR, $holidays2)) {
					$shipDate = strtotime($shipDateR . '+1 Weekdays');
					$shipDateR = date('Y-m-d', $shipDate);
				}
			} while (in_array($shipDateR, $holidays2));


		return $shipDate;
	}
	public function convert_to_weekday($dt)
	{
		// if the shipdate is a saturday or Sunday, this advances it to the next weekday.
		$dt2 = date("l", $dt);
		//echo $dt2;
		$dt3 = strtolower($dt2);
		//echo $dt3;
        $mdate = date('Y-m-d', $dt);
        //echo  $mdate;
        if(($dt3 == "saturday" )|| ($dt3 == "sunday"))
			{
	           $d = strtotime($mdate . '+1 Weekday');
	        }
	    else
			{
	            $d = $dt;
	        }
		//echo date('m/d/Y', $d);
		return $d;
	}
	public function get_member_billing_country()
	{
		$member_id = ee()->session->userdata('member_id');
		$sql = "SELECT m_field_id_9 FROM exp_member_data_field_9 WHERE member_id = ?";
		$query = ee()->db->query($sql, array($member_id));

		$row = $query->row();
		$country = $row->m_field_id_9;

		if(($country == "") || ($country == "US"))
		{ $country = 'USA'; }

		return $country;

	}
	public function get_inventory($inventoryId, $entry_id)
	{
		$field = 'field_id_'.$inventoryId;
		$table='exp_channel_data_field_'.$inventoryId;
		$sql = "SELECT $field as inventory FROM $table WHERE entry_id = ?";
		$query = ee()->db->query($sql, array($entry_id));
		$row = $query->row();
		//echo $row->inventory;
		return $row->inventory;
	}
	public function NEW_get_inventory($inventoryId, $entry_id, $qty = NULL)
	{
		// This is to take into account quantity requests that Inventory levels cannot meet.
		$field = 'field_id_'.$inventoryId;
		$table='exp_channel_data_field_'.$inventoryId;
		$sql = "SELECT $field as inventory FROM $table WHERE entry_id = ?";
		$query = ee()->db->query($sql, array($entry_id));
		$row = $query->row();
		if($qty <= $row->inventory) {
			$qty = $row->inventory;
		} else {
			$qty = '0';
		}
		return $qty;
	}
	public function get_onorderpo($on_order_po_id, $entry_id)
	{
		$sql = "SELECT field_id_$on_order_po_id as onorder FROM exp_channel_data_field_$on_order_po_id WHERE entry_id = ?";
		$query = ee()->db->query($sql, array($entry_id));
		$row = $query->row();
		return $row->onorder;
	}
	public function get_intransitqty($in_transit_qty_id, $entry_id)
	{
		$sql = "SELECT field_id_$in_transit_qty_id as qty FROM exp_channel_data_field_$in_transit_qty_id WHERE entry_id = ?";
		$query = ee()->db->query($sql, array($entry_id));
		$row = $query->row();
		return $row->qty;
	}
	public function get_buyer($entry_id)
	{
		$sql = "SELECT field_id_247 as buyer FROM exp_channel_data_field_247 WHERE entry_id = ?";
		$query = ee()->db->query($sql, array($entry_id));
		$row = $query->row();
		//echo $entry_id;
		return $row->buyer;
	}
	public function get_product_class($entry_id)
	{
		$sql = "SELECT field_id_5 FROM exp_channel_data_field_5 WHERE entry_id = ?";
		$query = ee()->db->query($sql, array($entry_id));
		$row = $query->row();
		return $row->field_id_5;
	}
	public function get_mfg_lead_time($entry_id)
	{
		$sql = "SELECT field_id_188 FROM exp_channel_data_field_188 WHERE entry_id = ?";
		$query = ee()->db->query($sql, array($entry_id));
		$row = $query->row();
		// 10-11-20 added this to advance the manufacture lead time to 5 days if the returned value is 0
		if($row->field_id_188 >= '1') {
			$time = $row->field_id_188;
		} else {
			$time = '5';
		}
		//echo $time;
		return $time;
	}
	public function get_po_req_date($po_req_date, $entry_id)
	{
		//$field = 'field_id_'.$po_req_date;
		//$table = 'exp_channel_data_field_'.$po_req_date;
		$sql = "SELECT field_id_$po_req_date as date FROM exp_channel_data_field_$po_req_date WHERE entry_id = ?";
		$query = ee()->db->query($sql, array($entry_id));
		$row = $query->row();
		return $row->date;
	}
	public function create_time()
	{
		$tz = 'America/New_York';
		$timestamp = time();
		$dt = new DateTime("now", new DateTimeZone($tz)); //first argument "must" be a string
		$dt->setTimestamp($timestamp); //adjust the object to correct timestamp
		$time = $dt->format('H:i:s');
		return $time;
	}
	public function create_date()
	{
		$tz = 'EST';
		$timestamp = time();
		$dt = new DateTime("now", new DateTimeZone($tz)); //first argument "must" be a string
		$dt->setTimestamp($timestamp); //adjust the object to correct timestamp
		$date = $dt->format('m/d/Y');
		return $date;
	}
	public function inter_shipping_from_today()
	{
		$today = $this->create_date();
		$shipDate = date('m/d/Y', strtotime($today.' + 5 weeks'));
		RETURN $shipDate;
	}




} // EOC Keltec_shipping

?>