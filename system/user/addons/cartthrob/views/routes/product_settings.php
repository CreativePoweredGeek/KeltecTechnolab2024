<div class="box add-mrg-bottom">
    <?php echo ee('CP/Alert')->getAllInlines(); ?>
</div>
<div class="box table-list-wrap">
    <?php echo form_open($base_url, 'class="tbl-ctrls"'); ?>
    <fieldset class="tbl-search right">
        <a class="btn tn action" href="<?php echo ee('CP/URL')->make('addons/settings/cartthrob/products/add-channel'); ?>"><?php echo lang('product_channel_add_another_channel'); ?></a>
    </fieldset>
    <h1><?php echo lang('nav_product_channels'); ?></h1>
    <div class="app-notice-wrap">
        <?php echo ee('CP/Alert')->get('items-table'); ?>
    </div>

    <?php $this->embed('ee:_shared/table', $table); ?>
    <?php echo form_close(); ?>
</div>

<br />
<?php
$this->embed('ee:_shared/form', ['alerts_name' => 'dont-use']);