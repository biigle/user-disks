<?php

namespace Biigle\Tests\Modules\UserDisks\Http\Controllers\Views;

use ApiTestCase;
use Biigle\Modules\UserDisks\UserDisk;

class UserDiskControllerTest extends ApiTestCase
{
    public function testIndex()
    {
        $this->get('storage-disks')->assertStatus(302);

        $this->beGlobalGuest();
        $this->get('storage-disks')->assertStatus(403);

        $this->beUser();
        $this->get('storage-disks')->assertStatus(200);
    }

    public function testCreate()
    {
        $this->get('storage-disks/create')->assertStatus(302);

        $this->beGlobalGuest();
        $this->get('storage-disks/create')->assertStatus(403);

        $this->beUser();
        $this->get('storage-disks/create')->assertStatus(200);
    }

    public function testUpdate()
    {
        $disk = UserDisk::factory()->create([
            'options' => [
                'key' => '123',
                'secret' => '456',
                'region' => 'eu',
                'endpoint' => 's3.example.com',
                'bucket' => 'example',
             ],
        ]);
        $this->get("storage-disks/{$disk->id}")->assertStatus(302);

        $this->beUser();
        $this->get("storage-disks/{$disk->id}")->assertStatus(403);

        $this->be($disk->user);
        $this->get("storage-disks/{$disk->id}")->assertStatus(200);
    }
}
