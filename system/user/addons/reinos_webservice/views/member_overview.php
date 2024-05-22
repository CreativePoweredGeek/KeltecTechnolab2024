<div class="box">
    <div class="tbl-ctrls">
        <fieldset class="tbl-search right">
            <a class="btn tn action " href="<?=ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/show_member')?>">Create New Member</a>
        </fieldset>
        <h1>Manage Members</h1>
        <?=ee('CP/Alert')->get(REINOS_WEBSERVICE_MAP.'_member_overview')?>
        <?=ee('View')->make('_shared/table')->render($table); ?>
        <?php if(isset($pagination)):?>
            <?=$pagination?>
        <?php endif;?>
    </div>
</div>
