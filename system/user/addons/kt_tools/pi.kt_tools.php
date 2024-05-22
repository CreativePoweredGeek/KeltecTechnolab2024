<?php
class KT_tools
{
    public $return_data = '';

    public function __construct()
    {
		//$parameter = ee()->TMPL->fetch_param('type');
		$this->return_data = ee()->TMPL->tagdata;
	}

    public function get_users_csr_email()
    {
	    //{exp:kt_tools:get_users_csr_email member_id=""}
		$member_id = ee()->TMPL->fetch_param('member_id');
	    $sql = "SELECT m_field_id_24 FROM exp_member_data_field_24 WHERE member_id = ?";
	    $query = ee()->db->query($sql, array($member_id));
	    $numrow = $query->num_rows();
	    if($numrow > 0) {
		    $row = $query->row();
		    $csrid = $row->m_field_id_24;
		    $sql = "SELECT email FROM exp_members WHERE username = ?";
		    $query = ee()->db->query($sql, array($csrid));
		    $csrrow= $query->row();
		    $email = $csrrow->email;
		} else {
		    $email = 'ekaiser@keltecinc.com';
	    }
	    return $email;
	 }
	public function get_users_csr_screenname()
    {
	    //{exp:kt_tools:get_users_csr_screenname member_id=""}
		$member_id = ee()->TMPL->fetch_param('member_id');
	    $sql = "SELECT m_field_id_24 FROM exp_member_data_field_24 WHERE member_id = ?";
	    $query = ee()->db->query($sql, array($member_id));
	    $numrow = $query->num_rows();
	    if($numrow > 0) {
		    $row = $query->row();
		    $csrid = $row->m_field_id_24;
		    $sql = "SELECT screen_name FROM exp_members WHERE username = ?";
		    $query = ee()->db->query($sql, array($csrid));
		    $csrrow= $query->row();
		    $name = $csrrow->screen_name;
		} else {
		    $name = 'Ed K.';
	    }
	    return $name;
	 }

	public function get_csr_name_fr_un()
	{
		$un = ee()->TMPL->fetch_param('username');
		$sql = "SELECT member_id FROM exp_members WHERE username = ?";
		$query = ee()->db->query($sql, array($un));
		$row = $query->row();
		$mid = $row->member_id;


		$sql="SELECT m_field_id_1 FROM exp_member_data_field_1 WHERE member_id=?";
	    $query = ee()->db->query($sql, array($mid));
	    $row=$query->row();
	    $fn = trim($row->m_field_id_1);
		$sql="SELECT m_field_id_2 FROM exp_member_data_field_2 WHERE member_id=?";
		   $query = ee()->db->query($sql, array($mid));
		   $row = $query->row();
		   $ln = trim($row->m_field_id_2);
		   $fullname = $fn.' '.$ln;
		   return $fullname;

	}



