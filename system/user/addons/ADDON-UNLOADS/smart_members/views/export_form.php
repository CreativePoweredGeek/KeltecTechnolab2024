
<div class="box panel <?php echo (SM_APP_VER_L6) ? '' : 'ee6_box' ?>">

    <div class="form-standard">
        
        <?php
        if(SM_APP_VER_L6){
            ?>
            <h1>Create new export</h1>
            <?php
        }else
        {
            ?>
            <div class="panel-heading">
                <div class="form-btns form-btns-top">
                        <div class="title-bar title-bar--large">
                            <h3 class="title-bar__title">Create new export</h3>
                        </div>
                    </div>
              </div>
            <?php   
        }
        ?>
        <form action="<?=$callback?>" method="POST" enctype="multipart/form-data" class="export_form">
        <input type="hidden" name="XID" value="<?=$xid?>" />
        <input type="hidden" name="csrf_token" value="<?=$csrf_token?>" />
        <?php if(isset($token) && $token != ""){?>
        <input type="hidden" id="token" name="token" value="<?=$token?>" />
        <?php }?>

        <div class="se_main">
            <div class="se_entry">
                <div class="se_channel_recieve">

                    <div class="field-wrapper">
                        <div class="se_channel_title select_all_div mt0">
                            <h2><?= lang(SM_APP_VER_L6 ? 'member_groups' : 'member_roles');?>
                            <label class="select_all_div choice">
                                <input type="checkbox" class="check_all" value="true" /> <?= lang(SM_APP_VER_L6 ? 'select_all_member_groups': 'select_all_member_roles');?>
                            </label>
                            </h2>
                        </div>
                        <div class="se_member_groups se_default_fields d-fields">
                            <?php for ($i=0; $i < count($member_groups); $i++) { ?>
                            <div class="se_boxes <?php if(isset($data['settings']['member_groups']) && in_array($member_groups[$i]['group_id'], $data['settings']['member_groups'])) {echo 'active';}?>">
                                <span>✔</span>
                                <input type="checkbox" class="check_fields" name="settings[member_groups][]" value="<?= $member_groups[$i]['group_id']?>"<?php if(isset($data['settings']['member_groups']) && in_array($member_groups[$i]['group_id'], $data['settings']['member_groups'])) {echo "checked";}?> > <?= $member_groups[$i]['group_title']; ?>
                            </div>
                            <?php }?>
                        </div>
                    </div>

                    <div class="field-wrapper">
                        <div class="se_channel_title select_all_div">
                            <h2><?= lang('default_member_fields')?>
                            <label class="select_all_div choice">
                                <input type="checkbox" class="check_all" value="true" /> <?= lang('select_all_default_member_fields');?>
                            </label>
                            </h2>
                        </div>
                        <div class="se_member_groups se_default_fields d-fields">
                            <?php for ($i=0; $i < count($member_static_fields); $i++) { ?>
                            <div class="se_boxes <?php if(isset($data['settings']['member_static_fields']) && in_array($member_static_fields[$i]['name'], $data['settings']['member_static_fields'])) {echo 'active';}?>">
                                <span>✔</span>
                                <input type="checkbox" class="check_fields" name="settings[member_static_fields][]" value="<?= $member_static_fields[$i]['name']?>"<?php if(isset($data['settings']['member_static_fields']) && in_array($member_static_fields[$i]['name'], $data['settings']['member_static_fields'])) {echo "checked";}?> > <?= $member_static_fields[$i]['label']; ?>
                            </div>
                            <?php }?>
                        </div>
                    </div>

                    <?php if(isset($member_dynamic_fields) && is_array($member_dynamic_fields) && count($member_dynamic_fields) > 0){ ?>
                    <div class="field-wrapper">
                        <div class="se_channel_title select_all_div">
                            <h2><?= lang('dynamic_member_fields')?>
                            <label class="select_all_div choice">
                                <input type="checkbox" class="check_all" value="true" /> <?= lang('select_all_dynamic_member_fields');?>
                            </label>
                            </h2>
                        </div>
                        <div class="se_member_groups se_default_fields d-fields">
                            <?php for ($i=0; $i < count($member_dynamic_fields); $i++) { ?>
                            <div class="se_boxes <?php if(isset($data['settings']['member_dynamic_fields']) && in_array($member_dynamic_fields[$i]['m_field_id'], $data['settings']['member_dynamic_fields'])) {echo 'active';}?>">
                                <span>✔</span>
                                <input type="checkbox" class="check_fields" name="settings[member_dynamic_fields][]" value="<?= $member_dynamic_fields[$i]['m_field_id']?>"<?php if(isset($data['settings']['member_dynamic_fields']) && in_array($member_dynamic_fields[$i]['m_field_id'], $data['settings']['member_dynamic_fields'])) {echo "checked";}?> > <?= $member_dynamic_fields[$i]['m_field_label']; ?>
                            </div>
                            <?php }?>
                        </div>
                    </div>
                    <?php } ?>

                    <div class="field-wrapper">
                        <div class="se_channel_title select_all_div">
                            <h2><?= lang('general_settings');?>:</h2>
                        </div>

                        <fieldset class="col-group ">
                            <div class="setting-txt col w-8">
                                <h3><?= lang('export_name');?></h3>
                                <em><?= lang('export_name_desc');?></em>
                            </div>
                            <div class="setting-field col w-8 last">
                                <input type="text" name="name" value="<?php if(isset($data['name']) && $data['name'] != ''){echo $data['name']; }?>">
                            </div>
                        </fieldset>

                        <fieldset class="col-group ">
                            <div class="setting-txt col w-8">
                                <h3><?= lang('access_export_withou_login');?></h3>
                                <em><?= lang('access_export_withou_login_desc');?></em>
                            </div>
                            <div class="setting-field col w-8 last">
                                <select name="download_without_login" id="download_without_login">
                                    <option value="n" <?php if(isset($data['download_without_login']) && $data['download_without_login'] == 'n'){echo 'selected'; }?>><?= lang('no');?></option>
                                    <option value="y" <?php if(isset($data['download_without_login']) && $data['download_without_login'] == 'y'){echo 'selected'; }?>><?= lang('yes');?></option>
                                </select>
                            </div>
                        </fieldset>

                        <fieldset class="col-group ">
                            <div class="setting-txt col w-8">
                                <h3><?= lang('export_type');?></h3>
                                <em><?= lang('export_type_desc');?></em>
                            </div>
                            <div class="setting-field col w-8 last">
                                <select name="type" id="type">
                                    <option value="private" <?php if(isset($data['type']) && $data['type'] == 'private'){echo 'selected'; }?>><?= lang('private');?></option>
                                    <option value="public" <?php if(isset($data['type']) && $data['type'] == 'public'){echo 'selected'; }?>><?= lang('public');?></option>
                                </select>
                            </div>
                        </fieldset>
                        
                    </div>

                    <fieldset class="field-wrapper form-ctrls">
                        <select name="format">
                            <option <?php if(isset($data['format']) && $data['format'] == 'XML'){echo 'selected'; }?>>XML</option>
                            <option <?php if(isset($data['format']) && $data['format'] == 'CSV'){echo 'selected'; }?>>CSV</option>
                        </select>
                        &nbsp;&nbsp; <input type="submit" name="submit" value="Save" class="submit se_export_btn btn" data-work-text="Saving...">
                    </fieldset>
                </div>
            </div>
        </div>

    </div>

</form>

<div class="error-message" style="display: none;"><h3></h3></div>
</div>