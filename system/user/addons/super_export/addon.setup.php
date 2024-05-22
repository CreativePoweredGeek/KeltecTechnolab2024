<?php
require PATH_THIRD.'super_export/config.php';

return array(
	'author'      		=>  SUPER_EXPORT_AUTHOR,
	'author_url'  		=>  SUPER_EXPORT_AUTHOR_URL,
	'docs_url'  		=>  SUPER_EXPORT_DOCS_URL,
	'name'        		=>  SUPER_EXPORT_NAME,
	'description' 		=> '',
	'version'     		=>  SUPER_EXPORT_VER,
	'namespace'   		=> 'Mufi\Addons\SuperExport',
	'settings_exist' 	=> TRUE,
	'models' => array(
		'ExportSettings' => 'Model\SuperExportSettings',
		'ExportData' 	 => 'Model\SuperExportData'
	)
);

//EOF