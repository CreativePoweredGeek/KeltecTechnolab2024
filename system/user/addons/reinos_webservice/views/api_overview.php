<div class="box">
    <div class="tbl-ctrls">
        <h1>API Overview</h1>
        <p>Looking for more API`s? Visit <a href="https://addons.reinos.nl/webservice/extensions" target="_blank">extension overview</a> page on <a href="https://addons.reinos.nl/webservice/extensions" target="_blank">https://addons.reinos.nl/webservice</a> </p>
        <?=ee('View')->make('_shared/table')->render($table); ?>
        <?php if(isset($pagination)):?>
            <?=$pagination?>
        <?php endif;?>
    </div>
</div>
