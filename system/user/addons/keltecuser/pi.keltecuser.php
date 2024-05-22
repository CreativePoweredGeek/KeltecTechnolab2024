<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Keltecuser {


     //public function loggedincount() {
          //$sql = "SELECT DISTINCT member_id FROM exp_sessions WHERE member_id != ?";
         //$query = ee()->db->query($sql, array(13));
          //$numrow = $query->num_rows();
          //return $numrow;

     //}
     //logout() is used to forcibly log a user out by an Admin
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