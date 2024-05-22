<div class="box">
	<h1>Delete Logs</h1>
	<div class="txt-wrap">
		<?=form_open(ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/api_logs_delete/'))?>

		<input type="hidden" name="confirm" value="ok"/>

		<p><strong><?=lang('webservice_delete')?></strong></p>
		<p class="notice"><?=lang('webservice_delete_notice')?></p>

			<input type="submit" class="btn" value="<?=lang('delete')?>" name="submit">
		</p>
		</form>
	</div>
</div>