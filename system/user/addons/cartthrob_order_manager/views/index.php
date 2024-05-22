<div class="box add-mrg-bottom">
    <?= ee('CP/Alert')->getAllInlines() ?>
</div>

<div class="box table-list-wrap">
    <?= form_open(ee('CP/URL')->make('addons/settings/cartthrob_order_manager'), 'id="reports_filter"') ?>
        Report <?= form_dropdown('report', $reports, $current_report) ?>
        <?= form_submit('', lang('refresh'), 'class="btn submit"') ?>
    <?= form_close() ?>

    <div>
        <?= form_open(ee('CP/URL')->make('addons/settings/cartthrob_order_manager'), 'id="reports_date"') ?>
        <p>
            <div class="" style="margin-bottom:12px">   
                <label><?= lang('ct.om.date_range') ?></label>
                <?= form_dropdown('date-range-select', $range_options) ?>
            </div>
            <div style="margin-bottom:12px;">
                <div class="" style="display: inline-block; margin-right:12px;">
                    <label><?= lang('ct.om.date_start') ?></label>
                    <input type="text" value="<?=$entry_start_date ?? '' ?>" class="datepicker" name="date_start" size="30" style="width:100px"/>
                </div>
                <div class="" style="display: inline-block;">
                    <label><?= lang('ct.om.date_finish') ?></label>
                    <input type="text" value="<?=$entry_end_date ?? '' ?>" class="datepicker" name="date_finish" size="30" style="width:100px"/>
                </div>
                <div class="" style="display: inline-block;margin-left:12px;"><?= form_submit('', lang('ct.om.date_range'), 'class="btn submit"') ?></div>
            </div>
            
        </p>
        <?= form_close() ?>
    </div>

    <?php if ($current_report): ?>
        <div id="reports_view">
            <?= $view ?>
        </div>
    <?php else : ?>
        <?= $view ?>
    <?php endif; ?>

    <?= $todays_orders ?>

    <?= $order_totals ?>
</div>