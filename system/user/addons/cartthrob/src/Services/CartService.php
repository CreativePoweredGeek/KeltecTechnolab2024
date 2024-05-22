<?php

namespace CartThrob\Services;

use CartThrob\Model\Cart;
use CartThrob\Model\Session;

class CartService
{
    /** @var array */
    private $config;

    /**
     * CartService constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param $member_id
     * @return array
     */
    public function getMemberCartIds($member_id): array
    {
        $carts = [];

        $query = ee()->db->select('cart_id')
            ->from('cartthrob_sessions')
            ->where(['member_id' => $member_id])
            ->get();

        if ($query->result() && $query->num_rows() > 0) {
            foreach ($query->result_array() as $session) {
                $carts[] = $session['cart_id'];
            }
        }

        return $carts;
    }

    /**
     * @param Cart $cart
     * @return bool|void
     */
    public function removeCart(Cart $cart)
    {
        $session = ee('Model')
            ->get('cartthrob:Session')
            ->filter('cart_id', $cart->getProperty('id'))
            ->first();

        if ($session instanceof Session) {
            $session->delete();
        }

        if ($cart->delete()) {
            return true;
        }
    }

    /**
     * @param $member_id
     * @param array $cart_data
     * @return array|void
     */
    public function mergeMemberCarts($member_id, array $cart_data)
    {
        if (empty($cart_data['id'])) {
            return $cart_data;
        }

        $cart_id = $cart_data['id'];
        $member_cart_ids = $this->getMemberCartIds($member_id);

        if ($member_cart_ids > 1) {
            $carts = ee('Model')
                ->get('cartthrob:Cart')
                ->order('id', 'desc')
                ->filter('id', 'IN', $member_cart_ids);
            if ($carts->count() > 1) {
                $new_cart_data = $cart_data;
                $total = ee()->cartthrob->cart->count_all();
                foreach ($carts->all() as $cart) {
                    // we don't check the "master" cart for items to merge up
                    if ($cart->id != $cart_id) {
                        // we have existing items AND we have old items
                        if (isset($new_cart_data['items']) && isset($cart->cart['items'])) {
                            foreach ($cart->cart['items'] as $item) {
                                $item['row_id'] = $total;
                                $item['quantity'] = $item['quantity'] ?? 1;
                                ee()->cartthrob->cart->add_item($item);
                                $total++;
                            }
                            $new_cart_data['items'] = array_merge($new_cart_data['items'], $cart->cart['items']);

                        // we don't have existing items but we do have old items so we just use the old one
                        } elseif (!isset($new_cart_data['items']) && isset($cart->cart['items'])) {
                            foreach ($cart->cart['items'] as $item) {
                                $item['row_id'] = $total;
                                $item['quantity'] = $item['quantity'] ?? 1;
                                ee()->cartthrob->cart->add_item($item);
                                $total++;
                            }
                            $new_cart_data['items'] = $cart->cart['items'];
                        }

                        $this->removeCart($cart);
                    }
                }
                ee()->cartthrob->cart->save();
            }
        }
    }
}
