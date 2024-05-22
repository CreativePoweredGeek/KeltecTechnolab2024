<div class="box">
    <form action="<?=$callback?>" method="POST" enctype="multipart/form-data" class="export_form">
    <input type="hidden" name="XID" value="<?=$xid?>" />
    <input type="hidden" name="csrf_token" value="<?=$csrf_token?>" />
    <?php if(isset($token) && $token != ""){?>
    <input type="hidden" id="token" name="token" value="<?=$token?>" />
    <?php }?>

    <div class="se_main">
        <div class="se_entry">
            <div class="se_main_part">
                <div class="se_channel">
                    <!-- List all channel -->
                    <div class="se_channel_title">
                        <h4><?= lang('channel');?></h4>
                    </div>
                    <div class="se_channel_data">
                        <select name="settings[channel_id]" class="select_channel" id="se_channel_01">
                            <option value="" selected=""><?= lang('select_channel');?></option>
                            <?php for($i=0; $i<count($result); $i++){?>
                            <option value="<?=$result[$i]['channel_id']?>" <?php if(isset($data['settings']['channel_id']) && $data['settings']['channel_id'] == $result[$i]['channel_id']){echo 'selected';}?>><?=$result[$i]['channel_title']?></option>
                            <?php }?>
                        </select>
                    </div>
                </div>
                <div class="se_channel">
                    <!-- List all Statuses -->
                    <div class="se_channel_title">
                        <h4><?= lang('status');?></h4>
                    </div>
                    <div class="se_channel_data">
                        <select name="settings[status]" class="select_channel" id="se_status">
                            <option value="ALL" selected=""><?= lang('all');?></option>
                            <?php foreach ($status as $key => $value){?>
                                <option value="<?=$value['status']?>" <?php if(isset($data['settings']['status']) && $data['settings']['status'] == $value['status']){echo "selected";}?>><?=$value['status']?></option>
                            <?php }?>
                        </select>
                    </div>
                </div>
                <div class="se_channel">
                    <div class="loading-indicator">
                        <img src="<?= $loading_image?>">
                    </div>
                </div>
            </div>
            <div class="se_channel_recieve">

                <div class="field-wrapper" <?php if(! $edit){ echo 'style="display: none;"'; }?>>
                    <div class="se_channel_title select_all_div">
                        <h2><?= lang('default_fields')?>:
                        <label class="select_all_div choice">
                            <input type="checkbox" class="check_all" value="true" /> <?= lang('select_all_channel_fields');?>
                        </label>
                        </h2>
                    </div>
                    <div class="se_default_fields d-fields">
                        <?php for ($i=0; $i < count($default_fields); $i++) { ?>
                        <div class="se_boxes <?php if(isset($data['settings']['default_fields']) && in_array($default_fields[$i], $data['settings']['default_fields'])) {echo 'active';}?>">
                            <span>✔</span>
                            <input type="checkbox" class="check_fields" name="settings[default_fields][]" value="<?= $default_fields[$i]?>"<?php if(isset($data['settings']['default_fields']) && in_array($default_fields[$i], $data['settings']['default_fields'])) {echo "checked";}?> > <?= lang($default_fields[$i]);?>
                        </div>
                        <?php }?>
                    </div>
                </div>

                <div class="field-wrapper" <?php if(! $edit){ echo 'style="display: none;"'; }?>>
                    <div class="se_channel_title select_all_div">
                        <h2><?= lang('custom_fields');?>:
                            <label class="select_all_div choice">
                                <input type="checkbox" class="check_all" value="true" /> <?= lang('select_all_channel_fields');?>
                            </label>
                        </h2>
                    </div>
                    <div class="se_channel_fields d-fields">
                        <?php if(isset($custom_fields) && is_array($custom_fields) && count($custom_fields) > 0){
                            for ($i = 0; $i < count($custom_fields); $i++) {?>
                                <div class="se_boxes <?php if(isset($data['settings']['custom_fields']) && in_array($custom_fields[$i]['field_id'], $data['settings']['custom_fields'])){ echo 'active';}?>">
                                    <span>&#10004</span>
                                    <input type="checkbox" class="check_fields" name="settings[custom_fields][]" value="<?= $custom_fields[$i]['field_id']; ?>" <?php if(isset($data['settings']['custom_fields']) && in_array($custom_fields[$i]['field_id'], $data['settings']['custom_fields'])){ echo 'checked';}?> />
                                    <?= $custom_fields[$i]['field_label']; ?> (<?= $custom_fields[$i]['field_type']; ?>)
                                    <?php if($custom_fields[$i]['field_type'] == "relationship"){ ?>
                                    <div class="se-inside_main">
                                        <div class="se_inside_rel relation_<?= $custom_fields[$i]['field_id']?>">
                                            <label>(To Identify Relationships When Import)</label>
                                            <select class="se_inside_select" name="settings[relationship_field][<?= $custom_fields[$i]['field_id']?>]">
                                                <option value="title" <?php if(isset($data['settings']['relationship_field'][$custom_fields[$i]['field_id']]) && $data['settings']['relationship_field'][$custom_fields[$i]['field_id']] == "title"){echo "selected";}?>>Title</option>
                                                <option value="url_title" <?php if(isset($data['settings']['relationship_field'][$custom_fields[$i]['field_id']]) && $data['settings']['relationship_field'][$custom_fields[$i]['field_id']] == "url_title"){echo "selected";}?>>Url Title</option>
                                                <option value="entry_id" <?php if(isset($data['settings']['relationship_field'][$custom_fields[$i]['field_id']]) && $data['settings']['relationship_field'][$custom_fields[$i]['field_id']] == "entry_id"){echo "selected";}?>>Entry ID</option>
                                            </select>
                                            <?php ?>
                                        </div>
                                    </div>
                                    <?php } elseif($custom_fields[$i]['field_type'] == "playa"){ ?>
                                    <div class="se-inside_main">
                                        <div class="se_inside_rel relation_<?= $custom_fields[$i]['field_id']?>">
                                            <label>(To Identify Relationships When Import)</label>
                                            <select class="se_inside_select" name="settings[playa_field][<?= $custom_fields[$i]['field_id']?>]">
                                                <option value="title" <?php if(isset($data['settings']['playa_field'][$custom_fields[$i]['field_id']]) && $data['settings']['playa_field'][$custom_fields[$i]['field_id']] == "title"){echo "selected";}?>>Title</option>
                                                <option value="url_title" <?php if(isset($data['settings']['playa_field'][$custom_fields[$i]['field_id']]) && $data['settings']['playa_field'][$custom_fields[$i]['field_id']] == "url_title"){echo "selected";}?>>Url Title</option>
                                                <option value="entry_id" <?php if(isset($data['settings']['playa_field'][$custom_fields[$i]['field_id']]) && $data['settings']['playa_field'][$custom_fields[$i]['field_id']] == "entry_id"){echo "selected";}?>>Entry ID</option>
                                            </select>
                                            <?php ?>
                                        </div>
                                    </div>
                                    <?php } elseif($custom_fields[$i]['field_type'] == "grid" && $custom_fields[$i]['grid_rel'] != "NA"){ 
                                        for ($j=0; $j < count($custom_fields[$i]['grid_rel']); $j++) { ?>
                                            <?php if($j == 0){?>
                                                <div class="se-inside_main">
                                            <?php } ?>
                                            
                                            <div class="se_inside_rel relation_<?= $custom_fields[$i]['grid_rel'][$j]['col_id']; ?>">
                                                <label>
                                                    <em><?= $custom_fields[$i]['grid_rel'][$j]['col_label']?></em> 
                                                    (To Identify Relationships When Import)
                                                </label> 
                                                <select class="se_inside_select" name="settings[grid_relationship][<?= $custom_fields[$i]['field_id']?>][<?= $custom_fields[$i]['grid_rel'][$j]['col_id']?>]">
                                                    <option value="title" <?php if(isset($data['settings']['grid_relationship'][$custom_fields[$i]['field_id']][$custom_fields[$i]['grid_rel'][$j]['col_id']]) && $data['settings']['grid_relationship'][$custom_fields[$i]['field_id']][$custom_fields[$i]['grid_rel'][$j]['col_id']] == 'title'){echo 'selected';}?>>Title</option>
                                                    <option value="url_title" <?php if(isset($data['settings']['grid_relationship'][$custom_fields[$i]['field_id']][$custom_fields[$i]['grid_rel'][$j]['col_id']]) && $data['settings']['grid_relationship'][$custom_fields[$i]['field_id']][$custom_fields[$i]['grid_rel'][$j]['col_id']] == 'url_title'){echo 'selected';}?>>Url Title</option>
                                                    <option value="entry_id" <?php if(isset($data['settings']['grid_relationship'][$custom_fields[$i]['field_id']][$custom_fields[$i]['grid_rel'][$j]['col_id']]) && $data['settings']['grid_relationship'][$custom_fields[$i]['field_id']][$custom_fields[$i]['grid_rel'][$j]['col_id']] == 'entry_id'){echo 'selected';}?>>Entry ID</option>
                                                </select>
                                            </div>
                                            <?php if($j == count($custom_fields[$i]['grid_rel']) - 1){?>
                                                </div>
                                            <?php }
                                        }
                                    } elseif($custom_fields[$i]['field_type'] == "matrix" && $custom_fields[$i]['matrix_rel'] != "NA"){ 
                                        for ($j=0; $j < count($custom_fields[$i]['matrix_rel']); $j++) { ?>
                                            <?php if($j == 0){?>
                                                <div class="se-inside_main">
                                            <?php } ?>
                                            
                                            <div class="se_inside_rel relation_<?= $custom_fields[$i]['matrix_rel'][$j]['col_id']; ?>">
                                                <label>
                                                    <em><?= $custom_fields[$i]['matrix_rel'][$j]['col_label']?></em> 
                                                    (To Identify Relationships When Import)
                                                </label> 
                                                <select class="se_inside_select" name="settings[matrix_playa][<?= $custom_fields[$i]['field_id']?>][<?= $custom_fields[$i]['matrix_rel'][$j]['col_id']?>]">
                                                    <option value="title" <?php if(isset($data['settings']['matrix_playa'][$custom_fields[$i]['field_id']][$custom_fields[$i]['matrix_rel'][$j]['col_id']]) && $data['settings']['matrix_playa'][$custom_fields[$i]['field_id']][$custom_fields[$i]['matrix_rel'][$j]['col_id']] == 'title'){echo 'selected';}?>>Title</option>
                                                    <option value="url_title" <?php if(isset($data['settings']['matrix_playa'][$custom_fields[$i]['field_id']][$custom_fields[$i]['matrix_rel'][$j]['col_id']]) && $data['settings']['matrix_playa'][$custom_fields[$i]['field_id']][$custom_fields[$i]['matrix_rel'][$j]['col_id']] == 'url_title'){echo 'selected';}?>>Url Title</option>
                                                    <option value="entry_id" <?php if(isset($data['settings']['matrix_playa'][$custom_fields[$i]['field_id']][$custom_fields[$i]['matrix_rel'][$j]['col_id']]) && $data['settings']['matrix_playa'][$custom_fields[$i]['field_id']][$custom_fields[$i]['matrix_rel'][$j]['col_id']] == 'entry_id'){echo 'selected';}?>>Entry ID</option>
                                                </select>
                                            </div>
                                            <?php if($j == count($custom_fields[$i]['matrix_rel']) - 1){?>
                                                </div>
                                            <?php }
                                        }
                                    }elseif($custom_fields[$i]['field_type'] == "fluid_field" && isset($custom_fields[$i]['rel']) && is_array($custom_fields[$i]['rel']) && count($custom_fields[$i]['rel']) > 0){?>
                                        <div class="se-inside_main">
                                            <?php if(isset($custom_fields[$i]['rel']['relationship'])){?>
                                                <?php for ($j=0; $j < count($custom_fields[$i]['rel']['relationship']); $j++) { ?>
                                                    <div class="se_inside_rel relation_<?= $custom_fields[$i]['rel']['relationship'][$j]['field_id']; ?>">
                                                        <label>
                                                            <em><?= $custom_fields[$i]['rel']['relationship'][$j]['field_label']?></em> 
                                                            (To Identify Relationships When Import)
                                                        </label> 
                                                        <select class="se_inside_select" name="settings[fluid_field][<?= $custom_fields[$i]['field_id']?>][<?= $custom_fields[$i]['rel']['relationship'][$j]['field_id']?>]">
                                                            <option value="title" <?php if(isset($data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['relationship'][$j]['field_id']]) && $data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['relationship'][$j]['field_id']] == 'title'){echo 'selected';}?>>Title</option>
                                                            <option value="url_title" <?php if(isset($data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['relationship'][$j]['field_id']]) && $data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['relationship'][$j]['field_id']] == 'url_title'){echo 'selected';}?>>Url Title</option>
                                                            <option value="entry_id" <?php if(isset($data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['relationship'][$j]['field_id']]) && $data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['relationship'][$j]['field_id']] == 'entry_id'){echo 'selected';}?>>Entry ID</option>
                                                        </select>
                                                    </div>
                                                <?php } ?>

                                            <?php }if(isset($custom_fields[$i]['rel']['playa'])){?>
                                                <?php for ($j=0; $j < count($custom_fields[$i]['rel']['playa']); $j++) { ?>
                                                    <div class="se_inside_rel relation_<?= $custom_fields[$i]['rel']['playa'][$j]['field_id']; ?>">
                                                        <label>
                                                            <em><?= $custom_fields[$i]['rel']['playa'][$j]['field_label']?></em> 
                                                            (To Identify Relationships When Import)
                                                        </label> 
                                                        <select class="se_inside_select" name="settings[fluid_field][<?= $custom_fields[$i]['field_id']?>][<?= $custom_fields[$i]['rel']['playa'][$j]['field_id']?>]">
                                                            <option value="title" <?php if(isset($data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['playa'][$j]['field_id']]) && $data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['playa'][$j]['field_id']] == 'title'){echo 'selected';}?>>Title</option>
                                                            <option value="url_title" <?php if(isset($data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['playa'][$j]['field_id']]) && $data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['playa'][$j]['field_id']] == 'url_title'){echo 'selected';}?>>Url Title</option>
                                                            <option value="entry_id" <?php if(isset($data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['playa'][$j]['field_id']]) && $data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['playa'][$j]['field_id']] == 'entry_id'){echo 'selected';}?>>Entry ID</option>
                                                        </select>
                                                    </div>
                                                <?php } ?>
                                            
                                            <?php }if(isset($custom_fields[$i]['rel']['grid_rel'])){?>
                                                <?php for ($j=0; $j < count($custom_fields[$i]['rel']['grid_rel']); $j++) { ?>
                                                    <div class="se_inside_rel relation_<?= $custom_fields[$i]['rel']['grid_rel'][$j]['col_id']; ?>">
                                                        <label>
                                                            <em><?= $custom_fields[$i]['rel']['grid_rel'][$j]['col_label']?></em> 
                                                            (To Identify Relationships When Import)
                                                        </label> 
                                                        <select class="se_inside_select" name="settings[fluid_field][<?= $custom_fields[$i]['field_id']?>][<?= $custom_fields[$i]['rel']['grid_rel'][$j]['col_id']?>]">
                                                            <option value="title" <?php if(isset($data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['grid_rel'][$j]['col_id']]) && $data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['grid_rel'][$j]['col_id']] == 'title'){echo 'selected';}?>>Title</option>
                                                            <option value="url_title" <?php if(isset($data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['grid_rel'][$j]['col_id']]) && $data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['grid_rel'][$j]['col_id']] == 'url_title'){echo 'selected';}?>>Url Title</option>
                                                            <option value="entry_id" <?php if(isset($data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['grid_rel'][$j]['col_id']]) && $data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['grid_rel'][$j]['col_id']] == 'entry_id'){echo 'selected';}?>>Entry ID</option>
                                                        </select>
                                                    </div>
                                                <?php } ?>
                                            <?php }if(isset($custom_fields[$i]['rel']['matrix_rel'])){?>
                                                <?php for ($j=0; $j < count($custom_fields[$i]['rel']['matrix_rel']); $j++) { ?>
                                                    <div class="se_inside_rel relation_<?= $custom_fields[$i]['rel']['matrix_rel'][$j]['col_id']; ?>">
                                                        <label>
                                                            <em><?= $custom_fields[$i]['rel']['matrix_rel'][$j]['col_label']?></em> 
                                                            (To Identify Relationships When Import)
                                                        </label> 
                                                        <select class="se_inside_select" name="settings[fluid_field][<?= $custom_fields[$i]['field_id']?>][<?= $custom_fields[$i]['rel']['matrix_rel'][$j]['col_id']?>]">
                                                            <option value="title" <?php if(isset($data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['matrix_rel'][$j]['col_id']]) && $data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['matrix_rel'][$j]['col_id']] == 'title'){echo 'selected';}?>>Title</option>
                                                            <option value="url_title" <?php if(isset($data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['matrix_rel'][$j]['col_id']]) && $data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['matrix_rel'][$j]['col_id']] == 'url_title'){echo 'selected';}?>>Url Title</option>
                                                            <option value="entry_id" <?php if(isset($data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['matrix_rel'][$j]['col_id']]) && $data['settings']['fluid_field'][$custom_fields[$i]['field_id']][$custom_fields[$i]['rel']['matrix_rel'][$j]['col_id']] == 'entry_id'){echo 'selected';}?>>Entry ID</option>
                                                        </select>
                                                    </div>
                                                <?php } ?>
                                            <?php } ?>
                                        </div>
                                    <?php }?>
                                </div>
                            <?php }
                        } ?>
                    </div>
                </div>

                <div class="field-wrapper other-general-fields" <?php if(! $edit){ echo 'style="display: none;"'; }?>>
                    <div class="se_channel_title select_all_div">
                        <h2><?= lang('other_modules_to_export');?>:
                            <label class="select_all_div choice">
                                <input type="checkbox" class="check_all" value="true" /> <?= lang('select_all_channel_fields');?>
                            </label>
                        </h2>
                    </div>
                    <div class="d-fields">
                        <div class="se_boxes wrap-cats <?php if(isset($data['settings']['general_settings']['categories']) && $data['settings']['general_settings']['categories'] == "yes"){echo 'active';}?>" <?php if( ! (isset($categories) && $categories === true) ){ echo 'style="display: none;"';}?>>
                            <span>&#10004</span>
                            <input type="checkbox" class="check_fields" name="settings[general_settings][categories]" value="yes" <?php if(isset($data['settings']['general_settings']['categories']) && $data['settings']['general_settings']['categories'] == "yes"){echo 'checked';}?> > <?= lang('categories');?>
                        </div>

                        <?php if($seo_lite === true){ ?>
                        <div class="se_boxes <?php if(isset($data['settings']['general_settings']['seo_lite']) && $data['settings']['general_settings']['seo_lite'] == "yes"){echo 'active';}?>">
                            <span>&#10004</span>
                            <input type="checkbox" class="check_fields" name="settings[general_settings][seo_lite]" value="yes"<?php if(isset($data['settings']['general_settings']['seo_lite']) && $data['settings']['general_settings']['seo_lite'] == "yes"){echo 'checked';}?> > <?= lang('seo_lite');?>
                        </div>
                        <?php }?>
                        
                        <?php if($pages === true){ ?>
                        <div class="se_boxes <?php if(isset($data['settings']['general_settings']['pages']) && $data['settings']['general_settings']['pages'] == "yes"){echo 'active';}?>">
                            <span>&#10004</span>
                            <input type="checkbox" class="check_fields" name="settings[general_settings][pages]" value="yes" <?php if(isset($data['settings']['general_settings']['pages']) && $data['settings']['general_settings']['pages'] == "yes"){echo 'checked';}?> > <?= lang('pages');?>
                        </div>
                        <?php }?>

                    </div>
                </div>

                <div class="field-wrapper" <?php if(! $edit){ echo 'style="display: none;"'; }?>>
                    <div class="se_channel_title select_all_div">
                        <h2><?= lang('filters');?>:</h2>
                    </div>
                    <fieldset class="col-group se-radio last">
                        <div class="setting-txt col  w-8">
                            <h3>Enable Date wise filter?</h3>
                            <em>When set to <b>yes</b>, You can select Entry date "start from" and "end to" to filter your export.</em>
                        </div>
                        <div class="setting-field col w-8 last toggle-item" toggle="se-date-filter">
                            <label class="choice mr yes <?php if(isset($data['settings']['filters']['date']) && $data['settings']['filters']['date'] == "y"){echo 'chosen';}?>">
                                <input type="radio" name="settings[filters][date]" value="y" <?php if(isset($data['settings']['filters']['date']) && $data['settings']['filters']['date'] == "y"){echo 'checked';}?> >
                                Yes
                            </label>
                            <label class="choice no <?php if(! isset($data['settings']['filters']['date']) || ( isset($data['settings']['filters']['date']) && $data['settings']['filters']['date'] == "n")){echo 'chosen';}?>">
                                <input type="radio" name="settings[filters][date]" value="n" <?php if(! isset($data['settings']['filters']['date']) || ( isset($data['settings']['filters']['date']) && $data['settings']['filters']['date'] == "n")){echo 'checked';}?>>
                                No
                            </label>
                        </div>
                    </fieldset>

                    <fieldset class="col-group se-date-filter hidden">
                        <div class="setting-txt col w-8">
                            <h3></span>Start Date</h3>
                            <em>Minimum date of entry to export</em>
                        </div>
                        <div class="setting-field col w-8 last">
                            <input type="text" name="settings[filters][start_date]" rel="date-picker"
                                <?php if(isset($data['settings']['filters']['start_date'])){ ?>
                                value="<?= ee()->localize->human_time($data['settings']['filters']['start_date']);?>"
                                data-timestamp="<?= $data['settings']['filters']['start_date']?>"
                                <?php } ?> >
                        </div>
                    </fieldset>

                    <fieldset class="col-group se-date-filter hidden">
                        <div class="setting-txt col w-8">
                            <h3></span>End Date</h3>
                            <em>Maximum date of entry to export</em>
                        </div>
                        <div class="setting-field col w-8 last">
                            <input type="text" name="settings[filters][end_date]" rel="date-picker"
                                <?php if(isset($data['settings']['filters']['end_date'])){ ?>
                                value="<?= ee()->localize->human_time($data['settings']['filters']['end_date']);?>"
                                data-timestamp="<?= $data['settings']['filters']['end_date']?>"
                                <?php } ?> >
                        </div>
                    </fieldset>
                </div>
                <div class="field-wrapper" <?php if(! $edit){ echo 'style="display: none;"'; }?>>
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

                    <fieldset class="col-group">
                        <div class="setting-txt col w-8">
                            <h3><?= lang('export_procedure');?></h3>
                            <em><?= lang('export_procedure_desc');?></em>
                        </div>
                        <div class="setting-field col w-8 last">
                            <select name="settings[procedure]" id="procedure">
                                <option value="normal" <?php if(isset($data['settings']['procedure']) && $data['settings']['procedure'] == 'normal'){echo 'selected'; }?>><?= lang('normal');?></option>
                                <option value="ajax" <?php if(isset($data['settings']['procedure']) && $data['settings']['procedure'] == 'ajax'){echo 'selected'; }?>><?= lang('ajax');?></option>
                            </select>
                        </div>
                    </fieldset>

                    <fieldset class="col-group batches_wrapper" <?php if( ! (isset($data['settings']['procedure']) && $data['settings']['procedure'] == 'ajax')){ echo 'style="display: none;"'; }?>>
                        <div class="setting-txt col w-8">
                            <h3><?= lang('batches');?></h3>
                            <em><?= lang('batches_desc');?></em>
                        </div>
                        <div class="setting-field col w-8 last">
                            <select name="settings[batches]" id="batches">
                                <option value="5" <?php if(isset($data['settings']['batches']) && $data['settings']['batches'] == '5'){echo 'selected'; }?>>5</option>
                                <option value="10" <?php if(isset($data['settings']['batches']) && $data['settings']['batches'] == '10'){echo 'selected'; }?>>10</option>
                                <option value="20" <?php if(isset($data['settings']['batches']) && $data['settings']['batches'] == '20'){echo 'selected'; }?>>20</option>
                                <option value="50" <?php if(isset($data['settings']['batches']) && $data['settings']['batches'] == '50'){echo 'selected'; }?>>50</option>
                                <option value="100" <?php if(isset($data['settings']['batches']) && $data['settings']['batches'] == '100'){echo 'selected'; }?>>100</option>
                                <option value="200" <?php if(isset($data['settings']['batches']) && $data['settings']['batches'] == '200'){echo 'selected'; }?>>200</option>
                                <option value="500" <?php if(isset($data['settings']['batches']) && $data['settings']['batches'] == '500'){echo 'selected'; }?>>500</option>
                            </select>
                        </div>
                    </fieldset>

                </div>
                <fieldset class="field-wrapper form-ctrls" <?php if(! $edit){ echo 'style="display: none;"'; }?>>
                    <select name="format">
                        <option <?php if(isset($data['format']) && $data['format'] == 'XML'){echo 'selected'; }?>>XML</option>
                        <option <?php if(isset($data['format']) && $data['format'] == 'CSV'){echo 'selected'; }?>>CSV</option>
                    </select>
                    &nbsp;&nbsp; <input type="submit" name="submit" value="Save" class="submit se_export_btn btn" data-work-text="Saving...">
                </fieldset>

            </div>
        </div>
    </div>
</form>

<div class="error-message" style="display: none;"><h3></h3></div>
</div>