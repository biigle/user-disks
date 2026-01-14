<?php

namespace Biigle\Modules\UserDisks\Console\Commands;

use Biigle\Modules\UserDisks\UserDisk;
use Illuminate\Console\Command;

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
        // Find all dcache disks where the refresh token expires within 2 hours
        UserDisk::where('type', 'dcache')
            ->get()
            ->filter(fn ($disk) => $disk->isDCacheRefreshTokenExpiring())
            ->each(function ($disk) {
                $success = $disk->refreshDCacheToken();

                if ($success) {
                    $this->info("Successfully refreshed token for disk {$disk->id} ({$disk->name})");
                } else {
                    $this->warn("Failed to refresh token for disk {$disk->id} ({$disk->name})");
                }
            });
    }
}
