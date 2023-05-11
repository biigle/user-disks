<?php

namespace Biigle\Modules\UserDisks\Console\Commands;

use Biigle\Modules\UserDisks\UserDisk;
use Illuminate\Console\Command;

class PruneExpiredUserDisks extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'user-disks:prune-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired user disks (after the grace period)';

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $pruneDate = now()->subWeeks(config('user_disks.delete_grace_period_weeks'));

        UserDisk::where('expires_at', '<', $pruneDate)->delete();
    }
}
