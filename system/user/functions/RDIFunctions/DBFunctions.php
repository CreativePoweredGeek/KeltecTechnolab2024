<?php

function ExecuteQuery($query,$parameters = Array()){

    //set POST variables
    $url = 'https://76.190.38.196/rdiportal/WebFront.php';
    
    $fields = array(
                'query' => trim($query),
                'parameters' => implode('|',$parameters)
            );

    //url-ify the data for the POST
    $fields_string = '';
    foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
    rtrim($fields_string, '&');

    //open connection
    $ch = curl_init();

    //set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_POST, count($fields));
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    //execute post
    $result = curl_exec($ch); 
    if (substr(trim(strtoupper($result)),0,5) == 'ERROR'){
      return Array(false,$result);
    } else {
      $resultArray = json_decode($result,true);
      return Array(true,$resultArray);
    }
    
    //close connection
    curl_close($ch);

    return $result;
  
}

function LoginCheck(){
  if (empty($_SESSION['username']) || !isset($_SESSION['username'])){
    header('Location: LoginForm.php');
    die();
  }
}

function DownloadPDF($query,$parameters = Array()){

    //set POST variables
    $url = 'https://76.190.38.196/rdiportal/WebFront.php';
    
    $fields = array(
      'query' => trim($query),
      'parameters' => implode('|',$parameters)
    );

    $fields_string = null;
    //url-ify the data for the POST
    foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
    rtrim($fields_string, '&');

    //open connection
    $ch = curl_init();

    //set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_POST, count($fields));
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    //execute post
    $result = curl_exec($ch);

    //close connection
    curl_close($ch);

    return $result;
}

function ExecutePost($query,$parameters = Array()){

    //set POST variables
    $url = 'https://76.190.38.196/rdiportal/WebFront.php';
    
    $fields_string = 'query='.$query.'&';

    //url-ify the data for the POST
    foreach($parameters as $key=>$value) { $fields_string .= $key.'='.json_encode($value).'&'; }
    rtrim($fields_string, '&');

    //open connection
    $ch = curl_init();

    //set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_POST, count($fields));
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    //execute post
    $result = curl_exec($ch);

    return $result;
  
}


?>