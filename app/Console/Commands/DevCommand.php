<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Starts all FridgeGPT development services in one command.
 *
 * Custom supervisor: spawns each backend service as a child process,
 * streams their output to the terminal with color-coded labels, and
 * listens for keystrokes so the user can restart individual services
 * without leaving the terminal.
 */
class DevCommand extends Command
{
    protected $signature = 'dev';

    protected $description = 'Start all development services in one command';

    private string $mobileDir;

    private string $container = 'fridgegpt-laravel.test-1';

    /** @var array<string,array{cmd:array<string>,color:string,process:?Process}> */
    private array $services = [];

    private ?Process $expo = null;

    private ?Process $tunnel = null;

    private string $expoLog = '';

    private int $expoLogOffset = 0;

    public function handle(): int
    {
        $this->mobileDir = base_path('../fridgegpt-mobile');

        if (! is_dir($this->mobileDir)) {
            $this->error('Mobile app directory not found at: ' . $this->mobileDir);

            return self::FAILURE;
        }

        $this->killStaleProcesses();

        if (! $this->ensureDockerRunning()) {
            return self::FAILURE;
        }

        // Start Sail
        $this->info('Starting Sail (Docker)...');
        $sailUp = new Process(['./vendor/bin/sail', 'up', '-d']);
        $sailUp->setWorkingDirectory(base_path());
        $sailUp->setTimeout(120);
        $sailUp->run();

        if (! $sailUp->isSuccessful()) {
            $this->error('Sail failed to start.');
            $this->line($sailUp->getErrorOutput());

            return self::FAILURE;
        }

        sleep(2);

        // Clean up stale processes inside the container
        shell_exec("docker exec {$this->container} pkill -9 -f 'queue:work' 2>/dev/null");
        shell_exec("docker exec {$this->container} pkill -9 -f 'inertia:start-ssr' 2>/dev/null");

        // API tunnel
        $this->info('Starting API tunnel...');
        $apiUrl = $this->startApiTunnel();

        if (! $apiUrl) {
            return self::FAILURE;
        }

        $this->updateMobileEnv($apiUrl);

        // Expo
        $this->info('Starting Expo...');
        $expoUrl = $this->startExpo();

        // Display URLs and QR code
        $this->newLine();
        $this->line('  <fg=cyan;options=bold>Web</>      http://localhost:8000');
        $this->line("  <fg=cyan;options=bold>API</>      {$apiUrl}");
        if ($expoUrl) {
            $this->line("  <fg=cyan;options=bold>Expo</>     {$expoUrl}");
        }
        $this->newLine();

        if ($expoUrl) {
            $qr = trim(shell_exec('qrencode -t ANSIUTF8 ' . escapeshellarg($expoUrl) . ' 2>/dev/null') ?? '');
            if ($qr) {
                $this->output->writeln($qr);
            }
        }

        $this->printHelp();
        $this->writePidFile();

        // Define backend services
        $this->services = [
            'queue' => [
                'cmd' => ['docker', 'exec', $this->container, 'php', 'artisan', 'queue:work', '--tries=2', '--timeout=300', '--verbose'],
                'color' => 'green',
                'process' => null,
            ],
            'logs' => [
                'cmd' => ['docker', 'exec', $this->container, 'php', 'artisan', 'pail', '--timeout=0'],
                'color' => 'yellow',
                'process' => null,
            ],
            'vite' => [
                'cmd' => ['docker', 'exec', $this->container, 'npm', 'run', 'dev'],
                'color' => 'cyan',
                'process' => null,
            ],
            'ssr' => [
                'cmd' => ['docker', 'exec', $this->container, 'php', 'artisan', 'inertia:start-ssr'],
                'color' => 'magenta',
                'process' => null,
            ],
        ];

        foreach ($this->services as $name => $_) {
            $this->startService($name);
        }

        // Set terminal to non-canonical mode so we can read individual keystrokes
        $stty = trim(shell_exec('stty -g 2>/dev/null') ?? '');
        shell_exec('stty -icanon -echo min 0 time 0 2>/dev/null');
        stream_set_blocking(STDIN, false);

        pcntl_async_signals(true);

        $cleanup = function () use ($stty) {
            // Restore terminal
            if ($stty !== '') {
                shell_exec('stty ' . escapeshellarg($stty));
            }

            $this->newLine();
            $this->info('Stopping all services...');

            foreach (array_keys($this->services) as $name) {
                $this->stopService($name);
            }
            $this->expo?->stop();
            $this->tunnel?->stop();

            shell_exec("pkill -9 -f 'cloudflared tunnel' 2>/dev/null");
            shell_exec("pkill -9 -f 'expo start' 2>/dev/null");
            shell_exec("pkill -9 -f 'ngrok' 2>/dev/null");
            shell_exec("docker exec {$this->container} pkill -9 -f 'queue:work' 2>/dev/null");
            shell_exec("docker exec {$this->container} pkill -9 -f 'inertia:start-ssr' 2>/dev/null");

            @unlink(base_path('.dev.pid'));
            $this->info('Stopped. Sail containers still running — use `sail down` to stop them.');

            exit(0);
        };

        pcntl_signal(SIGINT, $cleanup);
        pcntl_signal(SIGTERM, $cleanup);

        // Main supervisor loop: read service output, write to terminal, listen for keystrokes
        $this->supervisorLoop();

        $cleanup();

        return self::SUCCESS;
    }

