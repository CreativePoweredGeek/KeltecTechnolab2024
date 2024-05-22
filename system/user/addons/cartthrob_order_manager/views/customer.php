<div class="app-notice-wrap">
    <?= ee('CP/Alert')->getAllInlines() ?>
</div>

<div class="form-standard add-mrg-bottom">
    <div class="form-btns form-btns-top">
        <h1><?= $report_title ?></h1>
    </div>
    <!-- overflow-x: scroll; -->
    <div class="tbl-ctrls">
        <div class="tbl-wrap">
            <?= $total_table ?>
        </div>
    </div>
    <div class="tbl-ctrls">
        <div class="tbl-wrap">
            <?= $order_table ?>
        </div>
    </div>

    <?= $export_csv ?>
        <div class="form-btns">
            <input type="hidden" value="true" name="download">
            <button type="submit" name="download" value="xls" class="btn submit"><?=lang('ct.om.export_xls')?></button>
            <button type="submit" name="download" value="csv" class="btn submit"><?=lang('ct.om.export_csv')?></button>
        </div>
    <?= form_close() ?>
</div>
