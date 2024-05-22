<?php

function RenderSelectFromQuery($name, $query, $currVal, $attributes, $default = Array(), $disabled=false){

  $result = ExecuteQuery($query);
  
  $options = Array();
  if (count($default) == 2) $options[$default[0]] = $default[1];
  
  if ($result[0] == true){
    foreach ($result[1] as $rs => $row){
      if (count($default) == 2 && $row[0] == $default[0]) continue;
      $options[$row[0]] = $row[1];      
    }    
  }
  
  if (empty($currVal)) $currVal = $default[0];
  
  return RenderSelectBox($name, $currVal, $options, $attributes, $disabled);
}

function RenderStateSelect($name = '', $currVal = '', $attributes){
  $states_arr = array('' => 'Select State', 'AL'=>"Alabama",'AK'=>"Alaska",'AZ'=>"Arizona",'AR'=>"Arkansas",'CA'=>"California",'CO'=>"Colorado",'CT'=>"Connecticut",'DE'=>"Delaware",'DC'=>"District Of Columbia",'FL'=>"Florida",'GA'=>"Georgia",'HI'=>"Hawaii",'ID'=>"Idaho",'IL'=>"Illinois", 'IN'=>"Indiana", 'IA'=>"Iowa",  'KS'=>"Kansas",'KY'=>"Kentucky",'LA'=>"Louisiana",'ME'=>"Maine",'MD'=>"Maryland", 'MA'=>"Massachusetts",'MI'=>"Michigan",'MN'=>"Minnesota",'MS'=>"Mississippi",'MO'=>"Missouri",'MT'=>"Montana",'NE'=>"Nebraska",'NV'=>"Nevada",'NH'=>"New Hampshire",'NJ'=>"New Jersey",'NM'=>"New Mexico",'NY'=>"New York",'NC'=>"North Carolina",'ND'=>"North Dakota",'OH'=>"Ohio",'OK'=>"Oklahoma", 'OR'=>"Oregon",'PA'=>"Pennsylvania",'RI'=>"Rhode Island",'SC'=>"South Carolina",'SD'=>"South Dakota",'TN'=>"Tennessee",'TX'=>"Texas",'UT'=>"Utah",'VT'=>"Vermont",'VA'=>"Virginia",'WA'=>"Washington",'WV'=>"West Virginia",'WI'=>"Wisconsin",'WY'=>"Wyoming");
  return RenderSelectBox($name, $currVal, $states_arr, $attributes);
  
}

function RenderSelectBox($name = '', $currVal = '', $arrOptions = Array(), $attributes = '', $disabled = false){

  if ($disabled){
    $disabled = 'disabled';
  } else {
    $disabled = '';
  }
	if (empty($name)) return '';
	
	$returnString = '<select name="'.$name.'" id="'.$name.'" '.(!empty($attributes)?$attributes:'').' >';  
  
	foreach ($arrOptions as $optionValue => $optionName){   
		$returnString .= '<option value="'.$optionValue.'" '.(trim($currVal) == trim($optionValue)?'selected':$disabled).' >'.$optionName.'</option>';
	}  
	$returnString .= '</select>';
  return $returnString;
}

function RenderRadioButton($id = '', $name = '', $value = '', $label = '', $attributes = '', $currVal){

	if (empty($name)) return '';
	
	$returnString = 
    '<input type="radio" name="'.$name.'" id="'.$id.'" '.(
    !empty($attributes)?$attributes:'').
    ' value = "'.$value.'" '.
    ($value == $currVal?'checked':'').
    ' />';

    if (trim($label) != '') $returnString .= '&nbsp;<label for="'.$id.'">'.$label.'</label>';
  
  return $returnString;
}

function RenderCheckBox($id = '', $name = '', $value = '', $label = '', $attributes = '', $currVal = ''){

	if (empty($name)) return '';

  if (strpos($currVal,':') != false) $arrayValues = explode(':',$currVal);
  if (is_array($arrayValues) && count($arrayValues > 0)) $currVal = $arrayValues ;
	
	$returnString = 
    '<input type="checkbox" name="'.$name.'" id="'.$id.'" '.(
    !empty($attributes)?$attributes:'').
    ' value = "'.$value.'" '.
    ($value == $currVal || (is_array($currVal) && in_array($value,$currVal))?'checked':'').
    ' />';

    if (trim($label) != '') $returnString .= '&nbsp;<label for="'.$id.'">'.$label.'</label>';
  
  return $returnString;
}

function RenderTextBox($name = '', $value = '', $attributes = ''){

	if (empty($name)) return '';
	
	$returnString = 
    '<input type="text" name="'.$name.'" id="'.$name.'" '.
    (!empty($attributes)?$attributes:'').
    ' value = "'.trim($value).'"  />';
  
  return $returnString;
}

function RenderPasswordBox($name = '', $value = '', $attributes = ''){

	if (empty($name)) return '';
	
	$returnString = 
    '<input type="password" name="'.$name.'" id="'.$name.'" '.
    (!empty($attributes)?$attributes:'').
    ' value = "'.$value.'"  />';
  
  return $returnString;
}

function RenderTextArea($name = '', $rows = 10, $columns = 5, $value = '', $attributes = ''){

	if (empty($name)) return '';
	
	$returnString = 
    '<textarea name="'.$name.'" id="'.$name.'" '.
    " rows=$rows cols=$columns ".
    (!empty($attributes)?$attributes:'').
    '>'.$value.'</textarea>';
  
  return $returnString;
}

function RenderHiddenField($name = '', $value = '', $attributes = ''){

	if (empty($name)) return '';
	
	$returnString = '<input type="hidden" name="'.$name.'" id="'.$name.'" value = "'.$value.'" '.(!empty($attributes)?$attributes:'').' />';
  
  return $returnString;
}

function RDIFormatDate($date){

  if (!empty($date)){
    $fDate = new DateTime($date); 
    return $fDate->format('m/d/Y');
  } 
    
  return date('m/d/Y');
}

function br2nl( $input ) {
 return preg_replace('/<br(\s+)?\/?>/i', "\n", $input);
}
?>