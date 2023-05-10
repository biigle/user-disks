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

    public function testStoreBoolean()
    {
        $this->beUser();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 's3',
                'key' => 'abc',
                'secret' => 'abc',
                'region' => 'abc',
                'bucket' => 'abc',
                'endpoint' => 'http://example.com',
                'use_path_style_endpoint' => '1',
            ])
            ->assertStatus(201);

        $disk = UserDisk::where('user_id', $this->user()->id)->first();
        $this->assertTrue($disk->options['use_path_style_endpoint']);
    }

    public function testStoreS3()
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

    public function testUpdate()
    {
        $disk = UserDisk::factory()->create();
        $this->doTestApiRoute('PUT', "/api/v1/user-disks/{$disk->id}");

        $this->beUser();
        $this->putJson("/api/v1/user-disks/{$disk->id}")->assertStatus(403);

        $this->be($disk->user);
        $this->putJson("/api/v1/user-disks/{$disk->id}")->assertStatus(200);
    }

    public function testUpdateBoolean()
    {
        $disk = UserDisk::factory()->create([
            'type' => 's3',
            'name' => 'abc',
            'options' => [
                'use_path_style_endpoint' => false,
            ],
        ]);

        $this->be($disk->user);
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
                'use_path_style_endpoint' => '1',
            ])
            ->assertStatus(200);

        $disk->refresh();
        $this->assertTrue($disk->options['use_path_style_endpoint']);
    }

    public function testUpdateS3()
    {
        $disk = UserDisk::factory()->create([
            'type' => 's3',
            'name' => 'abc',
            'options' => [
                'key' => 'def',
                'secret' => 'ghi',
                'region' => 'jkl',
                'bucket' => 'mno',
                'endpoint' => 'https://example.com',
                'use_path_style_endpoint' => false,
            ],
        ]);

        $this->be($disk->user);
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
                'use_path_style_endpoint' => 'abc',
            ])
            ->assertStatus(422);

        $this->putJson("/api/v1/user-disks/{$disk->id}", [
                'type' => 'unknown',
                'name' => 'cba',
                'key' => 'fed',
                'secret' => 'ihg',
                'region' => 'lkj',
                'bucket' => 'onm',
                'endpoint' => 'https://s3.example.com',
                'use_path_style_endpoint' => true,
            ])
            ->assertStatus(200);

        $disk->refresh();
        $expect = [
            'key' => 'fed',
            'secret' => 'ihg',
            'region' => 'lkj',
            'bucket' => 'onm',
            'endpoint' => 'https://s3.example.com',
            'use_path_style_endpoint' => true,
        ];
        $this->assertEquals('s3', $disk->type);
        $this->assertEquals('cba', $disk->name);
        $this->assertEquals($expect, $disk->options);
    }

    public function testUpdateEmpty()
    {
        $options = [
            'key' => 'def',
            'secret' => 'ghi',
            'region' => 'jkl',
            'bucket' => 'mno',
            'endpoint' => 'https://example.com',
            'use_path_style_endpoint' => false,
        ];
        $disk = UserDisk::factory()->create([
            'type' => 's3',
            'name' => 'abc',
            'options' => $options,
        ]);
        $this->be($disk->user);

        $this->putJson("/api/v1/user-disks/{$disk->id}", [
                'name' => 'cba',
                'key' => '0',
                'secret' => '',
            ])
            ->assertStatus(200);
        $disk->refresh();
        $this->assertEquals('cba', $disk->name);
        $this->assertEquals('0', $disk->options['key']);
        $this->assertEquals('ghi', $disk->options['secret']);
    }

    public function testDestroy()
    {
        $disk = UserDisk::factory()->create();
        $this->doTestApiRoute('DELETE', "/api/v1/user-disks/{$disk->id}");

        $this->beUser();
        $this->deleteJson("/api/v1/user-disks/{$disk->id}")->assertStatus(403);

        $this->be($disk->user);
        $this->deleteJson("/api/v1/user-disks/{$disk->id}")->assertStatus(200);
        $this->assertNull($disk->fresh());
    }
}
