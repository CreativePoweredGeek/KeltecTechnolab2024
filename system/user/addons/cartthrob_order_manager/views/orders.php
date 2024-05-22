<div class="app-notice-wrap">
    <?= ee('CP/Alert')->getAllInlines() ?>
</div>

<div class="box add-mrg-bottom">
    <div class="form-btns form-btns-top">
        <h1><?= lang('ct.om.orders') ?></h1>
    </div>
    <?=form_open($update_url, 'class="tbl-ctrls"')?>
        <input type="hidden" name="page" value="<?= $current_page ?>">

        <?php $this->embed('ee:_shared/table', $table); ?>
        <?=$pagination; ?>

        <?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
            <fieldset class="tbl-bulk-act hidden">
                <input  class="btn submit" type="submit" name="submit" value="<?=lang('ct.om.update_orders')?>" />
            </fieldset>
        <?php endif; ?>
    <?=form_close();?>
</div>