<?php
class KT_oemlists
{
    public $return_data = '';
    
    public function __construct()
    {

		$parameter = ee()->TMPL->fetch_param('type');
		$this->return_data = ee()->TMPL->tagdata;
		

		switch ($parameter)
        {
            case "bullets":
                $this->return_data = '';
                	$list = explode(' | ', ee()->TMPL->tagdata);
					foreach($list as $item) {
	                	$this->return_data .= '<span class="badge badge-pill badge-primary bg-brightblue">'.$item.'</span> &nbsp;';
                	}
                
                
                
            
            break;
            case "numbered":
                
                $this->return_data = '<ol>';
                	$list = explode(' | ', ee()->TMPL->tagdata);
					foreach($list as $item) {
	                	$this->return_data .= '<li>'.$item.'</li>';
                	}
                $this->return_data .= '</ol>';

            break;
        }
    
    }
    
	
}

?>