    private function startService(string $name): void
    {
        $service = $this->services[$name];
        $process = new Process($service['cmd']);
        $process->setTimeout(null);
        $process->start();

        $this->services[$name]['process'] = $process;
        $this->writeServiceLine($name, "[started]");
    }

    private function stopService(string $name): void
    {
        $process = $this->services[$name]['process'] ?? null;
        if ($process && $process->isRunning()) {
            $process->stop(2);
        }

        // Kill lingering processes inside the container
        if ($name === 'ssr') {
            // SSR uses Node cluster — kill PHP artisan AND all Node workers on port 13714
            shell_exec("docker exec {$this->container} pkill -9 -f 'inertia:start-ssr' 2>/dev/null");
            shell_exec("docker exec {$this->container} sh -c \"fuser -k 13714/tcp 2>/dev/null\" 2>/dev/null");
            shell_exec("docker exec {$this->container} pkill -9 -f 'ssr.js' 2>/dev/null");
            usleep(500_000); // Let the port release
        } else {
            $pattern = match ($name) {
                'queue' => 'queue:work',
                'logs' => 'pail',
                'vite' => 'vite',
                default => null,
            };
            if ($pattern) {
                shell_exec("docker exec {$this->container} pkill -9 -f " . escapeshellarg($pattern) . ' 2>/dev/null');
            }
        }
    }

    private function restartService(string $name): void
    {
        if (! isset($this->services[$name])) {
            $this->writeServiceLine('dev', "Unknown service: {$name}");

            return;
        }

        $this->writeServiceLine($name, '[restarting...]');
        $this->stopService($name);
        usleep(500_000);
        $this->startService($name);
    }

    private function writeServiceLine(string $name, string $text): void
    {
        $color = $this->services[$name]['color'] ?? 'white';
        $padded = str_pad($name, 6);
        $this->output->writeln("<fg={$color}>[{$padded}]</> {$text}");
    }

    private function supervisorLoop(): void
    {
        while (true) {
            pcntl_signal_dispatch();

            // Drain output from all running services
            foreach ($this->services as $name => $service) {
                $process = $service['process'];
                if (! $process) {
                    continue;
                }

                $stdout = $process->getIncrementalOutput();
                $stderr = $process->getIncrementalErrorOutput();
                $combined = $stdout . $stderr;

                if ($combined !== '') {
                    foreach (preg_split('/\r?\n/', rtrim($combined, "\n")) as $line) {
                        if ($line !== '') {
                            $this->writeServiceLine($name, $line);
                        }
                    }
                }

                // Auto-restart crashed services
                if (! $process->isRunning() && $process->getExitCode() !== null) {
                    $this->writeServiceLine($name, '[crashed, restarting...]');
                    $this->startService($name);
                }
            }

            // Stream Expo log output
            $this->streamExpoLog();

            // Read keystrokes
            $key = fread(STDIN, 1);
            if ($key !== false && $key !== '') {
                $this->handleKey($key);
            }

            usleep(50_000); // 50ms
        }
    }

