    <div class="app-notice-wrap">
        <?= ee('CP/Alert')->getAllInlines() ?>
    </div>

    <div class="form-standard add-mrg-bottom">
        <?php if (!empty($data['report_title'])): ?>
            <div class="form-btns form-btns-top">
                <h1><?= $data['report_title']; ?></h1>
            </div>
        <?php endif; ?>

        <fieldset>
            <?=$data['total_table']?>
        </fieldset>

        <fieldset>
            <?=$data['order_table']?>
        </fieldset>

    	<?=$data['export_csv']?>
            <?=$data['hidden_inputs']?>

            <div class="form-btns">
                <button type="submit" name="download" value="xls" class="btn submit"><?=lang('ct.om.export_xls')?></button> <button type="submit" name="download" value="csv" class="btn submit"><?=lang('ct.om.export_csv')?></button>
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
            <div class="form-btns">
                <input type="submit" name="save_report" value="<?= lang('ct.om.save_report') ?>" class="btn submit" />
            </div>
        </form>
</div>