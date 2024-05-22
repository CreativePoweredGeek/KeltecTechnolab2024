<?php

namespace CartThrob\Seeds;

use CartThrob\Seeder\Channels\Entries\Entry as ChannelEntry;
use CartThrob\Seeder\Core\AbstractSeed;
use CartThrob\Seeder\Members\Member;

class Order extends AbstractSeed
{
    /**
     * @var string
     */
    protected string $type = 'cartthrob/order';

    /**
     * We require 100 fake members and 20 products already setup
     * @var \int[][]
     */
    protected array $dependencies = [
        'member' => [
            'min' => 100,
        ],
        'cartthrob/product' => [
            'min' => 20,
        ],
    ];

    public function __construct()
    {
        ee()->load->add_package_path(PATH_THIRD . 'cartthrob/');
        ee()->load->library('cartthrob_loader');
    }

    /**
     * @return AbstractSeed
     */
    public function generate(): AbstractSeed
    {
        $member_id = $this->getFakeMemberId();
        $items = $this->buildProductItems();
        $total = $this->productTotal($items);
        $cd_service = ee('cartthrob_seeder:ChannelEntryService');
        $member_service = ee('cartthrob_seeder:MembersService');
        $member = $member_service->getMember($member_id);

        // CREATE AN ORDER
        $order_data = [
            'shipping' => 0,
            'tax' => 0,
            'subtotal' => ee()->cartthrob->round($total),
            'total' => ee()->cartthrob->round($total),
            'cart_total' => ee()->cartthrob->round($total),
            'auth' => [],
            'purchased_items' => [],
            'create_user' => false,
            'member_id' => $member_id,
            'payment_gateway' => 'Fakie Payments',
            'items' => $items,
            'entry_date' => $this->faker()->dateTimeThisYear->format('U'),
        ];

        $email = $member instanceof Member ? $member->get('email') : ee()->functions->random('encrypt', 16) . $this->faker()->email();
        $first_name = $this->faker()->firstName;
        $last_name = $this->faker()->lastName;
        $address1 = $this->faker()->address;
        $city = $this->faker()->city();
        $country_code = $this->faker()->countryCode;
        $country = $this->faker()->country;
        $state = $this->faker()->state();

        $order = $cd_service->getBlankEntry(ee()->cartthrob->store->config('orders_channel'));
        $order->setFieldValue(ee()->cartthrob->store->config('orders_billing_first_name'), $first_name);
        $order->setFieldValue(ee()->cartthrob->store->config('orders_billing_last_name'), $last_name);
        $order->setFieldValue(ee()->cartthrob->store->config('orders_billing_last_name'), $first_name . ' ' . $last_name);
        $order->setFieldValue(ee()->cartthrob->store->config('orders_customer_ip_address'), $this->faker()->ipv4);
        $order->setFieldValue(ee()->cartthrob->store->config('orders_customer_phone'), $this->faker()->phoneNumber);
        $order->setFieldValue(ee()->cartthrob->store->config('orders_billing_address'), $address1);
        $order->setFieldValue(ee()->cartthrob->store->config('orders_billing_city'), $city);
        $order->setFieldValue(ee()->cartthrob->store->config('orders_billing_state'), $state);
        $order->setFieldValue(ee()->cartthrob->store->config('orders_country_code'), $country_code);
        $order->setFieldValue(ee()->cartthrob->store->config('orders_billing_country'), $country);
        $order->setFieldValue(ee()->cartthrob->store->config('orders_customer_email'), $email);

        $order->setFieldValue(ee()->cartthrob->store->config('orders_shipping_first_name'), $first_name);
        $order->setFieldValue(ee()->cartthrob->store->config('orders_shipping_last_name'), $last_name);
        $order->setFieldValue(ee()->cartthrob->store->config('orders_shipping_last_name'), $first_name . ' ' . $last_name);
        $order->setFieldValue(ee()->cartthrob->store->config('orders_shipping_address'), $address1);
        $order->setFieldValue(ee()->cartthrob->store->config('orders_shipping_city'), $city);
        $order->setFieldValue(ee()->cartthrob->store->config('orders_shipping_state'), $state);
        $order->setFieldValue(ee()->cartthrob->store->config('orders_shipping_code'), $country_code);
        $order->setFieldValue(ee()->cartthrob->store->config('orders_shipping_country'), $country);

        $order->setFieldValue(ee()->cartthrob->store->config('orders_payment_gateway'), 'ct_offline_title');
        $order->setFieldValue(ee()->cartthrob->store->config('orders_last_four_digits'), mt_rand(1111, 9999));
        $order->setFieldValue(ee()->cartthrob->store->config('orders_card_type'), $this->faker()->creditCardType());
        $order->setFieldValue(ee()->cartthrob->store->config('orders_transaction_id'), $this->faker()->uuid());

        $order_data = array_merge($order_data, $order->toArray());
        $this->pk = @ee('cartthrob:OrdersService')->createOrder($member_id, $order_data);

        return $this;
    }

    /**
     * @param $id
     * @return bool
     */
    public function delete($id)
    {
        if ($id) {
            ee()->load->model('order_model');
            $order = ee()->order_model->getOrder($id);
            if ($order) {
                // so, sometimes an Order won't exist due to the Member also being Fake but previously removed
                // EE will remove all entries owned by a member upon removal so :shrug:
                $entry = ee('Model')
                    ->get('ChannelEntry')
                    ->with('Channel', $id)
                    ->first();

                if ($entry) {
                    $entry->delete();
                }
            }

            return true;
        }
    }

    /**
     * Returns an array of Fake Product IDs
     * @return array
     */
    protected function getFakeProductIds()
    {
        $return = [];
        $total = rand(1, 3);
        for ($i = 0; $i < $total; $i++) {
            $return[] = rand($this->minTypeItemId('cartthrob/product'), $this->maxTypeItemId('cartthrob/product'));
        }

        return $return;
    }

    /**
     * Creates an array of random fake products
     * @return array
     */
    protected function buildProductItems(): array
    {
        $products = $this->getFakeProductIds();
        $cd_service = ee('cartthrob_seeder:ChannelEntryService');
        $items = [];
        foreach ($products as $key => $product_id) {
            $product = $cd_service->getEntry($product_id);
            if ($product instanceof ChannelEntry) {
                $items[] = [
                    'price' => $this->faker()->randomNumber(3),
                    'shipping' => 0,
                    'quantity' => 1,
                    'title' => $product->get('title'),
                    'entry_id' => $product->getEntryId(),
                ];
            }
        }

        // shouldn't happen, but just in case :(
        if (!$items) {
            $items[] = [
                'price' => $this->faker()->randomNumber(3),
                'shipping' => 0,
                'quantity' => 1,
                'title' => 'Fakie Order',
                'entry_id' => 0,
            ];
        }

        return $items;
    }

    /**
     * Adds up the `price` key into a grand total
     * @param array $items
     * @return false|int|mixed
     */
    protected function productTotal(array $items)
    {
        $total = 0;
        foreach ($items as $item) {
            $total += element('price', $item, 0);
        }

        return $total;
    }
}
