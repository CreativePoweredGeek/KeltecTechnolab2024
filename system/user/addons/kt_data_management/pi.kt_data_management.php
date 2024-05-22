<?php
class KT_data_management {



    public $return_data = '';

    public function __construct()
    {
		//$parameter = ee()->TMPL->fetch_param('type');
		$this->return_data = ee()->TMPL->tagdata;
        //$this->base = 'https://keltecinc.lcl/';
        //$this->today = $this->create_date();
	}

    public function update_product_status()
    {
        
        exit();
        
        
        // Requires parameters:
        // filename= ""
        // status = ""   options: open / closed
        // USE: {exp:kt_data_management:update_list_status status="" filename=""}

        $rawfile = trim(ee()->TMPL->fetch_param('filename'));
        $status = 'closed';
        $product_channel_id = "37";


        //$path = '/Users/russellkern/GitHub/KeltecIncWebsite';
        $path = '/home/keltecinc/public_html';
        $fileName = $path.'/uploads/'.$rawfile;
        //$count = 0;
        if(file_exists($fileName))
        {
            // Find File
            $file = fopen($fileName,'r');

                while(!feof($file))
                {
                    //Loop through each line
				    $name = fgets($file);

                    if(!empty($name)) {

                        $line = explode(',', $name);
                        $product = trim($line[0]);
                        //$status_n = trim($line[1]);
                        //$status_id_n = trim($line[2]);

                        // Get the product's entry ID from exp_channel_titles
                        $sql="SELECT * FROM exp_channel_titles WHERE title = ? AND channel_id = ?";
                        $result = ee()->db->query($sql, array($product, $product_channel_id));

                        if($result->num_rows() == 0) {
                            // If no entry is found, send to the screen
                            $this->logger('Status Update: Product '.$product.' not found.');
                        } else {

                            // Update the status and the Edit Date
                            $row = $result->row();
                            $date = ee()->localize->now;


                                ee()->db->update(
                                    'exp_channel_titles',
                                        array(
                                            'status' => 'closed',
                                            'status_id' => '2',
                                            'edit_date' => $date
                                        ),
                                        array(
                                            'entry_id' => $row->entry_id
                                        )
                                );
                                $this->logger('Status Update: Product '.$product.' status updated to '.$status.'. Filename: '.$rawfile);


                        }
                    }
                }
        } else {
            $this->logger('Status Update: File not Found - Filename: '.$fileName);
        }
    } // close update_product_status()


    private function logger($message)
    {

		$data = array(
	        'action' => $message
		);

		ee()->db->insert('exp_kcd_data_log', $data);

    }



} // Close Class