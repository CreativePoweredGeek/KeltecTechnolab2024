<?php

namespace CartThrob\Services;

class MsmService
{
    protected ?bool $is_msm = null;

    /**
     * @return bool
     */
    public function isMsmSite(): bool
    {
        if (is_null($this->is_msm)) {
            $this->is_msm = $this->hasCtSettings();
        }

        return $this->is_msm;
    }

    /**
     * @return bool
     */
    protected function hasCtSettings(): bool
    {
        $where = [
            'site_id' => ee()->config->item('site_id'),
        ];
        $result = ee()->db->select()->where($where)->from('cartthrob_settings')->limit(1)->get();
        if ($result) {
            return $result->num_rows === 1;
        }

        return false;
    }
}
