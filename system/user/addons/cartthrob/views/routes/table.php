<div class="box add-mrg-bottom">
    <?php echo ee('CP/Alert')->getAllInlines(); ?>
</div>
<div class="box table-list-wrap">
    <?php echo form_open($base_url, 'class="tbl-ctrls"'); ?>
    <h1><?php echo lang($cp_page_title); ?></h1>
    <?php $this->embed('ee:_shared/table', $table); ?>
    <?php echo isset($pagination) ? $pagination : ''; ?>
    <?php echo form_close(); ?>
</div>

<br />