	 public function get_csr_email_fr_un()
	{
		$un = ee()->TMPL->fetch_param('username');
		$sql = "SELECT email FROM exp_members WHERE username = ?";
		$query = ee()->db->query($sql, array($un));
		$row = $query->row();
		return $row->email;



	}
	public function translate_application_type()
    {

	    //$entry_id = ee()->TMPL->tagdata;
		$string = trim(preg_replace('/\s+/', ' ', ee()->TMPL->tagdata));
		//$channels = ee()->db->select('application_types')


	    $result = ee()->db->select('title')
		->from('exp_channel_titles')
		->where('entry_id', $string);


		$title = $result;

	    $this->return_data = $title;
	}
	public function  create_date()
	{
		$tz = 'EST';
		$timestamp = time();
		$dt = new DateTime("now", new DateTimeZone($tz)); //first argument "must" be a string
		$dt->setTimestamp($timestamp); //adjust the object to correct timestamp
		$date = $dt->format('m/d/Y');
		return $date;
	}
	public function  create_time()
	{
		$tz = 'EST';
		$timestamp = time();
		$dt = new DateTime("now", new DateTimeZone($tz)); //first argument "must" be a string
		$dt->setTimestamp($timestamp); //adjust the object to correct timestamp
		$time = $dt->format('H:i:s');
		return $time;
	}
	public function create_csr_customer_select_list(){
		//the CSR Username as passed in through the 'un' parameter
		$un = ee()->TMPL->fetch_param('un');
		$sel = ee()->TMPL->fetch_param('sel');

		$sql = "SELECT exp_members.member_id, exp_members.username
			FROM exp_members
			JOIN exp_member_data_field_24
			ON exp_members.member_id = exp_member_data_field_24.member_id
			WHERE exp_member_data_field_24.m_field_id_24 = ?
			ORDER BY exp_members.username ASC";

		$query = ee()->db->query($sql, array($un));

		$numrow = $query->num_rows();
	    if($numrow > 0) {
		    $list ='<select name="customer" class="form-control" onchange="if(this.selectedIndex!=0) self.location=this.options[this.selectedIndex].value">';
			$list .='<option value="">---- Select a Customer ----</option>';
			$list .= '<option value="{site_url}CSR/orders/all">Show All</option>';
			foreach($query->result() as $row) {
				$list .= '<option value="{site_url}CSR/orders/'.$row->username.'">'.$row->username.'</option>';
			}
			$list .='</select>';
			$list .='<small id="emailHelp" class="form-text text-muted">Customer count: '.$numrow.'</small>';

		} else {
			 $list='<p>No Customers Assigned.</p>';
		}
		return $list;
	}
	public function item_est_ship_date() //This takes values passed from the Entry
	{
		//Usage:  {exp:kt_tools:item_ship_date itemclass="" iteminventory="" intransitqty="" mfglead="" onorder_po="" onorder_reqdate=""}
		$itemClass = ee()->TMPL->fetch_param('itemclass');
		$itemInventory = ee()->TMPL->fetch_param('iteminventory');
		$inTransitQty = ee()->TMPL->fetch_param('intransitqty');
		$mfgLead = ee()->TMPL->fetch_param('mfglead');
		//---
		$onOrder_po = ee()->TMPL->fetch_param('onorder_po'); // Number
		$onOrder_reqDate = ee()->TMPL->fetch_param('onorder_reqdate'); //Date

		$inventoryArrayA = array("STOCK","MTO");
		$inventoryArrayB =  array("CAH-PURCH","DC","DR","G","HVF","KA‐PURCH","KC‐PURCH","KF‐PURCH","KP","KS‐PURCH","OI","PF","SCK‐PURCH","SEP-PURCH","AS", "SS", "CS","REF","SEP-PROD","KG");
		$today = $this->create_date();
		$tomorrow = date('m/d/Y', strtotime($today.' + 1 days'));
		$currentTime = $this->create_time();
		$mfgLeadTime = date('m/d/Y', strtotime($today.' + '.$mfgLead.' days'));

		if(in_array($itemClass, $inventoryArrayA))
		{
			if($itemInventory > 0)
			{
				if($currentTime < "15:00:00") {
					$shipDate = $today;
				} else {
					$shipDate = $tomorrow;
				}
			}
			else
			{
				$shipDate = $mfgLeadTime;
			}
		}
		elseif(in_array($itemClass, $inventoryArrayB)) {
				if($itemInventory >= 1) {
					if($currentTime < "15:00:00") {
						$shipDate = $today;
					} else {
						$shipDate = $tomorrow;
					}
				} else { // Inventory = 0
					if($inTransitQty >= 1) { //1
						$shipDate = date('m/d/Y', strtotime($today.' + 2 days'));
					}elseif($onOrder_po !='') { //2
						$shipDate = date('m/d/Y', strtotime($onOrder_reqDate.' + 2 days'));
					} else { //3
						if($mfgLead != '') {
							$shipDate = $mfgLeadTime;
						} else {
							$shipDate = 'Contact KELTEC for ship dates. (22)';
						}
					}
				}
		} else {
						$shipDate = 'Contact KELTEC for ship dates. (55)';
		}
			RETURN $shipDate;
	}
	public function inter_shipping_from_today()
	{
		$today = $this->create_date();
		$shipDate = date('m/d/Y', strtotime($today.' + 5 weeks'));
		RETURN $shipDate;
	}
	public function modelTest()
	{

		//You can create and update the Channel Entry using below Model query.
		//For Creating the Entry :-
		$channel = ee('Model')->get('Channel')->filter('channel_name', 'mariel_pages')->first();
		$title_url_title = "Test Entry";
		$entry = ee('Model')->make('ChannelEntry');
		$entry->Channel = $channel;
		$entry->site_id =  ee()->config->item('site_id');
		$entry->author_id = ee()->session->userdata('member_id');
		$entry->ip_address = ee()->session->userdata['ip_address'];
		$entry->versioning_enabled = $channel->enable_versioning;
		$entry->sticky = FALSE;
		$entry->status = 'open';
		$entry->title = $title_url_title;
		$entry->url_title = preg_replace("/[\s_]/", "-", $title_url_title);
		$entry->entry_date = ee()->localize->now;
		$entry->edit_date = ee()->localize->now;
		$entry->save();

		/* Array which contains your Field Data */
		$entry->set($entryFieldArray);

	}