    private function handleKey(string $key): void
    {
        switch (strtolower($key)) {
            case 'q':
                $this->restartService('queue');
                break;
            case 's':
                $this->restartService('ssr');
                break;
            case 'v':
                $this->restartService('vite');
                break;
            case 'a':
                foreach (array_keys($this->services) as $name) {
                    $this->restartService($name);
                }
                break;
            case 'h':
            case '?':
                $this->printHelp();
                break;
            case "\x03": // Ctrl+C
                posix_kill(posix_getpid(), SIGINT);
                break;
        }
    }

    private function printHelp(): void
    {
        $this->newLine();
        $this->line('<fg=yellow>Keyboard shortcuts:</>');
        $this->line('  <fg=green>q</> restart queue worker');
        $this->line('  <fg=magenta>s</> restart SSR server');
        $this->line('  <fg=cyan>v</> restart Vite');
        $this->line('  <fg=white>a</> restart all backend services');
        $this->line('  <fg=white>h</> show this help');
        $this->line('  <fg=red>Ctrl+C</> stop everything');
        $this->newLine();
    }

    private function startApiTunnel(): ?string
    {
        $this->tunnel = new Process(['cloudflared', 'tunnel', '--url', 'http://localhost:8000']);
        $this->tunnel->setTimeout(null);
        $this->tunnel->start();

        $deadline = time() + 15;
        while (time() < $deadline) {
            if (preg_match('#(https://[a-z0-9.-]+\.trycloudflare\.com)#', $this->tunnel->getErrorOutput(), $m)) {
                return $m[1];
            }
            usleep(500_000);
        }

        $this->tunnel->stop();
        $this->error('Failed to get API tunnel URL.');

        return null;
    }

    private function startExpo(): ?string
    {
        $this->expoLog = sys_get_temp_dir() . '/fridgegpt-expo.log';
        @unlink($this->expoLog);
        touch($this->expoLog);

        // Start ngrok ourselves (bypass broken @expo/ngrok wrapper)
        $ngrokLog = sys_get_temp_dir() . '/fridgegpt-ngrok.log';
        @unlink($ngrokLog);
        $ngrok = Process::fromShellCommandline(
            sprintf('ngrok http 8081 --log=stdout > %s 2>&1', escapeshellarg($ngrokLog))
        );
        $ngrok->setTimeout(null);
        $ngrok->start();

        // Wait for ngrok to be ready and capture URL
        $publicUrl = null;
        $deadline = time() + 15;
        while (time() < $deadline) {
            $info = @file_get_contents('http://127.0.0.1:4040/api/tunnels');
            if ($info) {
                $data = json_decode($info, true);
                $publicUrl = $data['tunnels'][0]['public_url'] ?? null;
                if ($publicUrl) {
                    break;
                }
            }
            usleep(500_000);
        }

        if (! $publicUrl) {
            $ngrok->stop();
            $this->error('Failed to start ngrok tunnel for Expo.');

            return null;
        }

        // Tell Expo about the public URL via env var so it embeds it in the QR code
        $host = parse_url($publicUrl, PHP_URL_HOST);

        $env = [
            'REACT_NATIVE_PACKAGER_HOSTNAME' => $host,
            'EXPO_PACKAGER_PROXY_URL' => $publicUrl,
            'EXPO_MANIFEST_PROXY_URL' => $publicUrl,
        ];
        $envStr = '';
        foreach ($env as $k => $v) {
            $envStr .= "{$k}=" . escapeshellarg($v) . ' ';
        }

        // Start Expo in --lan mode (no tunnel) since we have our own
        $this->expo = Process::fromShellCommandline(
            sprintf('%s npx expo start --lan --clear > %s 2>&1', $envStr, escapeshellarg($this->expoLog))
        );
        $this->expo->setWorkingDirectory($this->mobileDir);
        $this->expo->setTimeout(null);
        $this->expo->start();

        // Wait for Expo to be ready
        $deadline = time() + 60;
        while (time() < $deadline) {
            $log = @file_get_contents($this->expoLog) ?: '';
            if (str_contains($log, 'Waiting on') || str_contains($log, 'Logs for your project')) {
                $this->expoLogOffset = filesize($this->expoLog);

                return "exp://{$host}:443";
            }
            usleep(500_000);
        }

        $this->expoLogOffset = file_exists($this->expoLog) ? filesize($this->expoLog) : 0;

        return "exp://{$host}:443";
    }

