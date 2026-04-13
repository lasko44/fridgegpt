<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Restarts an individual dev service inside the Sail container.
 */
class DevRestartServiceCommand extends Command
{
    protected $signature = 'dev:reload {service : queue|ssr|vite|all}';

    protected $description = 'Restart a single dev service inside the Sail container';

    public function handle(): int
    {
        $service = $this->argument('service');
        $container = 'fridgegpt-laravel.test-1';

        $services = match ($service) {
            'queue' => ['queue:work'],
            'ssr' => ['inertia:start-ssr'],
            'vite' => ['vite'],
            'all' => ['queue:work', 'inertia:start-ssr', 'vite'],
            default => null,
        };

        if ($services === null) {
            $this->error("Unknown service '{$service}'. Use queue, ssr, vite, or all.");

            return self::FAILURE;
        }

        foreach ($services as $pattern) {
            $this->comment("Killing {$pattern} inside container...");
            shell_exec("docker exec {$container} pkill -9 -f " . escapeshellarg($pattern) . ' 2>/dev/null');
        }

        $this->info('Concurrently will auto-restart the killed processes.');

        return self::SUCCESS;
    }
}
