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

    public function testCreateElements()
    {
        $this->beUser();
        $this->get('storage-disks/create?type=elements&name=abc')->assertStatus(200);
    }

    public function testCreateAruna()
    {
        $this->beUser();
        $this->get('storage-disks/create?type=aruna&name=abc')->assertStatus(200);
    }

    public function testCreateDCache()
    {
        $this->beUser();
        $this->get('storage-disks/create?type=dcache&name=abc')->assertStatus(200);
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

    public function testUpdateElements()
    {
        $disk = UserDisk::factory()->create([
            'type' => 'elements',
            'options' => [
                'baseUri' => 'https://example.com',
                'token' => 'secret',
             ],
        ]);
        $this->be($disk->user);
        $this->get("storage-disks/{$disk->id}")->assertStatus(200);
    }

    public function testUpdateAruna()
    {
        $disk = UserDisk::factory()->create([
            'type' => 'aruna',
            'options' => [
                'key' => '123',
                'secret' => '456',
                'endpoint' => 'aruna.example.com',
                'bucket' => 'example',
             ],
        ]);
        $this->be($disk->user);
        $this->get("storage-disks/{$disk->id}")->assertStatus(200);
    }

    public function testUpdateDCache()
    {
        $disk = UserDisk::factory()->create([
            'type' => 'dcache',
            'options' => [
                'token' => 'access_token',
                'refresh_token' => 'refresh_token',
                'token_expires_at' => now()->addHour(),
                'refresh_token_expires_at' => now()->addDay(),
             ],
        ]);
        $this->be($disk->user);
        $this->get("storage-disks/{$disk->id}")->assertStatus(200);
    }
}
