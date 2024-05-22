<?php

use CartThrob\Transactions\TransactionState;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Async_job_model extends CI_Model
{
    /**
     * @var string
     */
    public const TABLE = 'cartthrob_async_jobs';

    /**
     * @param TransactionState $state
     * @param array $payload
     */
    public function create(TransactionState $state, array $payload, array $post = [])
    {
        $this->load->add_package_path(PATH_THIRD . 'cartthrob/');

        $this->db->insert(self::TABLE, [
            'order_id' => $payload['order']['order_id'],
            'state' => serialize($state),
            'payload' => ee('Encrypt')->encode(serialize($payload)),
            'post' => ee('Encrypt')->encode(serialize($post)),
        ]);
    }

    /**
     * @param int $limit
     * @return \stdClass|null
     */
    public function fetch(int $limit = 1)
    {
        $query = $this->db->select('*')
            ->where('failure_count <= 3')
            ->limit($limit)
            ->get(self::TABLE);

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $row['payload'] = unserialize(ee('Encrypt')->decode($row['payload']));
                $row['state'] = unserialize($row['state']);
                $row['post'] = unserialize(ee('Encrypt')->decode($row['post']));

                yield $row;
            }
        }

        return null;
    }

    /**
     * @param array $job
     * @param string $failureMessage
     */
    public function update(array $job, string $failureMessage)
    {
        $upd = $this->db->update(
            self::TABLE,
            [
                'failure_message' => $failureMessage,
                'failure_timestamp' => time(),
                'failure_count' => $job['failure_count'] + 1,
            ],
            [
                'id' => $job['id'],
            ]
        );

        var_dump($upd);
    }

    /**
     * @param int $id
     */
    public function delete(int $id)
    {
        $this->db->delete(
            self::TABLE,
            compact('id')
        );
    }
}
