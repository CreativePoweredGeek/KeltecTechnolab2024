<?
class Randomnumber {

    public $return_data = '';
    public function __construct()
    {
       //If min & max are not specified, default to 6 & 10
       
       $min = ee()->TMPL->fetch_param('min' 6);
       $max = ee()->TMPL->fetch_param('max' 10);
       
       $random = mt_rand($min,$max);
        
       $this->return_data = $random;
        
    }



}
// END CLASS
?>
