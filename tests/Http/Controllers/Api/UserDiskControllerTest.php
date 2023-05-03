<?php

namespace Biigle\Tests\Modules\UserDisks\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Modules\UserDisks\UserDisk;

class UserDiskControllerTest extends ApiTestCase
{
    public function testStore()
    {
        $this->doTestApiRoute('POST', "/api/v1/user-disks");

        $this->beGlobalGuest();
        $this->postJson("/api/v1/user-disks")->assertStatus(403);

        $this->beUser();
        $this->postJson("/api/v1/user-disks")->assertStatus(422);

        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
            ])
            ->assertStatus(422);

        $this->postJson("/api/v1/user-disks", [
                'type' => 's3',
            ])
            ->assertStatus(422);

        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 'unknown',
            ])
            ->assertStatus(422);

    }

    public function testStoreS3Disk()
    {
        $this->beUser();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 's3',
            ])
            ->assertStatus(422);

        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 's3',
                'key' => 'abc',
                'secret' => 'abc',
                'region' => 'abc',
                'bucket' => 'abc',
                'endpoint' => 'http://example.com',
                'use_path_style_endpoint' => true,
            ])
            ->assertStatus(201);

        $disk = UserDisk::where('user_id', $this->user()->id)->first();
        $this->assertNotNull($disk);
        $this->assertEquals('my disk', $disk->name);
        $this->assertEquals('s3', $disk->type);
        $expect = [
            'key' => 'abc',
            'secret' => 'abc',
            'region' => 'abc',
            'bucket' => 'abc',
            'endpoint' => 'http://example.com',
            'use_path_style_endpoint' => true,
        ];
        $this->assertEquals($expect, $disk->options);
    }
}
