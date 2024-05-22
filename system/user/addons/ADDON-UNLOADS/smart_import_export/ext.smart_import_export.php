<?php if (! defined('APP_VER')) exit('No direct script access allowed');

class smart_import_export_ext {

    var $name       	=  ZEAL_SIE_NAME;
    var $module_name    =  ZEAL_SIE_MOD_NAME;
    var $version        =  ZEAL_SIE_VER;

    function __construct($settings = array())
	{
		// Make a local reference to the ExpressionEngine super object
		$this->settings = $settings;
		/* Load language file */
        ee()->lang->loadfile('smart_import_export');
    
	}

    /**
	 * Activate Extension
	 */
	function activate_extension()
	{
		// -------------------------------------------
		//  Add the extension hooks
		// -------------------------------------------
		$hooks = array(
			'core_boot',
		);

		foreach($hooks as $hook)
		{
			ee()->db->insert('extensions', array(
				'class'    => get_class($this),
				'method'   => $hook,
				'hook'     => $hook,
				'settings' => '',
				'priority' => 10,
				'version'  => $this->version,
				'enabled'  => 'y'
			));
		}
	}

    public function core_boot() {
		if(REQ=='CP' && ee()->uri->uri_string!='cp/login'){
            if(ee()->db->table_exists("zeal_subscription")) {
                
                $query = ee()->db->query("SELECT * from ".ee()->db->dbprefix."zeal_subscription where `addon_name` = '".$this->module_name."'");

                $subscription_api_url = "https://www.zealousweb.com/store/webapi/v1/license/verify";
                $sku = 'zosie';

                if($query->num_rows() == 1) {
                    
                    foreach ($query->result_array() as $row)
                    {   
                        if(isset($row['api_response']) && $row['api_response'] == 1) {
                            $diff = strtotime(date('Y-m-d H:i:s')) - $row['last_api_call_date'];
                            if ($diff >= 86400) {
                                $headers = array(
                                    'X-Requested-With: XMLHttpRequest'
                                );
                                $curl = curl_init();
                                curl_setopt($curl, CURLOPT_URL, $subscription_api_url);
                                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                                $data = array(
                                    'api_key' => $row['subscription_key'],
                                    'sku' => $sku
                                );
                                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                                curl_setopt($curl,CURLOPT_HTTPHEADER, $headers); 
                                $output = unserialize(curl_exec($curl));
                                $api_response = '';
                                if($output->error == 'valid') {
                                    $api_response = '1';
                                }
                                if($output->error == 'expired') {
                                    $api_response = '0';
                                }
                                curl_close($curl);
                                if($output->error == 'expired'){
                                    if (ee()->db->simple_query("UPDATE `".ee()->db->dbprefix."zeal_subscription` SET api_response = $api_response, last_api_call_date = ".strtotime(date('Y-m-d H:i:s'))." where addon_name = '".$this->module_name."'"))
                                    {
                                        ee('CP/Alert')->makeBanner('smart_import_export')->asIssue()->withTitle('<a href="'.ee('CP/URL')->make('/addons/settings/smart_import_export/subscription').'">'.$this->name.'</a>' . ' ' . lang('subscription_key_expired'))->addToBody(lang('subscription_key_expired_desc'))->canClose()->now();
                                    }
                                }
                                if($output->error == 'valid'){
                                    ee()->db->simple_query("UPDATE `".ee()->db->dbprefix."zeal_subscription` SET api_response = $api_response, last_api_call_date = ".strtotime(date('Y-m-d H:i:s'))." where addon_name = '".$this->module_name."'");
                                }
                            } 
                        } 
                        else {
                            $headers = array(
                                'X-Requested-With: XMLHttpRequest'
                            );
                            $curl = curl_init();
                            curl_setopt($curl, CURLOPT_URL, $subscription_api_url);
                            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                            $data = array(
                                'api_key' => $row['subscription_key'],
                                'sku' => $sku
                            );
                            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                            curl_setopt($curl,CURLOPT_HTTPHEADER, $headers); 
                            $output = unserialize(curl_exec($curl));
                            $api_response = '';
                            if($output->error == 'valid') {
                                $api_response = '1';
                            }
                            if($output->error == 'expired') {
                                $api_response = '0';
                            }
                            curl_close($curl);
                            if($output->error == 'expired') {
                                if (ee()->db->simple_query("UPDATE `".ee()->db->dbprefix."zeal_subscription` SET last_api_call_date = ".strtotime(date('Y-m-d H:i:s'))." where addon_name = '".$this->module_name."'"))
                                {   
                                    ee('CP/Alert')->makeBanner('smart_import_export')->asIssue()->withTitle('<a href="'.ee('CP/URL')->make('/addons/settings/smart_import_export/subscription').'">'.$this->name.'</a>' . ' ' . lang('subscription_key_expired'))->addToBody(lang('subscription_key_expired_desc'))->canClose()->now();
                                }
                            }
                            if($output->error == 'valid') {
                                ee()->db->simple_query("UPDATE `".ee()->db->dbprefix."zeal_subscription` SET api_response = $api_response, last_api_call_date = ".strtotime(date('Y-m-d H:i:s'))." where addon_name = '".$this->module_name."'");
                            }
                        }
                    }
                } 
            }
        }
    }

}