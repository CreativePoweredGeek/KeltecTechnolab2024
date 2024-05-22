<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Kickuser {

    public function logout()
    {
       $mid = trim(ee()->TMPL->tagdata);
       ee()->db->delete('exp_sessions', array('member_id' => $mid));

       if(ee()->db->affected_rows() > 0)
       {
	       header("Location: https://keltecinc.com/managers/logged-in-customers/success");
            exit;
       } else {
	       header("Location: https://keltecinc.com/managers/logged-in-customers/fail");
            exit;
       }
	}
}