    private function streamExpoLog(): void
    {
        if ($this->expoLog === '' || ! file_exists($this->expoLog)) {
            return;
        }

        clearstatcache(true, $this->expoLog);
        $size = filesize($this->expoLog);

        if ($size <= $this->expoLogOffset) {
            return;
        }

        $fh = @fopen($this->expoLog, 'r');
        if (! $fh) {
            return;
        }

        fseek($fh, $this->expoLogOffset);
        $chunk = fread($fh, $size - $this->expoLogOffset);
        fclose($fh);
        $this->expoLogOffset = $size;

        foreach (preg_split('/\r?\n/', rtrim($chunk, "\n")) as $line) {
            if ($line !== '') {
                $this->output->writeln("<fg=blue>[expo  ]</> {$line}");
            }
        }
    }

    private function killStaleProcesses(): void
    {
        foreach ([8081, 5173] as $port) {
            $pids = trim(shell_exec("lsof -ti tcp:{$port} 2>/dev/null") ?? '');

            if ($pids !== '') {
                shell_exec("lsof -ti tcp:{$port} | xargs kill -9 2>/dev/null");
                usleep(300_000);
            }
        }

        foreach (['cloudflared tunnel', 'ngrok', 'expo start'] as $proc) {
            $pids = trim(shell_exec("pgrep -f '{$proc}' 2>/dev/null") ?? '');

            if ($pids !== '') {
                shell_exec("pkill -9 -f '{$proc}' 2>/dev/null");
                usleep(300_000);
            }
        }
    }

    private function ensureDockerRunning(): bool
    {
        if ($this->isDockerHealthy()) {
            return true;
        }

        $this->comment('Docker daemon is not responding. Restarting Docker Desktop...');

        shell_exec('osascript -e \'quit app "Docker Desktop"\' 2>/dev/null');
        sleep(2);
        shell_exec('open -a "Docker Desktop" 2>/dev/null');

        $deadline = time() + 60;

        while (time() < $deadline) {
            if ($this->isDockerHealthy()) {
                $this->newLine();
                $this->info('Docker is ready.');

                return true;
            }
            sleep(2);
            $this->output->write('.');
        }

        $this->newLine();
        $this->error('Docker failed to start within 60 seconds. Please open Docker Desktop manually.');

        return false;
    }

    private function isDockerHealthy(): bool
    {
        $process = new Process(['docker', 'info']);
        $process->setTimeout(5);
        $process->run();

        return $process->isSuccessful();
    }

    private function updateMobileEnv(string $url): void
    {
        $envPath = $this->mobileDir . '/.env';

        if (! file_exists($envPath)) {
            file_put_contents($envPath, "EXPO_PUBLIC_API_URL={$url}\n");

            return;
        }

        $contents = file_get_contents($envPath);
        $contents = preg_replace(
            '/^EXPO_PUBLIC_API_URL=.*/m',
            "EXPO_PUBLIC_API_URL={$url}",
            $contents
        );

        file_put_contents($envPath, $contents);
    }

    private function writePidFile(): void
    {
        file_put_contents(base_path('.dev.pid'), (string) getmypid());
    }
}
