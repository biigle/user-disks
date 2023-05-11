<?php

namespace Biigle\Tests\Modules\UserDisks\Console\Commands;

use Biigle\Modules\UserDisks\UserDisk;
use TestCase;

class PruneExpiredUserDisksTest extends TestCase
{
    public function testHandle()
    {
        config(['user_disks.delete_grace_period_weeks' => 1]);

        $disk1 = UserDisk::factory()->create([
            'expires_at' => now()->subWeeks(2),
        ]);
        $disk2 = UserDisk::factory()->create([
            'expires_at' => now()->subDay(),
        ]);
        $disk3 = UserDisk::factory()->create([
            'expires_at' => now()->addDay(),
        ]);

        $this->artisan('user-disks:prune-expired')->assertExitCode(0);

        $this->assertModelMissing($disk1);
        $this->assertModelExists($disk2);
        $this->assertModelExists($disk3);

    }
}
