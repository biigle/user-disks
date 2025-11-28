<?php

namespace Biigle\Modules\UserDisks\Console\Commands;

use Biigle\Modules\UserDisks\Http\Controllers\Api\UserDiskController;
use Biigle\Modules\UserDisks\UserDisk;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class RefreshDCacheTokens extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'user-disks:refresh-dcache-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh dCache storage disk tokens that are about to expire';

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $expirationThreshold = now()->addHours(2);

        // Find all dcache disks where the refresh token expires within 2 hours
        UserDisk::where('type', 'dcache')
            ->get()
            ->filter(function ($disk) use ($expirationThreshold) {
                $refreshTokenExpiresAt = $disk->options['refresh_token_expires_at'] ?? null;

                if (is_null($refreshTokenExpiresAt)) {
                    return false;
                }

                return Carbon::parse($refreshTokenExpiresAt) <= $expirationThreshold;
            })
            ->each(function ($disk) {
                $this->refreshToken($disk);
            });
    }

    /**
     * Refresh the access and refresh tokens for a dcache disk.
     *
     * @param UserDisk $disk
     * @return void
     */
    protected function refreshToken(UserDisk $disk)
    {
        $refreshToken = $disk->options['refresh_token'] ?? null;

        if (!$refreshToken) {
            $this->warn("Disk {$disk->id} ({$disk->name}) has no refresh token");
            return;
        }

        $postData = [
            'client_id' => config('services.dcache-token-exchange.client_id'),
            'client_secret' => config('services.dcache-token-exchange.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];

        try {
            $response = Http::asForm()->post(UserDiskController::DESY_TOKEN_ENDPOINT, $postData);

            if (!$response->successful()) {
                $this->error("Failed to refresh token for disk {$disk->id} ({$disk->name}): HTTP {$response->status()}");
                return;
            }

            $data = $response->json();

            $options = $disk->options;
            $options['token'] = $data['access_token'];
            $options['refresh_token'] = $data['refresh_token'];
            $options['token_expires_at'] = now()->addSeconds($data['expires_in']);
            $options['refresh_token_expires_at'] = now()->addSeconds($data['refresh_expires_in']);

            $disk->update(['options' => $options]);

            $this->info("Successfully refreshed token for disk {$disk->id} ({$disk->name})");
        } catch (Exception $e) {
            $this->error("Failed to refresh token for disk {$disk->id} ({$disk->name}): {$e->getMessage()}");
        }
    }
}
