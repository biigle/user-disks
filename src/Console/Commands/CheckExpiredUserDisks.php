<?php

namespace Biigle\Modules\UserDisks\Console\Commands;

use Biigle\Modules\UserDisks\Notifications\UserDiskExpiresSoon;
use Biigle\Modules\UserDisks\UserDisk;
use Illuminate\Console\Command;

class CheckExpiredUserDisks extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'user-disks:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify owners of user disks that are about to expire';

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $now = now()->toImmutable();
        $weeks = config('user_disks.about_to_expire_weeks');
        $warnDate = $now->addWeeks($weeks);

        // All disks that are about to expire in the configured number of weeks.
        UserDisk::where('expires_at', '<', $warnDate)
            ->where('expires_at', '>=', $warnDate->subDay())
            ->eachById(function ($disk) {
                $disk->user->notify(new UserDiskExpiresSoon($disk));
            });

        // Warn again one week before expiration (unless one week is already configured).
        if ($weeks > 1) {
            $warnDate = $now->addWeek();
            UserDisk::where('expires_at', '<', $warnDate)
                ->where('expires_at', '>=', $warnDate->subDay())
                ->eachById(function ($disk) {
                    $disk->user->notify(new UserDiskExpiresSoon($disk));
                });
        }

        // Final warning one day before expiration.
        UserDisk::where('expires_at', '<', $now->addDay())
            ->where('expires_at', '>=', $now)
            ->eachById(function ($disk) {
                $disk->user->notify(new UserDiskExpiresSoon($disk));
            });
    }
}
