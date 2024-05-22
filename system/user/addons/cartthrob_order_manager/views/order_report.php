<div class="app-notice-wrap">
    <?= ee('CP/Alert')->getAllInlines() ?>
</div>

<?php if ($reports): ?>
    <div class="form-standard add-mrg-bottom" id="order-select-field-container">
        <div class="form-btns form-btns-top">
            <h1><?= lang('ct.om.saved_reports') ?></h1>
        </div>

        <?= $reports_filter; ?>
            <fieldset>
                <div class="field-instruct">
                    <label for="report">Report</label>
                </div>
                <div class="field-control">
                    <?= form_dropdown('report', $reports) ?>
                </div>
            </fieldset>

            <div class="form-btns">
                <button
                    type="button"
                    class="m-link btn"
                    rel="modal-confirm-remove"
                    href=""
                    data-confirm="Content Item: <b>My Entry</b>"
                    data-content_id="23"
                >Remove?</button>
                &nbsp;
                <button class="btn" type="submit" class="btn submit"><?= lang('ct.om.saved_reports') ?></button>
            </div>
        <?= form_close() ?>
    </div>
<?php endif; ?>


<div class="form-standard add-mrg-bottom">
    <div class="form-btns form-btns-top">
        <h1>Order Report</h1>
    </div>

    <?=$run_report ?>
        <fieldset>
            <div class="field-instruct">
                <label for="where[status]"><?= lang('ct.om.status') ?></label>
            </div>
            <div class="field-control>">
                <?= form_dropdown('where[status]', $statuses) ?>
            </div>
        </fieldset>

        <h2><?= lang('ct.om.date_range') ?></h2>
        <fieldset>
            <div class="field-instruct">
                <label for="where[date_start]"><?= lang('ct.om.date_start') ?></label>
            </div>
            <div class="field-control">
                <input type="text" value="<?= $date_start ?>" data-timestamp="" class="date-picker" data-date-format="%n/%j/%Y %g:%i %A" name="where[date_start]" rel="date-picker">
            </div>
        </fieldset>

        <fieldset>
            <div class="field-instruct">
                <label for="where[date_finish]"><?= lang('ct.om.date_finish')?></label>
            </div>
            <div class="field-control">
                <input type="text" value="<?= $date_finish ?>" data-timestamp="" class="date-picker" data-date-format="%n/%j/%Y %g:%i %A" name="where[date_finish]" rel="date-picker">
            </div>
        </fieldset>

        <h2><?= lang('ct.om.price_range') ?></h2>
        <fieldset>
            <div class="field-instruct">
                <label for="where[total_minimum]"><?= lang('ct.om.total_minimum')?></label>
            </div>
            <div class="field-control">
                <input type="text" value="<?= $date_start ?>"  name="where[total_minimum]">
            </div>
        </fieldset>

        <fieldset>
            <div class="field-instruct">
                <label for="where[total_maximum]"><?= lang('ct.om.total_max')?></label>
            </div>
            <div class="field-control">
                <input type="text" value="<?= $date_finish ?>"  name="where[total_maximum]">
            </div>
        </fieldset>

        <h2><?= lang('ct.om.search_member_data') ?></h2>
        <div class="add-mrg-bottom">
            <div class="tbl-ctrls">
                <div class="tbl-wrap">
                    <?= $member_inputs ?>
                </div>
            </div>
        </div>

        <h2><?= lang('ct.om.search_field_settings') ?></h2>
        <div class="add-mrg-bottom">
            <div class="tbl-ctrls">
                <div class="tbl-wrap-list">
                    <?= $search_fields ?>
                </div>
            </div>
        </div>

        <h2><?= lang('ct.om.include_total_fields') ?></h2>
        <div class="add-mrg-bottom">
            <div class="tbl-ctrls">
                <div class="tbl-wrap">
                    <?= $order_totals ?>
                </div>
            </div>
        </div>

        <h2><?= lang('ct.om.include_order_fields') ?></h2>
        <div class="add-mrg-bottom">
            <div class="tbl-ctrls">
                <div class="tbl-wrap">
                    <?= $order_fields ?>
                </div>
            </div>
        </div>

        <div class="ct-static-btns">
            <input type="submit" name="submit" value="<?= lang('ct.om.run_report') ?>" class="btn submit" />

            <button type="submit" name="download" value="xls" class="btn submit"><?= lang('ct.om.export_xls') ?></button>

            <button type="submit" name="download" value="csv" class="btn submit"><?= lang('ct.om.export_csv') ?></button>
        </div>

        <h2><?= lang('ct.om.save_report') ?></h2>
        <fieldset>
            <div class="field-instruct">
                <label for="report_title"><?= lang('ct.om.report_title') ?></label>
            </div>
            <div class="field-control">
                <input type="text" name="report_title" value="">
            </div>
        </fieldset>
        <div class="ct-static-btns">
            <input type="submit" name="save_report" value="<?= lang('ct.om.save_report') ?>" class="btn submit" />
        </div>

    <?= form_close() ?>
</div>