	public function build_inventory_drop_options()
	{
		$optionrow = '';
		$prdct = ee()->TMPL->fetch_param('prdct');
		$whses = ee()->TMPL->fetch_param('whse');
		$users_whse =  ee()->TMPL->fetch_param('userwhse');
		$loc = explode('|', $whses);

		foreach($loc as $whse) {

			switch ($whse) {
				case 'CA':
					$inventoryId = '231';
					$in_transit_qty_id = '253';
				break;
				case 'TX':
					$inventoryId = '172';
					$in_transit_qty_id = '252';
				break;
				case 'NC':
					$inventoryId = '173';
					$in_transit_qty_id = '251';
				break;
				default:
				//main
				$inventoryId = '171';
				$in_transit_qty_id = '187';
			}

			$sql="SELECT entry_id FROM exp_channel_titles WHERE title=? AND channel_id=?";
			$query = ee()->db->query($sql, array($prdct, 37));
			$row = $query->row();

			$inventory_field = 'field_id_'.$inventoryId;
			$intransit_field = 'field_id_'.$in_transit_qty_id;

			$item = ee('Model')->get('ChannelEntry')
					->filter('entry_id', $row->entry_id)
					->fields($inventory_field)
					->fields($intransit_field)
					->first();

			$inventory= $item->$inventory_field;
			$intransit= $item->$intransit_field;
			//$qty = 0;
			if($intransit >= $inventory) {
				$qty = 0;
			} else {
				$qty = ($inventory - $intransit);
			}
			// create the option
			$optionrow .= '<option value="'.$whse.'"';
			if($whse == $users_whse) { $optionrow .= ' selected'; }
			$optionrow .= '>';
			$optionrow .= $qty . ' ' . $whse;
			if($intransit > 0) { $optionrow .= ' ( ' . $intransit . ' )'; }
			$optionrow .= '</option>';
		}
		return $optionrow;

	}

	public function update_price()
	{
		$table='exp_channel_data_field_3';

		$entry = ee()->TMPL->fetch_param('entry_id');
		$price = ee()->TMPL->fetch_param('price');
		$date = ee()->TMPL->fetch_param('date');
		$title = ee()->TMPL->fetch_param('title');

		ee()->db->update(
			$table,
				array(
					'field_id_3'  => $price
				),
				array(
					'entry_id' => $entry
				)
		);
		ee()->db->update(
			'exp_channel_titles',
				array(
					'edit_date'  => $date
				),
				array(
					'title' => $title
				)
		);



	}


	public function get_chnl_data()
    {
	    exit();
	   $channel_id = ee()->TMPL->fetch_param('channel_id');

	    $sql = "SELECT * FROM exp_channel_titles WHERE channel_id = ?";
	    $query = ee()->db->query($sql, array($channel_id));
	    foreach($query->result() as $row) {
		    echo '<p>'.$row->title.' - '.$row->entry_id.'</p>';


			$tables = array(
				'exp_channel_data_field_232'
				);
			ee()->db->where('entry_id', $row->entry_id);
			ee()->db->delete($tables);

		}


	}

	public function delete_channel_titles()
    {
	   exit();
	   $channel_id = ee()->TMPL->fetch_param('channel_id');

			$tables = array(
				'exp_channel_titles'
				);
			ee()->db->where('channel_id', $channel_id);
			ee()->db->delete($tables);



	}

    public function get_original_price()
    {
	    //Access the Product Channel Price field and returns the price based on entry_id
	    $entry_id = ee()->TMPL->fetch_param('entry_id');
		$sql = "SELECT * FROM exp_channel_data_field_3 WHERE entry_id = ?";
	    $query = ee()->db->query($sql, array($entry_id));
	    $row = $query->row();

	    return '$'.number_format($row->field_id_3, 2, '.', '');

    }

