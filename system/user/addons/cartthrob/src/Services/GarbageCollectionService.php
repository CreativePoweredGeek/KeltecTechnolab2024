<?php

namespace CartThrob\Services;

class GarbageCollectionService
{
    /**
     * @return void
     */
    public function run(): void
    {
        ee()->db->where('expires <', @time())->delete('cartthrob_sessions');

        ee()->db->query(
            'DELETE `' . ee()->db->dbprefix('cartthrob_cart') . '`
             FROM `' . ee()->db->dbprefix('cartthrob_cart') . '`
             LEFT OUTER JOIN `' . ee()->db->dbprefix('cartthrob_sessions') . '`
             ON `' . ee()->db->dbprefix('cartthrob_cart') . '`.`id` = `' . ee()->db->dbprefix('cartthrob_sessions') . '`.`cart_id`
             WHERE `' . ee()->db->dbprefix('cartthrob_sessions') . '`.`cart_id` IS NULL'
        );

        ee()->db->query('TRUNCATE ' . ee()->db->dbprefix('cartthrob_idempotency'));
    }
}
