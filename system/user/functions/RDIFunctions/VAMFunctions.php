<?php
function VAM_SalespersonName($strSalesP){

  $query = "select cname from arslpn where cslpnno = ?";
  $parameters = Array($strSalesP);
  $result = ExecuteQuery($query,$parameters);
  if ($result[0] == true){
    return $result[1]->fields[0];
  }
  else {
    return '';
  }
}

function VAM_GLDescription($strID){

  global $conn;
$query = "exec [dbo].[rdicsp_GLAccountDescriptionSel] ?";
$parameters = Array($strID);
$result = sqlsrv_query($conn,$query,$parameters);

  if ($result && $row = sqlsrv_fetch_array($result)){
    return $row[0];
  }
  else {
    return '';  
  }

}

function VAM_CreateUID(){

  $query = "select newid() as id";
  $result = ExecuteQuery($query);  
  if ($result[0]){
    $row = $result[1]->GetRowAssoc(false);
    return substr(trim($row['id']),0,8).substr(trim($row['id']),-7);
  } 

  return '';
}

function VAM_CreateFullUID(){

  $retvalue = '';
  
  $query = "select newid()";
  $result = ExecuteQuery($query);
  
  if ($result[0]){
    return $result[1]->fields[0];
  } else {
    return '';
  }
  
}

function VAM_GetUID($conn, $csono,$citemno){

  $retvalue = '';
  
  $query = "select cuid from sostrs where csono = ? and citemno= ?";  
  $parameters = Array($csono, $citemno);
  $result = ExecuteQuery($query,$parameters);
  if ($result[0] && $row = $result->FetchRow()){   
    return $row[0];
  } else {
    return '';
  }
  
}

function VAM_GetItemDetails($citemno,$cwarehouse){

  $query = "select icitem.*,isnull(iciwhs.ncost,0) ncost, isnull(icunit.cmeasure,'') as cmeasure, isnull(icunit.ncnvqty,0) ncnvqty, iciwhs.crevncode
            from icitem
            left outer join icunit on icitem.cmeasure = icunit.cmeasure
            left outer join iciwhs on icitem.citemno = iciwhs.citemno and iciwhs.cwarehouse = '$cwarehouse' 
            where icitem.citemno = '$citemno'";
  //$parameters = Array($cwarehouse,$citemno);
  $result = ExecuteQuery($query);

  if ($result[0] && $result[1]->RecordCount() > 0 && $row = $result[1]->GetRowAssoc()){
    return $row;
  }
  else {
    return Array();  
  }  
}

/*
function VAM_IncrementSystemSONumber($conn){

  $query = "update arsyst set csono = csono + 1";
  $result = ExecuteQuery($query);
      
  if (!$result[0]){  
    return "Error updating System SO #.<br/>".$result[1];
  } 
  
  return 'SUCCESS';
  
}*/

function VAM_GetCustNOFromSO($csono){

  $query = "select distinct ccustno from sosord where csono = ? union select distinct ccustno from sosordh where csono = ?";  
  $parameters = Array(str_pad($csono,10,' ',STR_PAD_LEFT),str_pad($csono,10,' ',STR_PAD_LEFT));
  $result = ExecuteQuery($query,$parameters);
    
  if ($result[0] && $row = $result[1]->FetchRow()){  
    return trim($row[0]);
  }
  
  return '';
  
}

function VAM_GetCustNOFromInvNo($cinvno){

  $query = "select distinct ccustno from arinvc where cinvno = ? union select distinct ccustno from arinvch where cinvno = ?";  
  $parameters = Array(str_pad($cinvno,10,' ',STR_PAD_LEFT),str_pad($cinvno,10,' ',STR_PAD_LEFT));
  $result = ExecuteQuery($query,$parameters);
    
  if ($result[0] && $row = $result[1]->FetchRow()){  
    return trim($row[0]);
  }
  
  return '';
  
}

function VAM_GetNextInvNumber() {
  
  $query = "select cinvno from arsyst";
  $result = ExecuteQuery($query);
  
  if ($result[0] && $row = $result[1]->FetchRow()){
    return $row[0];      
  }

  return '';
}

