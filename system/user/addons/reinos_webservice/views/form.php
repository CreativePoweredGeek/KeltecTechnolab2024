<div class="<?php echo ee(REINOS_WEBSERVICE_SERVICE_NAME.':Version')->viewBoxClass()?>">
    <?php if(isset($before_form)) echo $before_form;?>
    <?php $this->embed('ee:_shared/form')?>
    <?php if(isset($after_form)) echo $after_form;?>
</div>
