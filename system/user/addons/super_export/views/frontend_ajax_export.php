<!DOCTYPE html>
<html>
<head>
	<title>Super Export</title>
	<style type="text/css">
		* {margin: 0px; padding: 0px; box-sizing: border-box; }
		body, html {height: 100%; font-family: sans-serif; }
		body {margin: 0; font-size: 1rem; font-weight: 400; line-height: 1.5; color: #212529; background-color: #fff; }
		.container {width: 100%; min-height: 100vh; background: #fff; display: -webkit-box; display: -webkit-flex; display: -moz-box; display: -ms-flexbox; display: flex; align-items: center; justify-content: center; flex-wrap: wrap; padding: 33px 30px; }
		.wrap-table {width: 1170px;}
		.statistics {padding: 15px;}
		.table-wrapper {border-radius: 10px; overflow: hidden; box-shadow: 0 0px 40px 0px rgba(0, 0, 0, 0.15); -moz-box-shadow: 0 0px 40px 0px rgba(0, 0, 0, 0.15); -webkit-box-shadow: 0 0px 40px 0px rgba(0, 0, 0, 0.15); -o-box-shadow: 0 0px 40px 0px rgba(0, 0, 0, 0.15); -ms-box-shadow: 0 0px 40px 0px rgba(0, 0, 0, 0.15); position: relative; background-color: #fff; margin-bottom: 110px; }
		.table-wrapper table {width: 100%; border-collapse: collapse; }
		.table-wrapper tr:nth-child(even) {background-color: #f8f6ff; }
		.table-wrapper tr td {font-family: Lato-Regular; font-size: 15px; color: #808080; line-height: 1.4; padding-left: 16px; padding-top: 16px; padding-bottom: 16px;}
		.table-wrapper tr td:nth-child(1) {width: 75%}
		.table-wrapper tr td:nth-child(2) {width: 25%}
		.hide {display: none;}
		.status {text-transform: capitalize;}
		.completed {color: green;}
		.pending {color: red;}
		.uppercase {text-transform: uppercase;}
		.export-percent {font-style: italic;}
		.spinner {width: 30px; vertical-align: middle;}
	</style>
</head>
<body class="super-export">
	<div class="container">
		<div class="wrap-table">
			<div class="statistics">
				<p>
					<strong class="export-percent">0%</strong> completed. Please do not refresh or leave the page.
					<?php if($status == "pending") { ?>
					<svg class="spinner" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid"><g transform="rotate(0 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.9166666666666666s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(30 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.8333333333333334s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(60 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.75s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(90 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.6666666666666666s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(120 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.5833333333333334s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(150 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.5s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(180 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.4166666666666667s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(210 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.3333333333333333s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(240 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.25s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(270 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.16666666666666666s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(300 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.08333333333333333s" repeatCount="indefinite"></animate></rect></g><g transform="rotate(330 50 50)"><rect x="47" y="24" rx="9.4" ry="4.8" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="0s" repeatCount="indefinite"></animate></rect></g></svg>
					<?php } ?>
				</p>
			</div>

			<div class="table-wrapper">
				<table>
					<tbody>
						<tr>
							<td>Export Type</td>
							<td class="export-type"><span class="uppercase"><?= $format; ?></span></td>
						</tr>
						<tr>
							<td>Total Number of entries to Export</td>
							<td class="total"><?= $total?></td>
						</tr>
						<tr>
							<td>Exported entries</td>
							<td class="offset"><?= $offset?></td>
						</tr>
						<tr>
							<td>Limit per page</td>
							<td class="limit"><?= $limit; ?></td>
						</tr>
						<tr>
							<td>Export status</td>
							<td><span class="status <?= $status; ?>"><?= $status; ?></span></td>
						</tr>
						<tr class="">
							<td>Download</td>
							<td><a href="<?= (isset($url) && $url != '') ? $url : "#" ?>" download class="download_export <?= (isset($url) && $url != '') ? '' : 'hide' ?>">Download</a></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		<?php include_once(__DIR__ . '/../javascript/jquery.min.js'); ?>
	</script>
	<script type="text/javascript">
		var ajaxCancel = false;
		var process_on = false;
		$(document).ready(function() {
			<?php if($status == "pending") { ?>
				ajaxExport("<?= $next_batch; ?>");
			<?php } ?>

			function ajaxExport(url)
			{
				$.ajax({
					url: url,
					type: 'GET',
					cache: false,
					dataType: 'json',
					data: {type: 'ajax'},
				})
				.done(function(data, type, xhr) {

					if(ajaxCancel)
					{
						ajaxCancel = false;
						process_on = false;
						return false;
					}

					if(data.status == "error")
					{
						alert(data.error);
					}
					else
					{
						var percent = ((100 * data.offset) / data.total).toFixed(2);
						$('.export-percent').html(percent + "%");

						$('.total').html(data.total);
						$('.offset').html(data.offset);
						$('.limit').html(data.limit);
						$('.status').removeClass('pending active start completed').addClass(data.status).html(data.status);

						if(data.status == "pending")
						{
							ajaxExport(data.next_batch);
						}
						else
						{
							process_on = false;
							$('.spinner').hide();
							$('.download_export').removeClass('hide').attr('href', data.url);
						}
					}
				})
				.fail(function() {
					console.log("error");
				})
				.always(function() {
					console.log("complete");
				});

			}
		});
	</script>
</body>
</html>