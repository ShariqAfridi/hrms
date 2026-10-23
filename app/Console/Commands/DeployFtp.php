<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeployFtp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:ftp {--dry-run : Simulate deployment without uploading files} {--only= : Comma-separated list of relative files or directories to deploy}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy and synchronize HRMS application files to the live FTP server (Strictly preserving .env)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $config = config('deploy.ftp');

        if (empty($config['host']) || empty($config['username'])) {
            $this->error('FTP configuration missing in config/deploy.php');
            return Command::FAILURE;
        }

        $dryRun = $this->option('dry-run');
        $onlyOption = $this->option('only');

        $this->info('=================================================');
        $this->info('  AXECORP HRMS AUTOMATED LIVE FTP DEPLOYER       ');
        $this->info('=================================================');
        $this->line("Target Host: <comment>{$config['host']}</comment> ({$config['domain']})");
        $this->line("User:        <comment>{$config['username']}</comment>");
        $this->line("Mode:        " . ($dryRun ? '<fg=yellow>SIMULATION (DRY RUN)</>' : '<fg=green>LIVE SYNC</>'));
        if ($onlyOption) {
            $this->line("Scope:       <comment>{$onlyOption}</comment>");
        }
        $this->warn("Rule:        STRICTLY PRESERVING REMOTE .env FILE");
        $this->info('=================================================' . PHP_EOL);

        $connId = @ftp_connect($config['host'], $config['port'] ?? 21, $config['timeout'] ?? 60);
        if (!$connId) {
            $this->error("ERROR: Could not connect to FTP host {$config['host']}");
            return Command::FAILURE;
        }

        if (!@ftp_login($connId, $config['username'], $config['password'])) {
            $this->error("ERROR: Authentication failed for {$config['username']}");
            ftp_close($connId);
            return Command::FAILURE;
        }

        ftp_pasv($connId, $config['passive'] ?? true);
        $this->info("✓ Connected and authenticated successfully via Passive FTP!" . PHP_EOL);

        $localBase = base_path();
        $uploaded = 0;
        $skipped = 0;
        $errors = 0;

        $protectedFiles = $config['protected_files'] ?? ['.env'];
        if ($onlyOption) {
            $syncPaths = array_filter(array_map('trim', explode(',', $onlyOption)));
        } else {
            $syncPaths = $config['sync_paths'] ?? ['app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'artisan', 'composer.json'];
        }

        foreach ($syncPaths as $target) {
            $targetClean = trim(str_replace('\\', '/', $target), '/');
            $fullLocal = $localBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $targetClean);
            $fullRemote = $targetClean;

            if (is_dir($fullLocal)) {
                $this->ensureRemoteDir($connId, $fullRemote);
                $this->syncDirectory($connId, $fullLocal, $fullRemote, $protectedFiles, $uploaded, $skipped, $errors, $localBase, $dryRun);
            } elseif (is_file($fullLocal)) {
                if (!$this->isIgnored($targetClean, $protectedFiles)) {
                    $parentDir = dirname($fullRemote);
                    if ($parentDir !== '.' && $parentDir !== '/') {
                        $this->ensureRemoteDir($connId, $parentDir);
                    }
                    if ($dryRun) {
                        $uploaded++;
                        $this->line("  [DRY-RUN] Would upload: <info>{$fullRemote}</info>");
                    } else {
                        if (@ftp_put($connId, $fullRemote, $fullLocal, FTP_BINARY)) {
                            $uploaded++;
                            $this->line("  <fg=green>[OK]</> Uploaded: <info>{$fullRemote}</info>");
                        } else {
                            $errors++;
                            $this->line("  <fg=red>[ERR]</> Failed: <error>{$fullRemote}</error>");
                        }
                    }
                }
            } else {
                $this->warn("  [SKIP] Local path not found: {$targetClean}");
            }
        }

        ftp_close($connId);

        $this->info(PHP_EOL . '=================================================');
        $this->info('  DEPLOYMENT SUMMARY                             ');
        $this->info("  Total Files Processed: {$uploaded}");
        $this->info("  Total Skipped/Protected: {$skipped} (.env protected)");
        $this->info("  Total Errors:          {$errors}");
        $this->info('=================================================');

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    protected function isIgnored(string $relativePath, array $ignored): bool
    {
        $normalized = str_replace('\\', '/', $relativePath);
        $baseName = basename($normalized);
        $lowerBase = strtolower($baseName);
        $lowerNormalized = strtolower($normalized);

        // Strict: never touch .env files
        if ($lowerBase === '.env' || str_starts_with($lowerBase, '.env.')) {
            return true;
        }

        // Strict: block all AI, agent, and prompt files
        if (
            str_contains($lowerNormalized, '.gemini') ||
            str_contains($lowerNormalized, '.agents') ||
            str_contains($lowerNormalized, '_agents') ||
            str_contains($lowerNormalized, '.agent') ||
            str_contains($lowerNormalized, '.system_generated') ||
            str_contains($lowerNormalized, 'scratch/') ||
            in_array($lowerBase, ['gemini.md', 'agents.md', 'claude.md', 'copilot.md', '.cursorrules', 'scratch'])
        ) {
            return true;
        }

        // Strict: block database dumps, sqlite files, and logs
        if (
            str_ends_with($lowerBase, '.sql') ||
            str_ends_with($lowerBase, '.sqlite') ||
            str_ends_with($lowerBase, '.log') ||
            $lowerBase === 'database_dump.sql'
        ) {
            return true;
        }

        // Strict: block root vendor and node_modules
        if (
            $lowerNormalized === 'vendor' ||
            str_starts_with($lowerNormalized, 'vendor/') ||
            $lowerNormalized === 'node_modules' ||
            str_starts_with($lowerNormalized, 'node_modules/') ||
            $lowerNormalized === '.git' ||
            str_starts_with($lowerNormalized, '.git/')
        ) {
            return true;
        }

        foreach ($ignored as $pattern) {
            $cleanPattern = trim(str_replace(['*', '\\'], ['', '/'], strtolower($pattern)));
            if (empty($cleanPattern)) continue;

            if ($cleanPattern === 'vendor' || $cleanPattern === 'node_modules' || $cleanPattern === '.git') {
                continue; // Handled strictly at root above
            }

            if (
                $lowerNormalized === $cleanPattern ||
                str_starts_with($lowerNormalized, $cleanPattern . '/') ||
                $lowerBase === $cleanPattern
            ) {
                return true;
            }
        }

        return false;
    }

    protected function ensureRemoteDir($connId, string $remoteDir): void
    {
        $parts = explode('/', trim($remoteDir, '/'));
        $path = '';
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') continue;
            $path .= '/' . $part;
            if (!@ftp_chdir($connId, $path)) {
                @ftp_mkdir($connId, $path);
            }
        }
        @ftp_chdir($connId, '/');
    }

    protected function syncDirectory($connId, string $localDir, string $remoteDir, array $ignored, int &$uploaded, int &$skipped, int &$errors, string $localRoot, bool $dryRun): void
    {
        $items = scandir($localDir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $localPath = $localDir . DIRECTORY_SEPARATOR . $item;
            $remotePath = trim($remoteDir . '/' . $item, '/');
            $relativePath = ltrim(str_replace($localRoot, '', $localPath), '/\\');

            if ($this->isIgnored($relativePath, $ignored)) {
                $skipped++;
                continue;
            }

            if (is_dir($localPath)) {
                $this->ensureRemoteDir($connId, $remotePath);
                $this->syncDirectory($connId, $localPath, $remotePath, $ignored, $uploaded, $skipped, $errors, $localRoot, $dryRun);
            } else {
                $parentDir = dirname($remotePath);
                if ($parentDir !== '.' && $parentDir !== '/') {
                    $this->ensureRemoteDir($connId, $parentDir);
                }

                if ($dryRun) {
                    $uploaded++;
                    $this->line("  [DRY-RUN] Would upload: <info>{$remotePath}</info>");
                } else {
                    if (@ftp_put($connId, $remotePath, $localPath, FTP_BINARY)) {
                        $uploaded++;
                        $this->line("  <fg=green>[OK]</> Uploaded: <info>{$remotePath}</info>");
                    } else {
                        $errors++;
                        $this->line("  <fg=red>[ERR]</> Failed: <error>{$remotePath}</error>");
                    }
                }
            }
        }
    }
}
