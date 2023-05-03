<?php

namespace Biigle\Tests\Modules\UserDisks;

use Biigle\Modules\UserDisks\UserDisk;
use Biigle\Modules\UserDisks\UserDisksServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Storage;
use TestCase;

class UserDisksServiceProviderTest extends TestCase
{
    public function testServiceProvider()
    {
        $this->assertTrue(class_exists(UserDisksServiceProvider::class));
    }

    public function testResolveUserDisk()
    {
        $root = storage_path('framework/testing/disks/test');
        (new Filesystem)->cleanDirectory($root);

        config(['filesystems.disks.test' => [
            'driver' => 'local',
            'root' => $root,
        ]]);
        // UserDiskType factory default name is 's3'.
        config(['user_disks.disk_templates.s3' => [
            'driver' => 'local',
            'root' => $root,
        ]]);

        $userDisk = UserDisk::factory()->create(['type' => 's3']);

        $disk = Storage::disk('test');
        $disk->put('a/b.jpg', 'abc');

        $disk = Storage::disk("disk-{$userDisk->id}");
        $this->assertSame('abc', $disk->get('a/b.jpg'));
    }
}
