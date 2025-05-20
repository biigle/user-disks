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

    public function testCreateS3()
    {
        $this->beUser();
        $this->get('storage-disks/create?type=s3&name=abc')->assertStatus(200);
    }

    public function testCreateWebDAV()
    {
        $this->beUser();
        $this->get('storage-disks/create?type=webdav&name=abc')->assertStatus(200);
    }

    public function testCreateInvalid()
    {
        $this->beUser();
        $this->get('storage-disks/create?type=invalid&name=abc')->assertStatus(404);
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

    public function testUpdateWebDAV()
    {
        $disk = UserDisk::factory()->create([
            'type' => 'webdav',
            'options' => [
                'baseUri' => 'https://example.com',
                'userName' => 'joe',
                'password' => 'secret',
             ],
        ]);
        $this->be($disk->user);
        $this->get("storage-disks/{$disk->id}")->assertStatus(200);
    }
}
