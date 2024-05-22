<div class="box">
  <?php $this->embed('ee:_shared/form')?>
</div>

<?php 
//popup data in page
if(isset($popup_data))
{
	$this->embed('_popup_div', $popup_data);
}

//table data in page
if(isset($table) && !empty($table))
{
	echo '<br/>';
	$this->embed('ee:_shared/table', $table);
}
?>