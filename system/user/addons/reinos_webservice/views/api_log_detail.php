<div class="box">
    <div class="tbl-ctrls">
        <h1>API Overview</h1>
        <?=ee('View')->make('_shared/table')->render($table); ?>
        <?php if(isset($pagination)):?>
            <?=$pagination?>
        <?php endif;?>
    </div>
</div>
