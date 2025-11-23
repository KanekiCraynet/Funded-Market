<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Command;

class ListApiKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api-keys:list {--env= : Filter by environment}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all API keys and their status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔑 API Keys Status');
        $this->info('=================');
        $this->newLine();

        $environment = $this->option('env') ?? config('app.env');
        $this->info("Environment: {$environment}");
        $this->newLine();

        $apiKeys = ApiKey::where('environment', $environment)
            ->orderBy('service')
            ->get();

        if ($apiKeys->isEmpty()) {
            $this->warn('No API keys found.');
            $this->newLine();
            $this->line('Run: php artisan api-keys:seed to migrate keys from .env');
            return Command::FAILURE;
        }

        $headers = ['Service', 'Status', 'Usage Count', 'Last Used', 'Has Secret', 'Expires'];
        $rows = [];

        foreach ($apiKeys as $apiKey) {
            $status = $apiKey->is_active 
                ? ($apiKey->isExpired() ? '❌ Expired' : '✅ Active')
                : '⏸️  Inactive';

            $lastUsed = $apiKey->last_used_at 
                ? $apiKey->last_used_at->diffForHumans()
                : 'Never';

            $hasSecret = $apiKey->secret_value ? 'Yes' : 'No';

            $expires = $apiKey->expires_at 
                ? $apiKey->expires_at->format('Y-m-d')
                : 'Never';

            $rows[] = [
                ucfirst($apiKey->service),
                $status,
                $apiKey->usage_count,
                $lastUsed,
                $hasSecret,
                $expires,
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('Total: ' . $apiKeys->count() . ' API keys');
        $this->newLine();
        $this->line('Commands:');
        $this->line('  php artisan api-keys:seed     - Migrate keys from .env');
        $this->line('  php artisan api-keys:rotate   - Rotate specific key');
        $this->line('  php artisan api-keys:test     - Test key validity');

        return Command::SUCCESS;
    }
}
