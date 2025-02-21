<?php

namespace Biigle\Tests\Modules\UserDisks\Http\Controllers\Api;

use Mockery;
use ApiTestCase;
use Biigle\Modules\UserDisks\UserDisk;
use Biigle\Modules\UserDisks\Http\Controllers\Api\UserDiskController;

class UserDiskControllerTest extends ApiTestCase
{
    private $mockS3 = null;
    public function setUp(): void
    {
        parent::setUp();
        $this->mockS3 = Mockery::mock(UserDiskController::class)->shouldAllowmockingProtectedMethods()->makePartial();
        $this->app->instance(UserDiskController::class, $this->mockS3);
    }

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

        $this->mockS3->shouldReceive('canAccessDisk')->never();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 's3',
            ])
            ->assertStatus(422);

        $this->mockS3->shouldReceive('canAccessDisk')->once()->andReturn([]);
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 's3',
                'key' => 'abc',
                'secret' => 'abc',
                'bucket' => 'bucket',
                'region' => 'us-east-1',
                // Use a trailing slash to trick the path-style detection.
                'endpoint' => 'http://bucket.example.com/',
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
            'endpoint' => 'http://bucket.example.com/',
            'use_path_style_endpoint' => false,
        ];
        $this->assertEquals($expect, $disk->options);
    }

    public function testDuplicateNames(){
        $this->beUser();

        $this->mockS3->shouldReceive('canAccessDisk')->once()->andReturn([]);
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
        $this->mockS3->shouldReceive('canAccessDisk')->once()->andReturn([]);
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

        $this->mockS3->shouldReceive('canAccessDisk')->once()->andReturn([]);
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

    public function testStoreS3PathStyle()
    {
        $this->beUser();

        $this->mockS3->shouldReceive('canAccessDisk')->never();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 's3',
            ])
            ->assertStatus(422);

        $this->mockS3->shouldReceive('canAccessDisk')->once()->andReturn([]);
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 's3',
                'key' => 'abc',
                'secret' => 'abc',
                'bucket' => 'bucket',
                'region' => '',
                'endpoint' => 'http://example.com/bucket',
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
            'endpoint' => 'http://example.com/bucket',
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
                'use_path_style_endpoint' => false,
            ],
        ]);

        $this->be($disk->user);
        $this->mockS3->shouldReceive('canAccessDisk')->once()->andReturn([]);
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
            'use_path_style_endpoint' => false,
        ];
        $this->assertEquals('s3', $disk->type);
        $this->assertEquals('cba', $disk->name);
        $this->assertEquals($expect, $disk->options);
    }

    public function testUpdateS3PathStyle()
    {
        $disk = UserDisk::factory()->create([
            'type' => 's3',
            'name' => 'abc',
            'options' => [
                'key' => 'def',
                'secret' => 'ghi',
                'bucket' => 'jkl',
                'endpoint' => 'https://jkl.example.com',
                'use_path_style_endpoint' => false,
            ],
        ]);

        $this->be($disk->user);
        $this->mockS3->shouldReceive('canAccessDisk')->once()->andReturn([]);
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
                'endpoint' => 'https://example.com/jkl',
            ])
            ->assertStatus(200);

        $disk->refresh();
        $expect = [
            'key' => 'def',
            'secret' => 'ghi',
            'bucket' => 'jkl',
            'endpoint' => 'https://example.com/jkl',
            'use_path_style_endpoint' => true,
        ];
        $this->assertEquals($expect, $disk->options);

        $this->mockS3->shouldReceive('canAccessDisk')->once()->andReturn([]);
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
                'endpoint' => 'https://jkl.example.com/',
            ])
            ->assertStatus(200);

        $disk->refresh();
        $expect = [
            'key' => 'def',
            'secret' => 'ghi',
            'bucket' => 'jkl',
            'endpoint' => 'https://jkl.example.com/',
            'use_path_style_endpoint' => false,
        ];
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
    public function testStoreInvalidS3Config()
    {
        $this->beUser();

        $this->mockS3->shouldReceive('canAccessDisk')->never();
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'ucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])
            ->assertUnprocessable();

        $disk = UserDisk::where('user_id', $this->user()->id)->first();
        $this->assertEmpty($disk);
    }

    public function testUpdateInvalidS3Config()
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
                'use_path_style_endpoint' => false,
            ],
        ]);

        $this->be($disk->user);
        $this->mockS3->shouldReceive('canAccessDisk')->never();
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'endpoint' => 'https://bucket.example.com',
        ])
            ->assertUnprocessable();

        $disk = $disk->fresh();
        $this->assertEquals('https://jkl.example.com', $disk->options['endpoint']);
    }

    public function testStoreIncorrectBucketName()
    {
        $this->beUser();

        $this->mockS3->shouldReceive('canAccessDisk')->never();
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'ucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])
            ->assertUnprocessable();

        $this->mockS3->shouldReceive('canAccessDisk')->never();
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://ucket.example.com',
        ])
            ->assertUnprocessable();

        $this->mockS3->shouldReceive('canAccessDisk')->never();
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'ucket',
            'region' => '',
            'endpoint' => 'http://example.com/bucket',
        ])
            ->assertUnprocessable();

        $this->mockS3->shouldReceive('canAccessDisk')->never();
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://example.com/ucket',
        ])
            ->assertUnprocessable();
    }

    public function testUpdateIncorrectBucketName()
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
                'use_path_style_endpoint' => false,
            ],
        ]);

        $this->be($disk->user);

        $this->mockS3->shouldReceive('canAccessDisk')->once()->andReturn([]);
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'type' => 'unknown',
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ])
            ->assertUnprocessable();

        $this->mockS3->shouldReceive('canAccessDisk')->once()->andReturn([]);
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'type' => 'unknown',
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://m.example.com',
        ])
            ->assertUnprocessable();

        $this->mockS3->shouldReceive('canAccessDisk')->once()->andReturn([]);
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'type' => 'unknown',
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'm',
            'region' => 'us-east-2',
            'endpoint' => 'https://example.com/onm',
        ])
            ->assertUnprocessable();

        $this->mockS3->shouldReceive('canAccessDisk')->once()->andReturn([]);
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'type' => 'unknown',
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://example.com/m',
        ])
            ->assertUnprocessable();
    }
}
