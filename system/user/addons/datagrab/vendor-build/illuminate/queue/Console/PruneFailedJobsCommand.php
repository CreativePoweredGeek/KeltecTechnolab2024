<?php

namespace BoldMinded\DataGrab\Dependency\Illuminate\Queue\Console;

use BoldMinded\DataGrab\Dependency\Carbon\Carbon;
use BoldMinded\DataGrab\Dependency\Illuminate\Console\Command;
use BoldMinded\DataGrab\Dependency\Illuminate\Queue\Failed\PrunableFailedJobProvider;
class PruneFailedJobsCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'queue:prune-failed
                {--hours=24 : The number of hours to retain failed jobs data}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune stale entries from the failed jobs table';
    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        $failer = $this->laravel['queue.failer'];
        $count = 0;
        if ($failer instanceof PrunableFailedJobProvider) {
            $count = $failer->prune(Carbon::now()->subHours($this->option('hours')));
        } else {
            $this->error('The [' . class_basename($failer) . '] failed job storage driver does not support pruning.');
            return 1;
        }
        $this->info("{$count} entries deleted!");
    }
}