	public function reduce_warehouse_inventory()
	{
		//This plugin updates the individual warehouse inventories when an order has been placed.
		//

		//---Parameters---//
		//'quantity' = quantity[{row_id}]
		//'product_id' = entry_id[{row_id}]
		//'warehouse' = item_options[{row_id}][requested_warehouse]


		$quantity =   ee()->TMPL->fetch_param('quantity');
		$product_entry_id = ee()->TMPL->fetch_param('product_id');
		$warehouse = ee()->TMPL->fetch_param('warehouse');

		//internally - Warehouse suffix to field_id map
		if($warehouse == 'MAIN') { $id = 171; }
		if($warehouse == 'NC') { $id = 173; }
		if($warehouse == 'TX') { $id = 172; }
		if($warehouse == 'CA') { $id = 231; }

			$table = 'exp_channel_data_field_'.$id;
			$field = 'field_id_'.$id;

		//First, Get current Quantity
		$sql = "SELECT $field FROM $table WHERE entry_id = ?";
		$query = ee()->db->query($sql, array($product_entry_id));
		$row = $query->row();
		$beginning_inventory = $row->$field;

		if($beginning_inventory >= '1')
		{

			//then do math for reduction
			$reduced = ($beginning_inventory-$quantity);

			//then update the data
			$data = array(
			    $field => $reduced
			);

			ee()->db->update(
				$table,
					array(
						$field => $reduced
					),
					array(
						'entry_id' => $product_entry_id
					)
			);
		} else {}
	}

	public function update_so_remarks()
    {
	   exit();
	   $channel_id = ee()->TMPL->fetch_param('channel_id');

	    $sql = "SELECT * FROM exp_channel_titles WHERE channel_id = ?";
	    $query = ee()->db->query($sql, array($channel_id));
	    foreach($query->result() as $row) {
		    echo '<p>'.$row->title.' - '.$row->entry_id.'</p>';


			$tables = array(
				'exp_channel_data_field_232'
				);
			ee()->db->where('entry_id', $row->entry_id);
			ee()->db->delete($tables);

		}


	}

    public function get_company_name_fr_un()
    {
	    $un=ee()->TMPL->fetch_param('username');
	    $sql="SELECT member_id FROM exp_members WHERE username=?";
	    $query = ee()->db->query($sql, array($un));
	    $row=$query->row();

	    $sql="SELECT m_field_id_4 FROM exp_member_data_field_4 WHERE member_id=?";
	    $query = ee()->db->query($sql, array($row->member_id));
	    $row=$query->row();
	    return $row->m_field_id_4;

    }
    public function get_as_no()
    {

		$ktno = strtoupper(trim(ee()->TMPL->fetch_param('keltec_no')));

		//$sql = "SELECT field_id_220 as asItem
		//FROM exp_channel_data_field_220
		//INNER JOIN exp_channel_data_field_221
		//ON exp_channel_data_field_220.entry_id = exp_channel_data_field_221.entry_id
		//WHERE exp_channel_data_field_221.field_id_221 = ? LIMIT 1";


		//$query = ee()->db->query($sql, array($ktno, $ktno));
		//$row=$query->row();
		//$asno = $row->asItem;
		//if ($asno == $ktno) $asno = 'N/A';

		$channel = ee('Model')->get('Channel')->filter('channel_name', 'oem_lookup')->first();

			$entries = ee('Model')->get('ChannelEntry')
				->filter('channel_id', $channel->channel_id)
				->filter('status', 'open') // Added 'open' specifier 08/17/21
				->filter('field_id_221', $ktno) //customer
				->first();

			if($entries != "") {

				$item = ee('Model')->get('ChannelEntry')
				->filter('entry_id', $entries->entry_id)
				->fields('field_id_220')
				->first();

				$asno = $item->field_id_220;
			} else {
				$asno = "--";
			}
	    return $asno;

    }
	public function get_memberid_fr_un()
	{
		$un = ee()->TMPL->fetch_param('username');
		$sql = "SELECT member_id FROM exp_members WHERE username = ?";
		$query = ee()->db->query($sql, array($un));
		$row=$query->row();
		return $row->member_id;
	}
	public function displayunshipped()
    {
		$items = trim(ee()->TMPL->tagdata);
		$list = explode('|', $items);
		$data = '<p>';
		foreach($list as $item) {
			$newline = explode(':', $item);
			$data .= $newline[0] . ' - Qty: ' . $newline[1] . '<br>';
		}
        $data .= '</p>';
		return $data;
	}

