<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sm_fake_output extends EE_Output
{

    public function __construct()
    {

    }

    public function show_message($data, $xhtml = true) {}
    
    /*User error message display is important*/
    public function show_user_error($type = "submission", $errors, $heading = "")
    {
        get_instance()->old_output->show_user_error($type, $errors, $heading);
    }
    
}
