<?php

namespace BoldMinded\DataGrab\Queue\Jobs;

use BoldMinded\DataGrab\Dependency\Illuminate\Contracts\Queue\Job;
use ExpressionEngine\Cli\CliFactory;

class AbstractJob
{
    /**
     * @var Job
     */
    protected $job;

    /**
     * @var array
     */
    protected array $settings = [];

    /**
     * @return bool
     */
    protected function isCli(): bool
    {
        return defined('STDIN') && php_sapi_name() === 'cli';
    }

    protected static $settingsCache = [];

    /**
     * @param int $importId
     * @return void
     */
    protected function handleOutput(int $importId)
    {
        if (!$this->isCli()) {
            return;
        }

        $queueStatus = ee('datagrab:QueueStatus');
        $status = $queueStatus->fetch($importId)[$importId];
        $queueSize = $status['import_queue_size'];
        $totalRecords = $status['total_records'];
        $lastRecord = $status['last_record'];

        $factory = new CliFactory();
        $output = $factory->newStdio();
        $percent = 100 - round($queueSize / $totalRecords * 100);

        // $display = '<<bold>>'. $percent .'%<<reset>>';
        $display = '<<bold>>'. $lastRecord . '/' . $totalRecords .'<<reset>>';
        echo "\033[". strlen($display) ."D"; // backup N characters
        $output->out($display);

        if ($percent >= 100) {
            $display = '<<green>>Complete!<<reset>>';
            echo "\033[". strlen($display) ."D";
            $output->outln($display);
        }
    }

    /**
     * @return int
     */
    protected function getImportId(string $prefix = 'import'): int
    {
        return (int) str_replace($prefix . '_', '', $this->job->getQueue());
    }

    /**
     * @param int $importId
     * @return bool
     */
    protected function isValidImport(int $importId): bool
    {
        if (!empty(static::$settingsCache)) {
            $this->settings = static::$settingsCache;

            return true;
        }

        // Fetch import settings
        $query = ee('db')
            ->where('id', $importId)
            ->get('datagrab');

        if ($query->num_rows() == 0) {
            ee()->datagrab->logger->log('Import aborted. Requested Import ID not found.');

            return false;
        }

        $row = $query->row_array();
        $this->settings = unserialize($row['settings']);

        static::$settingsCache = $this->settings;

        return true;
    }
}
