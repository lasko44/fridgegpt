<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Restarts all FridgeGPT development services.
 */
class DevRestartCommand extends Command
{
    protected $signature = 'dev:restart';

    protected $description = 'Stop and restart all development services';

    public function handle(): int
    {
        $this->call('dev:stop');
        $this->newLine();

        return $this->call('dev');
    }
}
