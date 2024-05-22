<?php

namespace CartThrob\Seeds;

use CartThrob\Seeder\Core\AbstractSeed;

class Vault extends AbstractSeed
{
    /**
     * @var string
     */
    protected string $type = 'cartthrob/vault';

    /**
     * Vaults require at least 100 Fake Members
     * @var \int[][]
     */
    protected array $dependencies = [
        'member' => [
            'min' => 100,
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
        $vault = ee('Model')
            ->make('cartthrob:Vault');

        $member_id = $this->getFakeMemberId();
        $data = [
            'member_id' => $member_id,
            'gateway' => 'Cartthrob_stripe',
            'token' => uniqid('fakie_token_', true),
            'customer_id' => uniqid('fakie_customer_', true),
        ];

        $vault->set($data);
        $vault->save();
        $this->pk = $vault->id;

        return $this;
    }

    /**
     * @param $id
     * @return bool
     */
    public function delete($id)
    {
        $vault = ee('Model')
            ->get('cartthrob:Vault')
            ->filter('id', $id)
            ->first();

        if (!$vault instanceof \CartThrob\Model\Vault) {
            return false;
        }

        $vault->delete();

        return true;
    }
}
