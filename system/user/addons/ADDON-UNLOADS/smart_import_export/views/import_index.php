<div class="box">
    <?=form_open($table['base_url'])?>
    <div class="tbl-ctrls">
        <?=ee('CP/Alert')->getAllInlines()?>

        <fieldset class="tbl-search right">
            <a class="btn tn action" href="<?=$import_form?>"><?= lang('create_new') ?></a>
        </fieldset>
        <h1><?= lang('import_list')?></h1>
        <div class="smart-import-table-wrapper">
            <?php $this->embed('ee:_shared/table', $table); ?>
            <?php if ( isset($pagination) && ! empty($pagination)) echo $pagination; ?>

        <?php if ( ! empty($table['data'])){?>
        <fieldset class="tbl-bulk-act hidden">
            <select name="bulk_action" class="bulk_action">
                <option value="">-- <?=lang('with_selected')?> --</option>
                <option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove-entry"><?=lang('remove')?></option>
            </select>
            <button class="btn action" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
        </fieldset>
        <?php }?>
        </div>
    </div>
    <?=form_close()?>
</div>

<?php
$modal_vars = array(
    'name'      => 'modal-confirm-remove-entry',
    'form_url'  => $import_index,
    'hidden'    => array(
        'bulk_action'   => 'remove'
        )
    );
$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);

//popup data in page
if(isset($popup_data))
{
    $this->embed('_popup_div', $popup_data);
}
?>