<?php

namespace CartThrob\Controllers\Cp\Routes\Actions;

use CartThrob\Controllers\Cp\AbstractActionRoute;
use CartThrob\Controllers\Cp\AbstractRoute;

class EmailTest extends AbstractActionRoute
{
    /**
     * @var string
     */
    protected $route_path = 'actions/email-test';

    /**
     * @param $id
     * @return AbstractRoute
     */
    public function process($id = false): AbstractRoute
    {
        if (!AJAX_REQUEST || REQ !== 'CP') {
            exit;
        }

        ee()->load->library('cartthrob_emails');

        $event = ee()->input->post('email_event');
        if (!$event) {
            $emails = ee()->cartthrob_emails->get_email_for_event($event = 'status_change', 'open', 'closed');
        } else {
            $emails = ee()->cartthrob_emails->get_email_for_event($event);
        }

        if (!empty($emails)) {
            $test_panel = [
                'inventory' => 5,
                'billing_address' => 'Test Avenue',
                'billing_address2' => 'Apt 1',
                'billing_city' => 'Testville',
                'billing_company' => 'Testco',
                'billing_country' => 'United States',
                'billing_country_code' => 'USA',
                'billing_first_name' => 'Testy',
                'billing_last_name' => 'Testerson',
                'billing_state' => 'MO',
                'billing_zip' => '63303',
                'customer_email' => 'test@yoursite.com',
                'customer_name' => 'Test Testerson',
                'customer_phone' => '555-555-5555',
                'discount' => '0.00',
                'entry_id' => '111',
                'group_id' => '1',
                'member_id' => '1',
                'order_id' => '111',
                'shipping' => '10',
                'shipping_plus_tax' => '10.80',
                'subtotal' => '110.00',
                'subtotal_plus_tax' => '123.45',
                'tax' => '13.45',
                'title' => '111',
                'total' => '123.45',
                'total_cart' => '123.45',
                'transaction_id' => '12345678',
            ];

            foreach ($emails as $emailDetails) {
                ee()->cartthrob_emails->sendEmail($emailDetails, $test_panel);
            }
        }

        // forces json output
        ee()->output->send_ajax_response(['CSRF_TOKEN' => ee()->functions->add_form_security_hash('{csrf_token}')]);

        exit;

        return $this;
    }
}
