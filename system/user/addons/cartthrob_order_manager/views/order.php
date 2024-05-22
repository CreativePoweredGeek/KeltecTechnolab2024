<?php
    ee()->load->library("number");
?>

    <div class="form-standard add-mrg-bottom">

        <div class="app-notice-wrap">
            <?= ee('CP/Alert')->getAllInlines() ?>
        </div>

        <h1 class="add-mrg-bottom">
            <?php if ($refunded) echo "<del>"; ?>
            <?= lang('ct.om.manage_order')?>: <?= $view['title'] ?> (<a href='<?=ee('CP/URL')->make('publish/edit/entry/'.$view['entry_id'])?>'><?= $view['entry_id']?> &raquo;</a>)
            <?php if ($refunded) echo "</del> ".lang('refunded'); ?>
        </h1>

        <div class="btn-block add-mrg-bottom">
            <?php
                if ($print_invoice) {
                    echo '	<a class="btn submit btn-right" href="'.$print_invoice.'" target="_blank"  >'.lang("ct.om.print_invoice").'</a> ';
                }

                if ($print_packing_slip) {
                    echo '	<a class="btn submit" href="'.$print_packing_slip.'" target="_blank"  >'.lang("ct.om.print_packing_slip").'</a> ';
                }

                if ($custom_templates) {
                    foreach ($custom_templates as $key => $template_data) {
                        echo '	<a class="btn submit" href="'.$template_data['link'].'" target="_blank"  >'.lang("ct.om.print")." ".$template_data['name'].'</a> ';
                    }
                }
            ?>
        </div>

        <div class="tbl-ctrls">
            <div class="tbl-wrap">
                <table class="add-mrg-bottom">
                    <thead class="">
                        <tr>
                            <th>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($view['order_transaction_id'])): ?>
                            <tr>
                                <td>
                                    <strong><?= lang('ct.om.transaction_id') ?>: </strong> <?= $view['order_transaction_id'] ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td>
                                <strong><?= lang('ct.om.payment_method') ?>:</strong> <?=ucwords(str_replace("_", " ", $view['order_payment_gateway']))?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong><?= lang('ct.om.subtotal') ?> : </strong> <?= $view['orders_subtotal']?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong><?= lang('ct.om.tax') ?>: </strong> <?= $view['orders_tax']?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong><?= lang('ct.om.shipping') ?>: </strong> <?= $view['orders_shipping']?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong><?= lang('ct.om.total') ?>: </strong> <?= $view['orders_total']?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <a class="btn submit" href="<?= ee('CP/URL')->make('addons/settings/cartthrob_order_manager/delete/'.$view['entry_id']) ?>"><?= lang('ct.om.delete_order')?></a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tbl-ctrls">
            <div class="tbl-wrap">
                <table class="add-mrg-bottom" border="0" cellspacing="0" cellpadding="0">
                    <thead class="">
                    <tr>
                        <th>
                            <?= lang('ct.om.item_title') ?>
                        </th>
                        <th>
                            <?= lang('ct.om.item_price') ?>
                        </th>
                        <th>
                            <?= lang('ct.om.item_subtotal') ?>
                        </th>
                        <th>
                            <?= lang('ct.om.item_quantity') ?>
                        </th>
                        <th>
                            <?= lang('ct.om.shipping') ?>
                        </th>
                        <th>
                            <?= lang('ct.om.weight') ?>
                        </th>
                        <th>
                            <?= lang('ct.om.entry_id') ?>
                        </th>
                        <th>
                            <?= lang('ct.om.item_taxable') ?>
                        </th>
                        <th>
                            <?= lang('ct.om.item_shippable') ?>
                        </th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php foreach ($view['order_items'] as $item): ?>
                        <tr>
                            <td>
                                <a href="<?= ee('CP/URL')->make('publish/edit/entry/'.$item['entry_id']) ?>"><?= $item['title'] ?> (<?= $item['entry_id'] ?>) &raquo; </a>
                            </td>
                            <td>
                                <?=ee()->number->format($item['price']) ?> (<?=ee()->number->format($item['price_plus_tax']) ?>)
                            </td>
                            <td>
                                <?=ee()->number->format($item['price'] * $item['quantity']) ?> (<?=ee()->number->format($item['price_plus_tax']* $item['quantity']) ?>)
                            </td>
                            <td>
                                <?= $item['quantity']?>
                            </td>
                            <td>
                                <?= ee()->number->format($item['shipping']) ?>
                            </td>
                            <td>
                                <?= $item['weight']?>
                            </td>
                            <td>
                                <?= $item['entry_id']?>
                            </td>
                            <td>
                                <?php
                                    if ($item['no_shipping']) {
                                        echo "no";
                                    } else {
                                        echo "yes";
                                    }
                                ?>
                            </td>
                            <td>
                                <?php
                                    if ($item['no_tax']) {
                                        echo "no";
                                    } else {
                                        echo "yes";
                                    }
                                ?>
                            </td>
                        </tr>

                        <?php
                            $skip_keys = array(
                                'shipping',
                                'weight',
                                'price_plus_tax',
                                'price',
                                'quantity',
                                'title',
                                'entry_id',
                                'order_id',
                                'row_order',
                                'row_id',
                                'no_tax',
                                'no_shipping',
                            );
                            $count = 0;
                            foreach ($item as $key=> $value):
                                if (!in_array($key, $skip_keys)):
                                    $count ++;
                                    if ($count ==1) {
                                        echo '<tr class="'.alternator('even', 'odd').'">
                                                        <td></td>
                                                        <td colspan="8">';
                                    }

                                    if ($key == "sub_items") {
                                        echo '<strong>'.lang('ct.om.packaged_items').'</strong><br>';
                                        foreach ($value as $k => $v) {
                                            echo $v['title'] . " x " .$v['quantity'];
                                            echo ' <a href="'.ee('CP/URL')->make('publish/edit/entry/'.$item['entry_id']).'">'.lang('ct.om.view_product').'&nbsp;&raquo;</a><br>';
                                            foreach ($v as $kk => $vv) {
                                                if (!in_array($kk, $skip_keys)) {
                                                    if ($vv) {
                                                        echo "&nbsp;&nbsp;<strong>". ucwords(str_replace("_", " ", $kk))."</strong>: ". $vv."<br>";
                                                    }
                                                }
                                            }
                                        }
                                        echo "<br>";
                                    } else {
                                        if ($value) {
                                            echo '<strong>'.ucwords(str_replace("_", " ", $key)).'</strong>: ';
                                            echo ($key == "entry_date") ? ee()->localize->format_date('%m-%d-%Y %h:%i %a', $value, true) : $value;
                                            echo "<br>";
                                        }
                                    }
                                endif;
                            endforeach;

                            if ($count) {
                                echo "</td></tr>";
                            }
                        ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tbl-ctrls">
            <table class="add-mrg-bottom" border="0" cellspacing="0" cellpadding="0">
                <thead class="">
                    <tr>
                        <th>
                            <strong><?= lang('ct.om.billing_address') ?></strong>
                        </th>
                        <th>
                            <strong><?= lang('ct.om.shipping_address') ?></strong>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td valign="top">
                            <?= $view['orders_billing_first_name']?> <?= $view['orders_billing_last_name']?><br>
                            <?= $view['orders_billing_company']?> <br>
                            <?php if (isset($view['order_full_billing_address']) && !empty($view['order_full_billing_address'])): ?>
                                <?= nl2br($view['order_full_billing_address'], false) ?><br>
                            <?php else: ?>
                                <?= $view['orders_billing_address']?> <?= $view['orders_billing_address2']?> <br>
                                <?= $view['orders_billing_city']?>, <?= $view['orders_billing_state']?> <?= $view['orders_billing_zip']?><br>
                            <?php endif; ?>
                            <?= $view['orders_country_code']?><br><br>

                            <?php if (ee()->cartthrob->store->config('orders_convert_country_code')): ?>
                                <?= ee()->locales->country_from_country_code($view['orders_country_code']); ?>
                            <?php else: ?>
                                <?= $view['orders_country_code'] ?>
                            <?php endif; ?>

                            <?php
                                if (!empty($view['author_id'])) {
                                    echo "<a href='".$href_member.$view['author_id']."'>(".$view['author_id'].") ". lang('ct.om.member_details')." &raquo;</a><br> ";
                                }
                            ?>
                        </td>
                        <td valign="top">
                            <?= $view['orders_shipping_first_name']?> <?= $view['orders_shipping_last_name']?> <br>
                            <?= $view['orders_shipping_company']?> <br>
                            <?php if (isset($view['order_full_shipping_address']) && !empty($view['order_full_shipping_address'])): ?>
                                <?= nl2br($view['order_full_shipping_address'], false) ?><br>
                            <?php else: ?>
                                <?= $view['orders_shipping_address']?> <?= $view['orders_shipping_address2']?> <br>
                                <?= $view['orders_shipping_city']?>, <?= $view['orders_shipping_state']?> <?= $view['orders_shipping_zip']?><br>
                            <?php endif; ?>

                            <?php if (ee()->cartthrob->store->config('orders_convert_country_code')): ?>
                                <?= ee()->locales->country_from_country_code($view['orders_shipping_country_code']); ?>
                            <?php else: ?>
                                <?= $view['orders_shipping_country_code'] ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($form_capture): ?>
        <div class="form-standard add-mrg-bottom">
            <div class="form-btns form-btns-top">
                <h1><?= lang('ct.om.capture') ?></h1>
            </div>
            <?= $form_capture ?>
                <input type="hidden" value="<?= $view['entry_id']?>" name="id" />

                <fieldset>
                    <?= ee('CP/Alert')->makeInline('security')
                        ->asAttention()
                        ->withTitle(lang('ct.om.important'))
                        ->addToBody(lang('ct.om.capture_description'))
                        ->cannotClose()
                        ->render(); ?>
                </fieldset>

                <fieldset>
                    <div class="field-instruct">
                        <label><?= lang('ct.om.total') ?></label>
                    </div>
                    <div class="field-control>">
                        <input id="total" type="text" value="<?= $view['orders_total']?>" name="total" />
                    </div>
                </fieldset>

                <div class="form-btns">
                    <input class="btn" type="submit" name="submit" value="<?= lang('ct.om.capture_order') ?>">
                </div>
            <?= form_close(); ?>
        </div>
    <?php endif; ?>

    <?php if ($form_void): ?>
        <div class="form-standard add-mrg-bottom">
            <div class="form-btns form-btns-top">
                <h1><?= lang('ct.om.void') ?></h1>
            </div>
            <?= $form_void; ?>
                <input type="hidden" value="<?= $view['entry_id'] ?>" name="id" />
                <fieldset>
                    <?= ee('CP/Alert')->makeInline('security')
                        ->asAttention()
                        ->withTitle(lang('ct.om.important'))
                        ->addToBody(lang('ct.om.void_description'))
                        ->cannotClose()
                        ->render(); ?>
                </fieldset>
                <div class="form-btns">
                    <input class="btn" type="submit" name="submit" value="<?= lang('ct.om.void_order') ?>">
                </div>
            <?= form_close(); ?>
        </div>
    <?php endif; ?>

    <div class="form-standard add-mrg-bottom">
        <div class="form-btns form-btns-top">
            <h1><?= lang('ct.om.resend_email') ?></h1>
        </div>
        <?= form_open(ee('CP/URL')->make('addons/settings/cartthrob_order_manager/resend_email')) ?>
            <input type="hidden" value="<?= $view['entry_id']?>" name="id">
            <input type="hidden" value="order" name="return">

            <fieldset>
                <div class="field-instruct">
                    <label><?= lang('ct.om.email_address') ?></label>
                </div>
                <div class="field-control>">
                    <input id="resend_email_address" type="text" value="<?= $view['orders_customer_email']?>" name="email_address" />
                </div>
            </fieldset>
            <fieldset>
                <div class="field-instruct">
                    <label><?= lang('ct.om.subject') ?></label>
                </div>
                <div class="field-control>">
                    <input id="subject" type="text" value="Order Complete" name="email_subject" />
                </div>
            </fieldset>
            <div class="form-btns">
                <input class="btn" type="submit" name="submit" value="<?= lang('submit') ?>">
            </div>
        <?= form_close() ?>
    </div>

    <div class="form-standard add-mrg-bottom">
        <div class="form-btns form-btns-top">
            <h1><?= lang('ct.om.tracking_info') ?></h1>
        </div>
        <?= form_open(ee('CP/URL')->make('addons/settings/cartthrob_order_manager/add_tracking_to_order')) ?>
            <input type="hidden" value="<?= $view['entry_id'] ?>" name="id" />
            <fieldset>
                <div class="field-instruct">
                    <label><?= lang('ct.om.tracking_number') ?></label>
                </div>
                <div class="field-control>">
                    <?php if ($view['order_tracking_number'] === FALSE): ?>
                        <?= lang('cartthrob_order_manager_requires_tracking_field') ?>
                    <?php else: ?>
                        <input id="tracking_number" type="text" value="<?= $view['order_tracking_number']?>" name="order_tracking_number" />
                    <?php endif; ?>
                </div>
            </fieldset>
            <fieldset>
                <div class="field-instruct">
                    <label>Shipping Note</label>
                </div>
                <div class="field-control>">
                    <?php if ($view['order_shipping_note'] === FALSE): ?>
                        <?= lang('cartthrob_order_manager_requires_note_field') ?>
                    <?php else: ?>
                        <textarea id="tracking_note" name="order_shipping_note">
                            <?= $view['order_shipping_note'] ?>
                        </textarea>
                    <?php endif; ?>
                </div>
            </fieldset>
            <fieldset>
                <div class="field-instruct">
                    <label><?= lang('ct.om.status') ?></label>
                </div>
                <div class="field-control>">
                    <?= form_dropdown('status', $statuses, $view['status']); ?>
                </div>
            </fieldset>
            <div class="form-btns">
                <input class="btn" type="submit" name="submit" value="<?= lang('submit') ?>">
            </div>
        <?= form_close() ?>
    </div>

    <?= $refund_form; ?>

    <?php if ($custom_templates): ?>
        <div class="add-mrg-bottom form-standard">
            <div class="form-btns form-btns-top">
                <h1><?= lang('ct.om.custom_templates') ?></h1>
            </div>
            <?php foreach ($custom_templates as $key => $template_data): ?>
                <?= $template_data['form']?>
                    <input type="hidden" name="custom_template_id" value="<?= $key ?>">
                    <input type="hidden" name="return" value="order">
                    <h2><?= $template_data['name'];?></h2>
                    <fieldset>
                        <div class="field-instruct">
                            <label><?= lang('ct.om.email_address') ?></label>
                        </div>
                        <div class="field-control>">
                            <input type="text" value="<?= $view['orders_customer_email']?>" name="email_address" />
                        </div>
                    </fieldset>
                    <fieldset>
                        <div class="field-instruct">
                            <label><?= lang('ct.om.subject') ?></label>
                        </div>
                        <div class="field-control>">
                            <input type="text" value="<?= $template_data['name']?>" name="email_subject" />
                        </div>
                    </fieldset>
                    <div class="form-btns">
                        <input class="btn" type="submit" name="submit" value="<?= lang('submit') ?>">
                    </div>
                    <input type="hidden" value="<?= $view['entry_id'] ?>" name="id" />
                <?= form_close() ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
