<?php
class KT_exports
{
    // This Plugin creates various Client Exports


    public $return_data = '';

    public function __construct()
    {
		//$parameter = ee()->TMPL->fetch_param('type');
		$this->return_data = ee()->TMPL->tagdata;
	}



// This gets the inventory or each warehouse based on entry_id & warehouse code (MAIN == OH, NC, TX, CA)
	public function warehouseInventory($whse,$productEntryId)
	{
		switch ($whse) {
			case 'OH':
				$whseID = 171;
				$field = "field_id_".$whseID;
				$channel = "exp_channel_data_field_".$whseID;
				break;
			case 'NC':
				$whseID = 173;
				$field = "field_id_".$whseID;
				$channel = "exp_channel_data_field_".$whseID;
				break;
			case 'TX':
				$whseID = 172;
				$field = "field_id_".$whseID;
				$channel = "exp_channel_data_field_".$whseID;
				break;
			case 'CA':
				$whseID = 231;
				$field = "field_id_".$whseID;
				$channel = "exp_channel_data_field_".$whseID;
				break; 
		}

		$sql = "SELECT $field FROM $channel WHERE entry_id=?";
		$query = ee()->db->query($sql, array($productEntryId));
		$row = $query->row();
		return $row->$field;
	}
	
//This selects each open Product, then gets the TOTAL inventory and RETURNS it
	public function inventoryTotalList()
	{
		$sql="SELECT entry_id, title FROM exp_channel_titles WHERE channel_id=? AND status=?";
			$query = ee()->db->query($sql, array(37, 'open'));
			$contents="";
			foreach($query->result() as $row) {
				$contents .= $row->title.",".(($this->warehouseInventory('OH',$row->entry_id))+($this->warehouseInventory('NC',$row->entry_id))+($this->warehouseInventory('TX',$row->entry_id))+($this->warehouseInventory('CA',$row->entry_id)))."\r\n";

			
		}
		return $contents;
		//	$file = '../exports/inventory.txt';
			// Open the file to get existing content
			
			// Write the contents back to the file
		//	file_put_contents($file, $contents);
		//	echo 'File Added';
			
	
			
	}
		
	
}
?>