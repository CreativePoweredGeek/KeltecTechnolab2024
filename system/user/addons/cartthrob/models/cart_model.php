<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cart_model extends CI_Model
{
    /**
     * @var int The percent chance that garbage collection will occur
     */
    protected $garbage_collection_probability = 5;

    /**
     * @param array $cart
     * @return mixed|null
     */
    public function create(array $cart = [])
    {
        return $this->update(null, $cart);
    }

    /**
     * @param $id
     * @param array $cart
     * @param $url
     * @return mixed|null
     */
    public function update($id = null, array $cart = [], $url = null)
    {
        $data = [
            'cart' => ee('Encrypt')->encode(serialize($cart)),
            'timestamp' => time(),
        ];

        if ($url) {
            $data['url'] = $url;
        }

        if (is_null($id)) {
            $this->db->insert('cartthrob_cart', $data);

            $id = $this->db->insert_id();
        } else {
            $count = $this->db
                ->from('cartthrob_cart')
                ->where('id', $id)
                ->count_all_results();

            if ($count > 0) {
                $this->db->update('cartthrob_cart', $data, ['id' => $id]);
            } else {
                $this->db->insert('cartthrob_cart', $data);

                $id = $this->db->insert_id();
            }
        }

        return $id;
    }

    /**
     * @param int|null $id
     * @return array|null
     */
    public function fetch(?int $id): ?array
    {
        if (!$this->config->item('cartthrob:garbage_collection_cron') && rand(1, 100) <= $this->garbage_collection_probability) {
            $this->garbage_collection();
        }

        if (is_null($id)) {
            return null;
        }

        $query = $this->db->select('cart')
            ->from('cartthrob_cart')
            ->where('id', $id)
            ->limit(1)
            ->get();

        if ($query->row('cart')) {
            ee()->load->helper('data_formatting');

            $cart = _unserialize(ee('Encrypt')->decode($query->row('cart')));
            $cart['id'] = $id;

            return $cart;
        }

        return null;
    }

    /**
     * Deletes carts no longer associated with a session
     */
    protected function garbage_collection()
    {
        $this->db->query('
            DELETE `' . $this->db->dbprefix('cartthrob_cart') . '`
            FROM `' . $this->db->dbprefix('cartthrob_cart') . '`
            LEFT OUTER JOIN `' . $this->db->dbprefix('cartthrob_sessions') . '`
            ON `' . $this->db->dbprefix('cartthrob_cart') . '`.`id` = `' . $this->db->dbprefix('cartthrob_sessions') . '`.`cart_id`
            WHERE `' . $this->db->dbprefix('cartthrob_sessions') . '`.`cart_id` IS NULL
        ');
    }

    /**
     * Delete Cart
     * @param $id
     */
    public function delete($id)
    {
        $this->db->delete('cartthrob_cart', compact('id'));
    }
}
