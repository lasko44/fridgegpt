<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Kills all FridgeGPT development services and stops Sail containers.
 */
class DevStopCommand extends Command
{
    protected $signature = 'dev:stop {--keep-sail : Keep Sail containers running}';

    protected $description = 'Stop all development services';

    public function handle(): int
    {
        // Kill ports for Expo and Vite
        $ports = [8081, 5173];
        $labels = [8081 => 'Expo', 5173 => 'Vite'];

        foreach ($ports as $port) {
            $pids = trim(shell_exec("lsof -ti tcp:{$port} 2>/dev/null") ?? '');

            if ($pids !== '') {
                $this->comment("Stopping {$labels[$port]} on port {$port}...");
                shell_exec("lsof -ti tcp:{$port} | xargs kill -9 2>/dev/null");
            }
        }

        $processes = [
            'cloudflared tunnel' => 'Tunnel',
            'ngrok' => 'Ngrok',
        ];

        foreach ($processes as $pattern => $label) {
            $pids = trim(shell_exec("pgrep -f '{$pattern}' 2>/dev/null") ?? '');

            if ($pids !== '') {
                $this->comment("Stopping {$label}...");
                shell_exec("pkill -9 -f '{$pattern}' 2>/dev/null");
            }
        }

        // Stop Sail containers unless --keep-sail
        if (! $this->option('keep-sail')) {
            $this->comment('Stopping Sail containers...');
            shell_exec('cd ' . base_path() . ' && ./vendor/bin/sail down 2>/dev/null');
        }

        @unlink(base_path('.dev.pid'));

        $this->info('All services stopped.');

        return self::SUCCESS;
    }
}
