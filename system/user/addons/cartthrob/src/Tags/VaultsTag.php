<?php

namespace CartThrob\Tags;

class VaultsTag extends Tag
{
    public function process()
    {
        $vaults = ee('Model')
            ->get('cartthrob:Vault');

        if ($this->hasParam('id')) {
            $vault_ids = $this->explodeParam('id');
            $vaults->filter('id', 'IN', $vault_ids);
        }

        if ($this->hasParam('order_id')) {
            $order_ids = $this->explodeParam('order_id');
            $vaults->filter('order_id', 'IN', $order_ids);
        }

        if ($this->hasParam('primary')) {
            $primary = 0;
            if (bool_string($this->param('primary'))) {
                $primary = 1;
            }

            $vaults->filter('primary', $primary);
        }

        if ($this->hasParam('member_id')) {
            if (in_array($this->param('member_id'),
                ['CURRENT_USER', '{member_id}', '{logged_in_member_id}'])) {
                $vaults->filter('member_id', $this->getMemberId());
            } else {
                $vaults->filter('member_id', 'IN', $this->explodeParam('member_id'));
            }
        }

        // default to current member's vaults if no other params are specified
        if (!$this->hasParam('member_id') &&
            !$this->hasParam('id') &&
            !$this->hasParam('primary') &&
            !$this->hasParam('order_id')) {
            $vaults->filter('member_id', $this->getMemberId());
        }

        $limit = ($this->hasParam('limit')) ? $this->param('limit') : 100;
        $vaults->limit($limit);
        if ($vaults->count() == 0) {
            return ee()->TMPL->no_results();
        }

        $variables = [];
        foreach ($vaults->all() as $vault) {
            $variables[] = $vault->toArray();
        }

        return ee()->template_helper->parse_variables($variables);
    }
}
