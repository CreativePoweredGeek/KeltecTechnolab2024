<?php  if(! defined('BASEPATH')) exit('no direct script access allowed');

class Super_export
{

	function __construct()
	{
		ee()->lang->loadfile('super_export');
	}

	function super_export_frontend()
	{

		if(! isset($_GET['id']))
		{
			return ee()->output->show_user_error('general', lang('export_id_not_found'));
		}

		$vars = array(
			'id' 	 => $_GET['id'],
			'type'   => (isset($_GET['type']) && $_GET['type'] == "ajax") ? "ajax" : "normal",
			'limit'  => (isset($_GET['limit'])) ? $_GET['limit'] : 0,
			'offset' => (isset($_GET['offset'])) ? $_GET['offset'] : 0,
		);

		ee()->load->library('Super_export_settings_lib', null, 'exportSettings');
		ee()->load->library('Super_export_download', null, 'exportDownload');
		$data = ee()->exportDownload->process($vars);

		if($vars['offset'] == 0)
		{
			return ee()->load->view('frontend_ajax_export', $data);
		}

		echo json_encode($data);exit();

	}

	function super_export_download()
	{

		if(! (isset($_GET['file']) && $_GET['file'] != ""))
		{
			echo "You must have to specify a file name to download it."; exit();
		}

		$fileurl = rtrim(SYSPATH, "/") . "/user/cache/super_export/" . $_GET['file'];
		if(! file_exists($fileurl))
		{
			echo "File you want to download is not exists. Please run export again to generate related file."; exit();
		}

		// header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' . $_GET['file']);
		readfile($fileurl);exit();
	}

}