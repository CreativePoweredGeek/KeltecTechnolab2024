<?php

namespace CartThrob\Services;

use CartThrob\Model\Idempotency;

class IdempotencyService
{
    /**
     * @return string
     */
    public function generateKey(): string
    {
        ee()->load->helper('license_number');

        return generate_license_number();
    }

    /**
     * @param string $key
     * @return bool
     */
    public function isValid(string $key): bool
    {
        $idempotency = $this->getKey($key);
        if (!$idempotency instanceof Idempotency) {
            $this->saveKey($key);

            return true;
        }

        return false;
    }

    /**
     * @param string $key
     * @return Idempotency
     */
    public function saveKey(string $key): Idempotency
    {
        $idempotency = ee('Model')
            ->make('cartthrob:Idempotency');

        $data = [
            'member_id' => ee()->session->userdata('member_id'),
            'guid' => $key,
            'payload' => ee('cartthrob:EncryptionService')->encode(json_encode($_POST)),
            'return_path' => ee()->input->post('return'),
            'create_date' => ee()->localize->now,
        ];

        return $idempotency->set($data)->save();
    }

    /**
     * @param string $key
     * @return Idempotency|null
     */
    public function getKey(string $key): ?Idempotency
    {
        $idempotency = ee('Model')
            ->get('cartthrob:Idempotency')
            ->filter('guid', $key)
            ->first();

        if ($idempotency instanceof Idempotency) {
            return $idempotency;
        }

        return null;
    }

    /**
     * Simple poll to check for a valid status
     * @param string $key
     * @return bool
     */
    public function waitForRequest(string $key): bool
    {
        for ($i = 0; $i < 50; $i++) {
            sleep(1);
            $idempotency = $this->getKey($key);
            if (!is_null($idempotency->status)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function saveStatus(string $key): bool
    {
        $idempotency = $this->getKey($key);
        if ($idempotency instanceof Idempotency) {
            $idempotency->status = 200; // value doesn't matter :shrug:
            $idempotency->save();

            return true;
        }

        return false;
    }
}
