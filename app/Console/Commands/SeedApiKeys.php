<?php

namespace App\Console\Commands;

use App\Services\ApiKeyService;
use Illuminate\Console\Command;

class SeedApiKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api-keys:seed 
                           {--force : Force seeding even if keys already exist}
                           {--env= : Target environment (defaults to current)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate API keys from .env to encrypted database storage';

    private ApiKeyService $apiKeyService;

    public function __construct(ApiKeyService $apiKeyService)
    {
        parent::__construct();
        $this->apiKeyService = $apiKeyService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔐 API Key Migration Tool');
        $this->info('========================');
        $this->newLine();

        $environment = $this->option('env') ?? config('app.env');
        $force = $this->option('force');

        $this->info("Target environment: {$environment}");
        $this->newLine();

        // Define API keys to migrate
        $apiKeys = [
            [
                'service' => 'gemini',
                'env_key' => 'GEMINI_API_KEY',
                'description' => 'Gemini AI (LLM Analysis)',
            ],
            [
                'service' => 'newsapi',
                'env_key' => 'NEWSAPI_KEY',
                'description' => 'NewsAPI (News Sentiment)',
            ],
            [
                'service' => 'cryptopanic',
                'env_key' => 'CRYPTOPANIC_KEY',
                'description' => 'CryptoPanic (Crypto News)',
            ],
            [
                'service' => 'binance',
                'env_key' => 'BINANCE_API_KEY',
                'env_secret' => 'BINANCE_API_SECRET',
                'description' => 'Binance (Market Data)',
            ],
            [
                'service' => 'alpha_vantage',
                'env_key' => 'ALPHA_VANTAGE_API_KEY',
                'description' => 'Alpha Vantage (Stock Data)',
            ],
            [
                'service' => 'twitter',
                'env_key' => 'TWITTER_BEARER_TOKEN',
                'description' => 'Twitter (Social Sentiment)',
            ],
        ];

        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($apiKeys as $keyConfig) {
            $service = $keyConfig['service'];
            $description = $keyConfig['description'];

            $this->info("Processing: {$service}");
            $this->line("  Description: {$description}");

            // Check if already exists
            if (!$force && $this->apiKeyService->hasKey($service)) {
                $this->warn("  ⚠️  Already exists (use --force to overwrite)");
                $skipped++;
                $this->newLine();
                continue;
            }

            // Get key from .env
            $key = env($keyConfig['env_key']);
            $secret = isset($keyConfig['env_secret']) ? env($keyConfig['env_secret']) : null;

            if (!$key) {
                $this->warn("  ⚠️  Not found in .env file");
                $skipped++;
                $this->newLine();
                continue;
            }

            try {
                // Store encrypted key
                $this->apiKeyService->store(
                    $service,
                    $key,
                    $secret,
                    [
                        'environment' => $environment,
                        'is_active' => true,
                    ]
                );

                $this->info("  ✅ Migrated successfully");
                $migrated++;
            } catch (\Exception $e) {
                $this->error("  ❌ Failed: " . $e->getMessage());
                $failed++;
            }

            $this->newLine();
        }

        // Summary
        $this->info('========================');
        $this->info('Migration Summary:');
        $this->info("  ✅ Migrated: {$migrated}");
        $this->info("  ⚠️  Skipped:  {$skipped}");
        $this->info("  ❌ Failed:   {$failed}");
        $this->newLine();

        if ($migrated > 0) {
            $this->info('✨ Next steps:');
            $this->line('  1. Verify keys with: php artisan api-keys:list');
            $this->line('  2. Test your services');
            $this->line('  3. Remove sensitive keys from .env file (optional)');
            $this->line('  4. Keep APP_KEY secure - it encrypts your API keys!');
        }

        return $migrated > 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
