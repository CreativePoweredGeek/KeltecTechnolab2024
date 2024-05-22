<!DOCTYPE html>
<html>
<head>
	<title>Import Success</title>
	<?php 
	if(isset($extra_data))
	{
		echo $extra_data;
	}
	
	if($redirect_import !== false){ $redirect_import = str_replace("&amp;", "&", $redirect_import);
	?>
	<script type="text/javascript">
		$(document).ready(function() {
			setTimeout(function() {
				window.location.href = "<?= $redirect_import;?>"
			}, 3000);
		});	
	</script>
	<?php }?>
	<script type="text/javascript">
		$(document).ready(function() {
			$('.insert_data table, .update_data table, .delete_data table').DataTable({
				aLengthMenu: [
				[10, 50, 100, 200, 400, -1],
				[10, 50, 100, 200, 400, "All"]
				],
			});

			$('table').addClass('table table-bordred table-striped')

		});	
	</script>
</head>
<body>
	<br>
	<div class="sm_import_success_data">
		<div class="container">

			<?php if($redirect_import !== false){?>
			<div class="row">
				<div class="col-md-12">
					<div class="call_another_import mb10">
						<p class="main-img">
							<img src="<?=$loading_image;?>" class="searchIndicator" width="16" height="16">
						</p>
						<p>
							<?= lang('next_batch_loading')?>
						</p>
					</div>
				</div>
			</div>
			<?php } ?>

			<div class="row">
				<div class="col-md-12">
					<div class="table-responsive">
						<table class="mainTable statastics" border="0" cellspacing="0" cellpadding="0">
							<caption><?= lang('statastics'); ?></caption>
							<tr>
								<td><?= lang('total_row_to_perform_action'); ?></td>
								<td><?= $total_members?></td>
							</tr>
							<tr>
								<td><?= lang('total_inserted_members'); ?></td>
								<td><?= $imported_members?></td>
							</tr>
							<tr>
								<td><?= lang('total_updated_members'); ?></td>
								<td><?= $updated_members?></td>
							</tr>
							<tr>
								<td><?= lang('total_re_created_members'); ?></td>
								<td><?= $recreated_members?></td>
							</tr>
							<tr>
								<td><?= lang('total_skipped_members'); ?></td>
								<td><?= $skipped_members?></td>
							</tr>
							<tr>
								<td><?= lang('memory_usage_for_this_batch'); ?></td>
								<td>
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
								</td>
							</tr>
							<tr>
								<td><?= lang('total_memory_usage'); ?></td>
								<td>
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
								</td>
							</tr>
							<tr>
								<td><?= lang('time_taken_for_this_batch_to_import'); ?></td>
								<td>
									<?php 
									if($time_taken > 60)
									{
										echo round(($time_taken / 60), 2) . " " . lang('minutes');
									}
									else
									{
										echo $time_taken . " " . lang('seconds');
									}
									?>
								</td>
							</tr>
							<tr>
								<td><?= lang('total_time_taken_to_import'); ?></td>
								<td>
									<?php 
									if($total_time_taken > 60)
									{
										echo round(($total_time_taken / 60), 2) . " " . lang('minutes');
									}
									else
									{
										echo $total_time_taken . " " . lang('seconds');
									}
									?>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</div>

			<?php if(isset($insert_data)){?>
			<div class="row">
				<div class="col-md-12">
					<h4><?= lang('members_added')?> (<?= lang('total')?> : <?= $imported_members?>)</h4>
					<div class="insert_data table-responsive">
						<?php echo $insert_data;?>
						<hr>
					</div>
				</div>
			</div>
			<br>
			<?php }?>

			<?php if(isset($update_data)){?>
			<div class="row">
				<div class="col-md-12">
					<h4><?= lang('members_updated')?> (<?= lang('total')?> : <?= $updated_members?>)</h4>
					<div class="update_data table-responsive">
						<?php echo $update_data;?>
						<hr>
					</div>
				</div>
			</div>		
			<br>
			<?php }?>

			<?php if(isset($delete_data)){?>
			<div class="row">
				<div class="col-md-12">
					<h4><?= lang('members_re_created')?> (<?= lang('total')?> : <?= $recreated_members?>)</h4>
					<div class="delete_data table-responsive">
						<?php echo $delete_data;?>
						<hr>
					</div>
				</div>
			</div>
			<?php }?>

		</div>
	</div>

</body>
</html>
