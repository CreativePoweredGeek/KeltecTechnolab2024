<div class="app-notice-wrap">
    <?= ee('CP/Alert')->getAllInlines() ?>
</div>

<div class="form-standard add-mrg-bottom">
    <div class="form-btns form-btns-top">
        <h1><?=lang("ct.om.total_customers")?>: <?= $customer_count ?></h1>
    </div>

    <?= $export_csv ?>
        <input type="hidden" value="true" name="download">
        <input type="hidden" value="Customers" name="filename">

        <div class="tbl-ctrls">
            <div class="tbl-wrap">
                <?= $html ?>
            </div>
        </div>

        <div class="form-btns">
            <button type="submit" name="download" value="xls" class="btn submit"><?=lang('ct.om.export_xls')?></button>
            <button type="submit" name="download" value="csv" class="btn submit"><?=lang('ct.om.export_csv')?></button>
        </div>
    <?= form_close() ?>
</div>
