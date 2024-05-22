<div class="box add-mrg-bottom">
    <?php echo ee('CP/Alert')->getAllInlines(); ?>
</div>
<div class="box table-list-wrap">
    <?php echo form_open($base_url, 'class="tbl-ctrls"'); ?>
    <fieldset class="tbl-search right">
        <a class="btn tn action" href="<?php echo ee('CP/URL')->make('addons/settings/cartthrob/notifications/add'); ?>"><?php echo lang('ct.route.notification.add_notification'); ?></a>
    </fieldset>
    <h1><?php echo lang('ct.route.notification.existing_notifications'); ?></h1>
    <div class="app-notice-wrap">
        <?php echo ee('CP/Alert')->get('items-table'); ?>
    </div>

    <?php $this->embed('ee:_shared/table', $table); ?>
    <?php echo form_close(); ?>
</div>

<br />
<?php
$this->embed('ee:_shared/form', ['alerts_name' => 'dont-use']);