<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SignUserPasswordsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:sign-passwords {--force : Re-sign even if already signed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cryptographically sign all existing user password hashes with server APP_KEY to prevent database tampering';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $this->info("Initializing Cryptographic Password Binding...");

        $query = User::query();
        if (!$force) {
            $query->whereNull('password_signature');
        }

        $users = $query->get();
        $total = $users->count();

        if ($total === 0) {
            $this->info("All user accounts already have valid cryptographic signatures. Use --force to re-sign.");
            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $signedCount = 0;
        foreach ($users as $user) {
            if (!empty($user->password)) {
                $user->refreshPasswordSignature();
                $signedCount++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Successfully generated tamper-proof HMAC signatures for {$signedCount} user(s).");
        return 0;
    }
}
