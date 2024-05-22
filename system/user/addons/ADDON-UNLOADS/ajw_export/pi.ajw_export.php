<?php

/**
 * Ajw_export Class
 *
* @package		ExpressionEngine
* @category		Plugin
* @author		Andrew Weaver
* @copyright	Copyright (c) 2004 - 2016, Andrew Weaver
* @link			http://brandnewbox.co.uk/products/ajw_export/
* @license		https://opensource.org/licenses/MIT MIT
 */

class Ajw_export {

	public $sql;
	public $channel;
	public $format = 'csv';

	public $filename = "";
	public $newline = "\r\n";

	// For CSV exports
	public $delimiter = ",";
	public $enclosure = '"';

	// For XML exports
	public $root = 'root';
	public $element = 'element';

	public $return_data = '';

	public function __construct()
	{
		$this->sql = ee()->TMPL->fetch_param('sql', '');
		$this->format = ee()->TMPL->fetch_param('format', $this->format);
		$this->filename = ee()->TMPL->fetch_param('filename', $this->filename);
		$this->delimiter = ee()->TMPL->fetch_param('delimiter', $this->delimiter);
		$this->newline = ee()->TMPL->fetch_param('newline', $this->newline);
		$this->root = ee()->TMPL->fetch_param('root', $this->root);
		$this->element = ee()->TMPL->fetch_param('element', $this->element);
		$this->channel = ee()->TMPL->fetch_param("channel", FALSE);

		if( $this->channel !== FALSE )
		{
			// Find field group from channel
			ee()->db->select("channel_id, field_group");
			ee()->db->where("channel_name", $this->channel);
			$query = ee()->db->get("exp_channels");

			if( $query->num_rows() == 0 )
			{
				$this->return_data = "channel does not exist";
				return;
			}

			$row = $query->row_array();
			$field_group = $row["field_group"];
			$channel_id = $row["channel_id"];

			// Get list of field id and names
			$fields = array();
			ee()->db->select("field_id, field_name");
			ee()->db->where("group_id", $field_group);
			$query = ee()->db->get("exp_channel_fields");

			foreach ($query->result_array() as $row)
			{
				$fields[] = "d.field_id_".$row["field_id"]." as ".$row["field_name"];
			}

			// Build SQL for export
			$sql = "SELECT t.*, " . implode(", ", $fields) . " ";
			$sql .= "FROM exp_channel_titles t, exp_channel_data d ";
			$sql .= "WHERE t.entry_id = d.entry_id ";
			$sql .= "AND t.channel_id = \"" . $channel_id . "\"";

			$this->sql = $sql;
		}

		if ($this->sql == '')
		{
			// Error: sql parameter is empty
			$this->return_data = "sql parameter cannot be empty";
			return;

		}

		$query = ee()->db->query($this->sql);

		if ($this->format == "csv")
		{
			ee()->load->dbutil();
			$data = ee()->dbutil->csv_from_result($query, $this->delimiter, $this->newline);
		}

		if ($this->format == "xml")
		{
			ee()->load->dbutil();

			$config = array (
				'root'    => $this->root,
				'element' => $this->element,
				'newline' => $this->newline,
				'tab'    => "\t"
				);

			$data = ee()->dbutil->xml_from_result($query, $config);
		}

		if ($this->filename != "")
		{
			// Write data to file

			ee()->load->helper('download');
			force_download($this->filename, $data);
		}
		else
		{
			// Display data in template
			$this->return_data = $data;
			return;
		}
	}

	// ------------------------------------------------------------------------
}
// END CLASS

// EOF


