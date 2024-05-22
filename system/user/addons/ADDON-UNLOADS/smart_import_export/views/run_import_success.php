<div class="box sm-form">
	
	<div class="sm_import_success_data">
		<form>
			<?php if($redirect_import !== false){?>
			<h2 class="call_another_import">
				<p class="main-img">
					<img src="<?=$loading_image;?>" class="searchIndicator" width="16" height="16">
				</p>
				<p>
					<?= lang('next_batch_loading'); ?>
				</p>
			</h2>
			<?php } ?>

			<h2><?= lang('statastics'); ?></h2>
			<fieldset class="col-group">
				<div class="setting-txt col  w-8">
					<td><?= lang('total_row_to_perform_action'); ?></td>
				</div>
				<div class="setting-field col w-8 last">
					<?= $total_entries?>
				</div>
			</fieldset>
			<fieldset class="col-group">
				<div class="setting-txt col  w-8">
					<td><?= lang('total_inserted_entries'); ?></td>
				</div>
				<div class="setting-field col w-8 last">
					<?= $imported_entries?>
				</div>
			</fieldset>
			<fieldset class="col-group">
				<div class="setting-txt col  w-8">
					<td><?= lang('total_updated_entries'); ?></td>
				</div>
				<div class="setting-field col w-8 last">
					<?= $updated_entries?>
				</div>
			</fieldset>
			<fieldset class="col-group">
				<div class="setting-txt col  w-8">
					<td><?= lang('total_re_created_entries'); ?></td>
				</div>
				<div class="setting-field col w-8 last">
					<?= $recreated_entries?>
				</div>
			</fieldset>
			<fieldset class="col-group">
				<!-- delete existing entries -->
				<div class="setting-txt col  w-8">
					<td><?= lang('total_deleted_existing_entries'); ?></td>
				</div>
				<div class="setting-field col w-8 last">
					<?= $deleted_existing_entries?>
				</div>
			</fieldset>
			<fieldset class="col-group">
				<div class="setting-txt col  w-8">
					<td><?= lang('total_skipped_entries'); ?></td>
				</div>
				<div class="setting-field col w-8 last">
					<?= $skipped_entries?>
				</div>
			</fieldset>
			<fieldset class="col-group">
				<div class="setting-txt col  w-8">
					<td><?= lang('memory_usage_for_this_batch'); ?></td>
				</div>
				<div class="setting-field col w-8 last">
					<?php
					$memory_usage = round(($memory_usage / (1024 * 1024)), 2);
					if($memory_usage <= 1024)
					{
						echo $memory_usage . " MB";
					}
					else
					{
						echo round(($memory_usage / 1024), 2) . " GB";
					}
					?>
				</div>
			</fieldset>
			<fieldset class="col-group">
				<div class="setting-txt col  w-8">
					<h3><?= lang('total_memory_usage'); ?></h3>
				</div>
				<div class="setting-field col w-8 last">
					<?php
					$total_memory_usage = round(($total_memory_usage / (1024 * 1024)), 2);
					if($total_memory_usage <= 1024)
					{
						echo $total_memory_usage . " MB";
					}
					else
					{
						echo round(($total_memory_usage / 1024), 2) . " GB";
					}
					?>
				</div>
			</fieldset>
			<fieldset class="col-group">
				<div class="setting-txt col  w-8">
					<h3><?= lang('time_taken_for_this_batch_to_import'); ?></h3>
				</div>
				<div class="setting-field col w-8 last">
					<?php 
					if($time_taken > 60)
					{
						echo round(($time_taken / 60), 2) . " " . lang('minutes') ;
					}
					else
					{
						echo $time_taken . " " . lang('seconds');
					}
					?>
				</div>
			</fieldset>
			<fieldset class="col-group last">
				<div class="setting-txt col  w-8">
					<h3><?= lang('total_time_taken_to_import'); ?></h3>
				</div>
				<div class="setting-field col w-8 last">
					<?php 
					if($total_time_taken > 60)
					{
						echo round(($total_time_taken / 60), 2) . " " . lang('minutes') ;
					}
					else
					{
						echo $total_time_taken . " " . lang('seconds');
					}
					?>
				</div>
			</fieldset>

			<div class="inside-table">
				<?php if(isset($insert_data)){?>
				<h2><?= lang('members_added')?> (<?= lang('total')?> : <?= $imported_entries?>)</h2>
				<div class="insert_data">
					<?php echo $insert_data;?>
				</div>
				<?php }?>

				<?php if(isset($update_data)){?>
				<h2><?= lang('members_updated')?> (<?= lang('total')?> : <?= $updated_entries?>)</h2>
				<div class="update_data">
					<?php echo $update_data;?>
				</div>
				<?php }?>

				<?php if(isset($delete_data)){?>
				<h2><?= lang('members_re_created')?> (<?= lang('total')?> : <?= $recreated_entries?>)</h2>
				<div class="delete_data">
					<?php echo $delete_data;?>
				</div>
				<?php }?>
			</div>

		</form>
	</div>
</div>