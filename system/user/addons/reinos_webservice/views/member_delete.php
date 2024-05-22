<div class="box">
	<h1>Delete member</h1>
	<div class="txt-wrap">
		<?=form_open(ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/delete_member/'.$webservice_member_id))?>

		<input type="hidden" name="confirm" value="ok"/>

		<p><strong><?=lang('reinos_webservice_delete_check')?></strong></p>
		<p class="notice"><?=lang('reinos_webservice_delete_check_notice')?></p>

			<input type="submit" class="btn" value="<?=lang('delete')?>" name="submit">
		</p>
		</form>
	</div>
</div>