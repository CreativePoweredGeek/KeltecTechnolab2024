<?php if (is_array($templates_installed) && count($templates_installed)) { ?>
    <h2><?php echo lang('ct.gc.installed'); ?></h2>
    <ul>
        <?php foreach ($templates_installed as $installed) { ?>
            <li><?php echo $installed; ?></li>
        <?php } ?>
    </ul>
<?php } ?>

<?php if (is_array($template_errors) && count($template_errors)) { ?>
    <h2><?php echo lang('ct.gc.errors'); ?></h2>
    <ul>
        <?php foreach ($template_errors as $error) { ?>
            <li><?php echo $error; ?></li>
        <?php } ?>
    </ul>
<?php } ?>

<?php $this->embed('ee:_shared/form'); ?>