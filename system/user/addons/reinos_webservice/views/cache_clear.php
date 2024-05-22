<?=ee('CP/Alert')->get(REINOS_WEBSERVICE_MAP.'_cache_clear')?>
<?=form_open(ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/clear_cache/'))?>
    <input type="hidden" name="confirm" value="ok"/>
    <p><strong><?=lang(REINOS_WEBSERVICE_MAP.'_delete_cache_check')?></strong></p>
    <p class="notice"><?=lang(REINOS_WEBSERVICE_MAP.'_delete_check_notice')?></p>
    <input type="submit" class="btn" value="<?=lang('clear cache')?>" name="submit">
</form>
