<?php

namespace Biigle\Tests\Modules\UserDisks\Console\Commands;

use Biigle\Modules\UserDisks\Notifications\UserDiskExpiresSoon;
use Biigle\Modules\UserDisks\UserDisk;
use Illuminate\Support\Facades\Notification;
use TestCase;

class CheckExpiredUserDisksTest extends TestCase
{
    public function testHandle()
    {
        config(['user_disks.about_to_expire_weeks' => 2]);
        Notification::fake();
        $disk1 = UserDisk::factory()->create([
            'expires_at' => now()->addWeeks(2)->subHour(),
        ]);
        $disk2 = UserDisk::factory()->create([
            'expires_at' => now()->addWeek()->subHour(),
        ]);
        $disk3 = UserDisk::factory()->create([
            'expires_at' => now()->addDay()->subHour(),
        ]);
        $disk4 = UserDisk::factory()->create([
            'expires_at' => now()->addDays(2),
        ]);
        $disk5 = UserDisk::factory()->create([
            'expires_at' => now()->addWeeks(4),
        ]);
        $disk6 = UserDisk::factory()->create([
            'expires_at' => now()->subDay(),
        ]);

        $this->artisan('user-disks:check')->assertExitCode(0);

        Notification::assertSentTo([$disk1->user], UserDiskExpiresSoon::class);
        Notification::assertSentTo([$disk2->user], UserDiskExpiresSoon::class);
        Notification::assertSentTo([$disk3->user], UserDiskExpiresSoon::class);

        Notification::assertNotSentTo([$disk4->user], UserDiskExpiresSoon::class);
        Notification::assertNotSentTo([$disk5->user], UserDiskExpiresSoon::class);
        Notification::assertNotSentTo([$disk6->user], UserDiskExpiresSoon::class);
    }
}