	public function mark_unshipped()
	{
		//{exp:kt_tools:mark_unshipped product="" unshipped=""}
		$unshipped = ee()->TMPL->fetch_param('unshipped');
		$product = trim(ee()->TMPL->fetch_param('product'));
		//if($unshipped == "") {
			//$display = $product;
		//} else {

			$un = array();
			$list = explode('|', $unshipped);
			foreach($list as $item) {
				$no = explode(':', $item);
				$un[] = trim($no[0]);
			}

			if(in_array($product, $un)) {
				$display = '<span class="text-warning"><i class="fas fa-pause-circle"></i></span> ' . $product;
			} else {
				//display as shipped
				$display = '<span class="text-success"><i class="fas fa-check-circle"></i> ' . $product . '</span>';
			}

		//}
		return $display;
	}

	public function cleandescription ()
	{
		$item = trim(ee()->TMPL->tagdata);
		$list = explode('^', $item);
		$data = trim($list[1]);

		return $data;
	}
	public function get_original_price_from_title()
    {
	    //Access the Product Channel Price field and returns the price based on entry_id
	    $product = ee()->TMPL->fetch_param('product');

		$sql="SELECT entry_id FROM exp_channel_titles WHERE title=? AND channel_id=?";
		$query = ee()->db->query($sql, array($product, 37));
	    $row = $query->row();

		$sql = "SELECT * FROM exp_channel_data_field_3 WHERE entry_id = ?";
	    $query = ee()->db->query($sql, array($row->entry_id));
	    $row = $query->row();

	    return '$'.number_format($row->field_id_3, 2, '.', '');

    }

	public function get_entry_value()
	{
		//{exp:kt_tools:get_entry_value entry_id = "" field=""}
	}


	public function get_customernumber()
	{
		$member_id = ee()->session->userdata('member_id');
		$sql="SELECT m_field_id_3 as value FROM exp_member_data_field_3 WHERE member_id = ?";
		$query = ee()->db->query($sql, array($member_id));
		$row=$query->row();
		return $row->value;
	}
	public function get_member_field_value()
	{
		//{exp:kt_tools:get_member_field_value fieldid=""}
		$fieldid = ee()->TMPL->fetch_param('fieldid'); // ID of the member field to collect
		$customer = $this->get_customernumber(); // Customer Number
		//--get the member_id of the master username--//
		$sql = "SELECT member_id FROM exp_members WHERE username = ?";
		$query = ee()->db->query($sql, array($customer));
		$row=$query->row();
		$co_member_id = $row->member_id;

		$this->get_memberid_fr_un($customer);

		//----//
		$sql="SELECT m_field_id_$fieldid as value FROM exp_member_data_field_$fieldid WHERE member_id = ?";
		$query = ee()->db->query($sql, array($co_member_id));
		$row=$query->row();
		return $row->value;
	}


	public function get_member_fieldid($fieldname)
	{
		//This function returns the field_id from the shortname of a member custom field

		$sql="SELECT m_field_id FROM exp_member_fields WHERE m_field_name = ?";
		$query = ee()->db->query($sql, array($fieldname));
		$row=$query->row();
		return $row->m_field_id;
	}

	function check_user_logged_in()
{
	//{exp:kt_tools:check_user_logged_in}{logged_in_member_id}{/exp:kt_tools:check_user_logged_in}
	$user = ee()->TMPL->tagdata;
	
	$sql = "SELECT * FROM exp_sessions WHERE member_id = ?";
	$query = ee()->db->query($sql, array($user));

		if($query->num_rows() > 1) {
			$row = $query->row();
			$message =  '<div class="alert alert-danger text-center p-4"><h4>There are currently '.$query->num_rows().' people logged in under this login.</h4>
			<p>At the current time we are only allowing a single login per customer as it can cause loss of data in your shopping carts. 
			Usually this is caught at login, however, somehow you slipped through the cracks.</p> </div>'  ;
		} else {
			$message = "";
		}
		return $message;
}

}
?>