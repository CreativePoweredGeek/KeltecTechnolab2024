<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>

<div class="cartthrobOptionConfiguratorOptions">
	<input type="hidden" class="field_id_name" name="opt_field_name" value="<?=$field_id?>" /> 
	<table cellspacing="0" cellpadding="0" border="0" class="mainTable">
 		<thead class="">
			<tr>
				<th>
					<strong><?=lang('option')?></strong><br />
				</th>
				<th colspan="2">
					<strong><?=lang('price')?></strong><br />
				</th>
			</tr>
		</thead>
		<tbody>
			<?php 
 			foreach ($modifiers['option'] as $key => $value)
			{
 				$option_field = array(
				              'name'        =>  $name."[option][".$count."]",
				              'value'       =>  $value,
				              'maxlength'   => '20',
 					            );
				$price_field = array(
				              	'name'        =>  $name."[price][".$count."]",
								'value'       => ((!empty($modifiers['price'][$key])) ? $modifiers['price'][$key] : NULL),
				              'maxlength'   => '20',
 				            );
 
				
			 	echo "<tr class='group_option'>"; 
					
					echo "<td>". form_input($option_field). "</td>"; 

					echo "<td>". form_input($price_field). "</td>"; 

					echo "<td>"; 
					echo '<a href="javascript:void(0);" title="'.lang('add_field').'" class="ct_add_field">
						<b class="ico settings plus"></b></a>';
					echo '<a href="javascript:void(0);" title="'.lang('delete_field').'" class="ct_delete_field">
						<b class="ico settings minus"></b></a>'; 
					echo "</td>";
				echo "</tr>"; 
				
				$count ++; 
			}
			?>
			<tr class="group_option_template" style="display:none" >
				<td>
					<?php $option_field['name'] = $name."[option_template]";
						$option_field['value'] = NULL; 
 						echo form_input($option_field); 
					?>
				</td>
				<td>
					<?php 
						 $price_field['name'] = $name."[price_template]";
						$price_field['value']	= NULL; 
					 	echo form_input($price_field); 
					?>
				</td>
				<td>
 					<a href="javascript:void(0);" title="<?=lang('add_field') ?>" class="ct_add_field">
						<b class="ico settings plus"></b></a>
					<a href="javascript:void(0);" title="<?=lang('delete_field')?>" class="ct_delete_field">
						<b class="ico settings minus"></b></a>
				</td>
			</tr>
 		</tbody>
	</table>
</div>	
	
 
	
	
	
	
	