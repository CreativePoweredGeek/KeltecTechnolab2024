<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>
<?php echo $form_edit; ?>

<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
    <thead class="">
    <tr>
        <th>
            <strong><?= lang('order_id')?></strong>
        </th>
        <th>
            <strong><?= lang('date')?></strong>
        </th>
        <th>
            <strong><?= lang('first_name')?></strong>
        </th>
        <th>
            <strong><?= lang('last_name')?></strong>
        </th>
        <th>
            <strong><?= lang('total')?></strong>
        </th>
        <th>
            <strong><?= lang('subtotal')?></strong>
        </th>
        <th>
            <strong><?= lang('tax')?></strong>
        </th>
        <th>
            <strong><?= lang('shipping') ?></strong>
        </th>
        <th>
            <strong><?= lang('shipping_option') ?></strong>
        </th>
        <th>
            <strong><?= lang('gateway') ?></strong>
        </th>
        <th>
            <strong><?= lang('status') ?></strong>
        </th>

        <th colspan="3">
            <strong><?=lang('actions')?></strong>
        </th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($view['orders'] as $key => $value):
        ?>

        <tr>
            <td>
                <a href="<?php echo $edit_href->setQueryStringVariable('id', $value['entry_id']);  ?>"><?=$value['title']?> (<?=$value['entry_id']?>)</a>
                <input type="hidden" value="<?=$value['entry_id']?>" name="id[]" />

            </td>
            <td>
                <?=date("Y-m-d h:i a", $value['entry_date'])?>
            </td>
            <td>
                <?php
                if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_billing_first_name')]))
                {
                    echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_billing_first_name')];
                }
                ?>
            </td>
            <td>
                <?php
                if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_billing_last_name')]))
                {
                    echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_billing_last_name')];
                }
                ?>
            </td>
            <td>
                <?php
                if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_total_field')]))
                {
                    echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_total_field')];
                }
                ?>
            </td>
            <td>
                <?php
                if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_subtotal_field')]))
                {
                    echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_subtotal_field')];
                }
                ?>
            </td>
            <td>
                <?php
                if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_tax_field')]))
                {
                    echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_tax_field')];
                }
                ?>
            </td>
            <td>
                <?php
                if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_field')]))
                {
                    echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_field')];
                }
                ?>
            </td>
            <td>
                <?php
                if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_option')]))
                {
                    echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_option')];
                }
                ?>
            </td>
            </td>
            <td>
                <?php
                if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_payment_gateway')])) {
                    echo  "<small>".ucwords(str_replace("_", " ", $value['field_id_'.  ee()->cartthrob->store->config('orders_payment_gateway')]).'</small>');
                }
                ?>
            </td>
            <td>
                <?php
                echo form_dropdown('status['.$value['entry_id'].']', $view['statuses'], $value['order_status_mapped']);
                ?>
            </td>
            <td colspan="3">
                <a href="<?php echo $edit_href->setQueryStringVariable('id', $value['entry_id']);  ?>"><?=lang('cartthrob_order_manager_manage_order')?>&nbsp;&raquo;</a><br>
                <a href="<?php echo $delete_href->setQueryStringVariable('id', $value['entry_id']);  ?>"><?=lang('delete')?>&nbsp;&raquo;</a><br>
                <a href="<?php echo str_replace('id',$value['entry_id'],$href_entry);  ?>"><?=lang('cartthrob_order_manager_view_entry')?>&nbsp;&raquo;</a><br>

                <a href="javascript:void(0)" class="show_quick_view"><?=lang('ct.om.quick_view')?>&nbsp;&raquo;</a>
                <a href="javascript:void(0)" class="hide_quick_view" style="display:none"><?=lang('cartthrob_order_manager_cancel_quick_view')?>&nbsp;&raquo;</a>
            </td>
        </tr>
        <tr style="display:none" class="quick_view">
            <td>
            </td>
            <td colspan="2" valign="top">
                <h2><?= lang('ct.om.order_totals') ?></h2>
                <p>
                    <strong><?= lang('ct.om.total') ?></strong>
                    <?php
                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_total_field')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_total_field')];
                    }
                    ?>
                    <br>
                    <strong><?=lang('ct.om.subtotal')?></strong>
                    <?php
                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_subtotal_field')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_subtotal_field')];
                    }
                    ?>
                    <br>
                    <strong><?=lang('ct.om.shipping')?></strong>
                    <?php
                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_field')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_field')];
                    }
                    ?>
                    <br>
                    <strong><?=lang('ct.om.tax')?></strong>
                    <?php
                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_tax_field')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_tax_field')];
                    }
                    ?>
                    <br>
                    <strong><?=lang('ct.om.discount')?></strong>
                    <?php
                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_discount_field')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_discount_field')];
                    }
                    ?>
                </p>
            </td>
            <td colspan="3" valign="top">
                <h2><?=lang('ct.om.billing_address')?></h2>
                <p>
                    <?php

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_billing_first_name')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_billing_first_name')]." ";
                    }

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_billing_last_name')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_billing_last_name')];
                    }

                    if (!empty($view['author_id'])) {
                        echo "<a href='".$href_member.$view['author_id']."'>(".$view['author_id'].") ". lang('ct.om.member_details')." &raquo;</a> ". "<br>";
                    }

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_billing_address')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_billing_address')]."<br>";
                    }

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_billing_address2')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_billing_address2')]."<br>";
                    }

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_billing_city')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_billing_city')]."<br>";
                    }

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_billing_state')])) {
                        if (!empty( $value['field_id_'.  ee()->cartthrob->store->config('orders_billing_state')])) {
                            echo ", ". $value['field_id_'.  ee()->cartthrob->store->config('orders_billing_state')]."<br>";
                        }
                    }

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_billing_zip')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_billing_zip')]."<br>";
                    }

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_country_code')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_country_code')]."<br>";
                    }
                    ?>
                </p>
            </td>
            <td colspan="3" valign="top">
                <h2><?= lang('ct.om.shipping_address') ?></h2>
                <p>
                    <?php

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_first_name')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_first_name')]." ";
                    }

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_last_name')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_last_name')];
                    }

                    if (!empty($view['author_id'])) {
                        echo "<a href='".$href_member.$view['author_id']."'>(".$view['author_id'].") ". lang('ct.om.member_details')." &raquo;</a> ";
                    }
                    echo "<br>";

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_address')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_address')]."<br>";
                    }

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_address2')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_address2')]."<br>";
                    }

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_city')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_city')]."<br>";
                    }

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_state')])) {
                        if (!empty( $value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_state')])) {
                            echo ", ". $value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_state')]."<br>";
                        }
                    }

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_zip')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_zip')]."<br>";
                    }

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_country_code')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_country_code')]."<br>";
                    }

                    ?>
                </p>
            </td>
            <td colspan="4" valign="top">
                <h2><?=lang('ct.om.details')?></h2>
                <p>
                    <?php

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_customer_phone')])) {
                        echo  $value['field_id_'.  ee()->cartthrob->store->config('orders_customer_phone')]."<br>";
                    }

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_customer_email')])) {
                        $email_address = $value['field_id_'.  ee()->cartthrob->store->config('orders_customer_email')];
                        echo "<a href='mailto:".$email_address."'>".$email_address."</a><br>";
                    }

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_option')])) {
                        echo "<strong>".lang('ct.om.shipping_option'). "</strong> " . $value['field_id_'.  ee()->cartthrob->store->config('orders_shipping_option')]."<br>";
                    }

                    if (isset($value['field_id_'.  ee()->cartthrob->store->config('orders_payment_gateway')])) {
                        echo "<strong>".lang('ct.om.payment_method'). "</strong> " .ucwords(str_replace("_", " ",  $value['field_id_'.  ee()->cartthrob->store->config('orders_payment_gateway')]))."<br>";
                    }

                    ?>
                </p>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p>
    <input type="submit" name="submit" value="<?=lang('ct.om.update_orders')?>" class="btn submit" />
</p>
<div class="tableSubmit">
    <input type="submit" name="submit" value="<?=lang('ct.om.update_checked_items')?>" class="btn submit" />
    <select name='toggle_status' class='statuses_blank' >
    </select>
</div>

</form>

<?php echo $pagination; ?>
</div>