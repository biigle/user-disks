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
                'bucket' => 'bucket',
                'region' => 'us-east-1',
                'endpoint' => 'http://bucket.example.com',
            ])
            ->assertStatus(201);

        $disk = UserDisk::where('user_id', $this->user()->id)->first();
        $this->assertNotNull($disk);
        $this->assertEquals('my disk', $disk->name);
        $this->assertEquals('s3', $disk->type);
        $this->assertNotNull($disk->expires_at);
        $expect = [
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => 'us-east-1',
            'endpoint' => 'http://bucket.example.com',
        ];
        $this->assertEquals($expect, $disk->options);
    }

    public function testDuplicateNames(){
        $this->beUser();
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => 'us-east-1',
            'endpoint' => 'http://bucket.example.com',
        ])
        ->assertStatus(201);

        // Disk names must be unique for one user
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => 'us-east-1',
            'endpoint' => 'http://bucket.example.com',
        ])
        ->assertStatus(422);

        $this->beEditor();
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => 'us-east-1',
            'endpoint' => 'http://bucket.example.com',
        ])
        ->assertStatus(201);
    }

    public function testStoreS3RegionEmpty()
    {
        $this->beUser();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 's3',
                'key' => 'abc',
                'secret' => 'abc',
                'bucket' => 'bucket',
                'region' => '',
                'endpoint' => 'http://bucket.example.com',
            ])
            ->assertStatus(201);

        $disk = UserDisk::where('user_id', $this->user()->id)->first();
        $this->assertArrayNotHasKey('region', $disk->options);
    }

    public function testStoreAruna()
    {
        $this->beUser();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 'aruna',
            ])
            ->assertStatus(422);

        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 'aruna',
                'key' => 'abc',
                'secret' => 'abc',
                'bucket' => 'bucket',
                'endpoint' => 'http://bucket.example.com',
                'collectionId' => 'MYARUNACOLLECTIONULID',
                'token' => 'MYSECRETTOKEN',
            ])
            ->assertStatus(201);

        $disk = UserDisk::where('user_id', $this->user()->id)->first();
        $this->assertNotNull($disk);
        $this->assertEquals('my disk', $disk->name);
        $this->assertEquals('aruna', $disk->type);
        $this->assertNotNull($disk->expires_at);
        $expect = [
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'endpoint' => 'http://bucket.example.com',
            'collectionId' => 'MYARUNACOLLECTIONULID',
            'token' => 'MYSECRETTOKEN',
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

    public function testUpdateS3()
    {
        $disk = UserDisk::factory()->create([
            'type' => 's3',
            'name' => 'abc',
            'options' => [
                'key' => 'def',
                'secret' => 'ghi',
                'bucket' => 'jkl',
                'region' => 'us-east-1',
                'endpoint' => 'https://jkl.example.com',
            ],
        ]);

        $this->be($disk->user);
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
                'type' => 'unknown',
                'name' => 'cba',
                'key' => 'fed',
                'secret' => 'ihg',
                'bucket' => 'onm',
                'region' => 'us-east-2',
                'endpoint' => 'https://onm.example.com',
            ])
            ->assertStatus(200);

        $disk->refresh();
        $expect = [
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ];
        $this->assertEquals('s3', $disk->type);
        $this->assertEquals('cba', $disk->name);
        $this->assertEquals($expect, $disk->options);
    }

    public function testUpdateAruna()
    {
        $disk = UserDisk::factory()->create([
            'type' => 'aruna',
            'name' => 'abc',
            'options' => [
                'key' => 'def',
                'secret' => 'ghi',
                'bucket' => 'jkl',
                'endpoint' => 'https://jkl.example.com',
                'collectionId' => 'pqr',
                'token' => 'stu',
            ],
        ]);

        $this->be($disk->user);
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
                'type' => 'unknown',
                'name' => 'cba',
                'key' => 'fed',
                'secret' => 'ihg',
                'bucket' => 'onm',
                'endpoint' => 'https://lkj.example.com',
                'collectionId' => 'rqp',
                'token' => 'uts',
            ])
            ->assertStatus(200);

        $disk->refresh();
        $expect = [
            'key' => 'fed',
                'secret' => 'ihg',
                'bucket' => 'onm',
                'endpoint' => 'https://lkj.example.com',
                'collectionId' => 'rqp',
                'token' => 'uts',
        ];
        $this->assertEquals('aruna', $disk->type);
        $this->assertEquals('cba', $disk->name);
        $this->assertEquals($expect, $disk->options);
    }

    public function testUpdateEmpty()
    {
        $options = [
            'key' => 'def',
            'secret' => 'ghi',
            'bucket' => 'bucket',
            'endpoint' => 'https://bucket.example.com',
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

    public function testExtend()
    {
        config(['user_disks.about_to_expire_weeks' => 4]);
        $expires = now()->addWeeks(3);
        $disk = UserDisk::factory()->create([
            'expires_at' => $expires,
        ]);
        $id = $disk->id;

        $this->doTestApiRoute('POST', "/api/v1/user-disks/{$id}/extend");

        $this->beGuest();
        $this->postJson("/api/v1/user-disks/{$id}/extend")->assertStatus(403);

        $this->be($disk->user);
        $this->postJson("/api/v1/user-disks/{$id}/extend")
            ->assertStatus(200);

        $disk->refresh();
        $this->assertTrue($disk->expires_at > $expires);
    }

    public function testExtendNotAboutToExpire()
    {
        config(['user_disks.about_to_expire_weeks' => 4]);
        $disk = UserDisk::factory()->create([
            'expires_at' => now()->addWeeks(5),
        ]);
        $id = $disk->id;

        $this->be($disk->user);
        $this->postJson("/api/v1/user-disks/{$id}/extend")->assertStatus(422);
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
