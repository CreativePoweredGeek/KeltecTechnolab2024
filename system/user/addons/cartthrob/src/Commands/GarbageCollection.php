<?php

namespace CartThrob\Commands;

use ExpressionEngine\Cli\Cli;

class GarbageCollection extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Garbage Collection';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'cartthrob:gc';

    /**
     * Public description of command
     * @var string
     */
    public $description = 'Removes expired cart and session data from your CartThrob site';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary = 'Removes expired cart and session data from your CartThrob site';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php cartthrob:gc';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        ee('cartthrob:GarbageCollectionService')->run();
    }
}
