<!DOCTYPE html>
<html>
<head>
	<title>Smart Export</title>
	<?php 
	if(isset($extra_data))
	{
		echo $extra_data;
	}
	?>
	<?php if($output['status'] == "pending"){?>
	<script type="text/javascript">
		$(document).ready(function() {
			runAjax("<?= $output['next_batch'];?>");
		});	
	</script>
	<?php }?>
	<script type="text/javascript">
		function runAjax(callUrl)
		{
			$.ajax({
		        url: callUrl,
		        type: 'POST',
		        data: { xid: "<?= XID_SECURE_HASH; ?>", csrf_token : "<?= XID_SECURE_HASH; ?>"} ,
		    })
		    .success(function(data) {
		        data = atob(data);
		        data = JSON.parse(data);
		        if(typeof(data.error) !== "undefined"){
		            $('.error').removeClass('hide').find('td:last').html('<p>'+data.error+'</p>');
		            $('.import-loader').hide();
		            return false;
		        } else {
		        	if(data.offset > data.totalrows){
		        		$('.offset').html(data.totalrows);
		        	}else{
		        		$('.offset').html(data.offset);
		        	}
		        	$('.limit').html(data.limit);
		        	$('.status').html(data.status);
		            if(data.status == "pending"){
		                $done = (data.offset * 100) / data.totalrows;
		                $('.perc-calc').html(parseInt($done) + "%");
		                runAjax(data.next_batch);
		            }else{
		            	$('.perc-calc').html("100%");
		                $(".url").removeClass('hide').find('td:last').html('<a href="' + data.url + '" download="" >Download</a>');
		                $('.status').removeClass('pending').addClass(data.status).html(data.status);
	                	setTimeout(function() {
	                		$('.call_another_import').fadeOut('slow');
	                	}, 1500);
		            }
		        }
		    })
		    .fail(function(data) {
		        alert("Something went wrong! Please check for console errors");
		    })

		}
	</script>
</head>
<body>
	<br>
	
	<div class="sm_import_success_data">
		<div class="container">

			<div class="row import-loader" <?php if(isset($output['url'])){echo 'style="display: none;"';}?>>
				<div class="col-md-12">
					<div class="call_another_import">
						<p>
							<img src="<?=$loading_image;?>" class="searchIndicator" width="16" height="16">
						</p>
						<p>
							Export is <span class="perc-calc">0%</span> done.. <span> <?= lang('do_not_refresh')?></span>
						</p>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					<div class="table-responsive">
						<table class="mainTable statastics table table-bordred table-striped" border="0" cellspacing="0" cellpadding="0">
							<caption><?= lang('statastics')?></caption>
							<tr>
								<td><?= lang('total_entries_to_be_export'); ?></td>
								<td><?= $output['totalrows']?></td>
							</tr>
							<tr>
								<td><?= lang('total_exported_rows'); ?></td>
								<td class="offset">
									<?php if($output['offset'] > $output['totalrows']){
										echo $output['totalrows'];
									}else{
										echo $output['offset'];
									}?>
								</td>
							</tr>
							<tr>
								<td><?= lang('limit_per_page'); ?></td>
								<td class="limit"><?= $output['limit']?></td>
							</tr>
							<tr>
								<td><?= lang('status'); ?></td>
								<td class="status setformat <?= $output['status']?>"><?= $output['status']?></td>
							</tr>
							<tr class="url <?php if(! isset($output['url'])){echo 'hide';}?>">
								<td><?= lang('download_exported_file'); ?></td>
								<td><?php if(isset($output['url'])){?><a href="<?= $output['url']?>" download> <?= lang('download'); ?></a><?php }?></td>
							</tr>
							<tr class="error hide">
								<td><?= lang('error'); ?></td>
								<td></td>
							</tr>
						</table>
					</div>
				</div>
			</div>

		</div>
	</div>

</body>
</html>