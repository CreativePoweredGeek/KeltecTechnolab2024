<?php

namespace CartThrob\Commands;

use ExpressionEngine\Cli\Cli;

class Version extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Version';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'cartthrob:version';

    /**
     * Public description of command
     * @var string
     */
    public $description = 'Displays the Version number of CartThrob';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary = 'Displays the Version number of CartThrob';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php cartthrob:version';

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
        $this->output->outln('CartThrob is version: ' . CARTTHROB_VERSION);
    }
}
