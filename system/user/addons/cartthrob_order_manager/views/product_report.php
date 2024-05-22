<div class="box add-mrg-bottom">
    <?= ee('CP/Alert')->getAllInlines() ?>
</div>

<div class="box">
    <div class="form-btns form-btns-top">
        <h1><?= lang('ct.om.product_report') ?></h1>
    </div>
    <div>
        <?= form_open(ee('CP/URL')->make('addons/settings/cartthrob_order_manager/product_report'), 'id="reports_date" class="tbl-ctrls"') ?>
        <p>
            <?= lang('ct.om.date_start') ?>
            <input type="text" value="<?=$entry_start_date ?? '' ?>" autocomplete="off" class="datepicker" name="where[date_start]" size="30" style="width:100px"/>
            <?= lang('ct.om.date_finish') ?>
            <input type="text" value="<?=$entry_end_date ?? '' ?>" autocomplete="off" class="datepicker" name="where[date_finish]" size="30" style="width:100px"/>
            <?= form_submit('', lang('ct.om.date_range'), 'class="btn submit"') ?>
        </p>
        <?= form_close() ?>
    </div>

    <?= form_open(ee('CP/URL')->make('addons/settings/cartthrob_order_manager/product_report', ['return' => __FUNCTION__]), 'class="tbl-ctrls"') ?>
        <?= $hidden_inputs ?>
        <input type="hidden" name="filename" value="Products">

        <div class="tbl-wrap">
            <?= $products ?>
        </div>

        <div class="form-btns">
            <button type="submit" name="download" value="xls" class="btn">
                <?= lang('ct.om.export_xls') ?>
            </button>
            <button type="submit" name="download" value="csv" class="btn">
                <?= lang('ct.om.export_csv') ?>
            </button>
            <input type="submit" name="submit" value="<?= lang('ct.om.run_report') ?>" class="btn" />
        </div>
    <?= form_close() ?>
</div>