/*  
function VAM_IncrementSystemInvNumber(){

  $query = "update arsyst set cinvno = cinvno + 1";
  $result = ExecuteQuery($query);
      
  if (!$result[0]){  
    return "Error updating System SO #.<br/>".$result[1];
  } 

  return 'SUCCESS';
  
}*/

function VAM_GetCompanyDefaults() {
  
  $query = "select * from arsyst";
  $result = ExecuteQuery($query);
  
  if ($result[0] && $row = $result[1]->GetRowAssoc(false)){
    return $row;      
  }

  return '';
}

function VAM_GetCustomerDetails($ccustno){
  // Retrieve Paycode Info
  $query = "select * from arcust where ccustno = ?";
  $parameters = Array($ccustno);
  $result = ExecuteQuery($query,$parameters);

  $arcust = Array();
  if ($result[0] && is_array($result[1]) && count($result[1]) > 0){
    $arcust = $result[1][0];
  } 
  
  return $arcust;
}

function VAM_GetPaycodeDetails($paycode){
  // Retrieve Paycode Info
  $query = "select npaytype,cdescript,cpaycode from arpycd where cpaycode = ?";
  $parameters = Array($paycode);
  $result = ExecuteQuery($query,$parameters);

  $arpycd = Array();
  if ($result[0] && $row = $result[1]->GetRowAssoc(false)){
    $arpycd = $row;
  } 
  
  return $arpycd;
}

function VAM_GetTaxcodeDetails($taxcode){
  // Retrieve Paycode Info
  $query = "select * from costax where lcurrent = 1 and ctaxcode = ?";
  $parameters = Array($taxcode);
  $result = ExecuteQuery($query,$parameters);

  $ctaxcode = Array();
  if ($result[0] && $row = $result[1]->GetRowAssoc(false)){
    $ctaxcode = $row;
  } 
  
  return $ctaxcode;
}

function VAM_EncryptCCNumber($ccnumber){

  $offsetArray = Array(32,31,30,29,28,27,26,25,24,33,32,31,30,29,28,27);
  
  $encryptedNumber = '';
  for ($i = 0; $i < strlen($ccnumber); $i++){
  
    $currentChar = '';
    $currentChar = substr($ccnumber,$i,1);
    
    $currentChar = ord($currentChar);
    
    $currentChar = intval($currentChar) + $offsetArray[$i];
    
    $encryptedNumber .= strtoupper(chr($currentChar));
  }
  
  return $encryptedNumber;
  
}

function VAM_DecryptCCNumber($ccnumber){

  $offsetArray = Array(32,31,30,29,28,27,26,25,24,33,32,31,30,29,28,27);
  
  $decryptedNumber = '';
  for ($i = 0; $i < strlen($ccnumber); $i++){
  
    $currentChar = '';
    $currentChar = substr($ccnumber,$i,1);
    
    $currentChar = ord($currentChar) - $offsetArray[$i];
    
    $currentChar = chr($currentChar);
        
    $decryptedNumber .= $currentChar;
  }
  
  return $decryptedNumber;
  
}

function VAM_GetInvoiceItems($cinvno){

  $query = "select citemno,cwarehouse,cdescript,nordqty,cmeasure,ntaxamt1,nsalesamt,nprice,ltaxable1 from aritrs where ltrim(cinvno) = ?
						union
						select citemno,cwarehouse,cdescript,nordqty,cmeasure,ntaxamt1,nsalesamt,nprice,ltaxable1 from nxpfdt where rtrim(cpfno) = ?";
  $parameters = Array(trim($cinvno),trim($cinvno));
  $result = ExecuteQuery($query,$parameters);
  $aritrs = Array();
  while ($result[0] && !$result[1]->EOF){
		$row = $result[1]->GetRowAssoc(false);
    $aritrs[] = $row;
		$result[1]->MoveNext();
  } 
  
  return $aritrs;

}
?>