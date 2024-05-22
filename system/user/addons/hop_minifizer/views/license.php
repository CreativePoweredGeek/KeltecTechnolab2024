<style>
    .addon-license .col {
        padding: 3px 5px;
    }
</style>
<div class="box addon-license">
    <h1>License</h1>

    <?php echo form_open($action_url, array('class'=>'settings')); ?>
    <input name="license_setting_id" type="hidden" value="<?= $license_setting_id ?>" />
    <fieldset class="col-group required">
        <div class="setting-txt col w-6">
            <h3>License Key</h3>
            <p>Please enter your license key from the EE Store or Devot-ee. If there are any questions, please contact us at <strong><a href="https://hopstudios.com/contact">hopstudios.com</a></strong>.</p>
        </div>
        <div class="setting-field col w-10 last">
            <?php echo form_input('license_key', $license_key); ?>
        </div>
    </fieldset>

    <fieldset class="col-group license-status-group">
        <div class="setting-txt col w-6">
            <h3>License Status</h3>
        </div>
        <div class="setting-txt col w-10 last">
            <p>
                <?php if ($license_valid) { ?>
                    <span class="st-open">Valid</span>
                <?php } elseif ( ! empty($license_key)) { ?>
                    <span class="st-closed">Unlicensed</span>
                <?php } else { ?>
                    <span class="st-closed">Unlicensed</span>
                <?php } ?>
            </p>
        </div>
    </fieldset>

    <fieldset class="col-group last">
        <div class="setting-txt col w-6">
            <h3>License Agreement</h3>
        </div>
        <div class="setting-txt col w-10 last">
            <p>By using this software, you agree to the <a href="<?php echo $license_agreement; ?>">Add-on License Agreement</a>.</p>
        </div>
    </fieldset>

    <fieldset class="form-submit form-ctrls">
        <input class="btn submit" type="submit" value="Save License Key" />
    </fieldset>

    <?php echo form_close(); ?>
</div>