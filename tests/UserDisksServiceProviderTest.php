<?php

namespace Biigle\Tests\Modules\UserDisks;

use Biigle\Modules\UserDisks\UserDisk;
use Biigle\Modules\UserDisks\UserDisksServiceProvider;
use Biigle\Role;
use Biigle\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Gate;
use Storage;
use TestCase;

class UserDisksServiceProviderTest extends TestCase
{
    public function testServiceProvider()
    {
        $this->assertTrue(class_exists(UserDisksServiceProvider::class));
    }

    public function testOverrideUseDiskGate()
    {
        $disk = UserDisk::factory()->create();
        $disk2 = UserDisk::factory()->create();
        $this->be($disk->user);
        $this->assertTrue(Gate::allows('use-disk', "disk-{$disk->id}"));
        $this->assertFalse(Gate::allows('use-disk', "disk-{$disk2->id}"));
    }

    public function testOverrideUseDiskGateGlobalAdmin()
    {
        $disk = UserDisk::factory()->create();
        $admin = User::factory()->create([
            'role_id' => Role::adminId(),
        ]);
        $this->be($admin);
        $this->assertTrue(Gate::allows('use-disk', "disk-{$disk->id}"));
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
        config(['user_disks.templates.s3' => [
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
