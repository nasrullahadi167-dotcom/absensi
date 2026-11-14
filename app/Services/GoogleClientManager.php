<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;

class GoogleClientManager
{
    protected ?GoogleClient $client = null;

    /**
     * Get or create the Google Client instance.
     */
    public function getClient(): ?GoogleClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $credentialsPath = config('services.google.credentials_path');

        if (! $credentialsPath) {
            return null;
        }

        // Check if relative path or absolute path
        if (! file_exists($credentialsPath)) {
            $basePath = base_path($credentialsPath);
            if (file_exists($basePath)) {
                $credentialsPath = $basePath;
            } else {
                Log::warning("Google credentials file not found at: {$credentialsPath}");

                return null;
            }
        }

        try {
            $client = new GoogleClient;
            $client->setAuthConfig($credentialsPath);
            $client->setApplicationName(config('app.name', 'ORGA-ATTEND'));
            $client->setScopes([
                Sheets::SPREADSHEETS,
                Drive::DRIVE,
            ]);
            $client->setAccessType('offline');

            $this->client = $client;

            return $this->client;
        } catch (\Throwable $e) {
            Log::error('Failed to initialize Google Client: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Check if Google integration is configured and credentials exist.
     */
    public function isConfigured(): bool
    {
        return $this->getClient() !== null;
    }
}
