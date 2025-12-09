<?php

namespace Biigle\Tests\Modules\UserDisks\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Modules\UserDisks\Http\Controllers\Api\UserDiskController;
use Biigle\Modules\UserDisks\UserDisk;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Mockery;

class UserDiskControllerTest extends ApiTestCase
{
    private $mockController;

    public function setUp(): void
    {
        parent::setUp();
        $this->mockController = Mockery::mock(UserDiskController::class)->shouldAllowmockingProtectedMethods()->makePartial();
        $this->app->instance(UserDiskController::class, $this->mockController);
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
        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 's3',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['bucket', 'endpoint', 'key', 'secret']);

        $this->mockController->shouldReceive('validateDiskAccess')->once();
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

    public function testStoreS3DuplicateNames()
    {
        $this->beUser();
        $this->mockController->shouldReceive('validateDiskAccess')->once();
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
        ->assertStatus(422)
        ->assertJsonValidationErrors('name');

        $this->beEditor();
        $this->mockController->shouldReceive('validateDiskAccess')->once();
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
        $this->mockController->shouldReceive('validateDiskAccess')->once();
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
        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 's3',
            ])
            ->assertStatus(422);

        $this->mockController->shouldReceive('validateDiskAccess')->once();
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


    public function testStoreS3DiskAccessTimeout()
    {
        $this->beUser();
        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('some Timeout error'));
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('some timeout error'));
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);
    }

    public function testStoreS3InvalidEndpoint()
    {
        $this->beUser();
        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('some cURL error'));
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('some curl error'));
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('some Error parsing XML'));
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('some error parsing xml'));
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);
    }

    public function testStoreS3InvalidBucket()
    {
        $this->beUser();
        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('error AccessDenied'));
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('error accessDenied'));
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('error NoSuchBucket'));
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('error noSuchBucket'));
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('error NoSuchKey'));
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('error noSuchKey'));
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);

        // bucket does not exist
        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('error InvalidAccessKeyId'));
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('error invalidAccessKeyId'));
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);
    }

    public function testStoreS3InvalidDiskConfig()
    {
        $this->beUser();
        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('some other error'));
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);
    }

    public function testStoreS3InvalidConfig()
    {
        $this->beUser();
        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'ucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $disk = UserDisk::where('user_id', $this->user()->id)->first();
        $this->assertEmpty($disk);
    }


    public function testStoreS3BucketName()
    {
        $this->beUser();
        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'ucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://ucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'ucket',
            'region' => '',
            'endpoint' => 'http://example.com/bucket',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://example.com/ucket',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket .example.com/',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://example.com/bucket.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket/example.com/.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->once();
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk1',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://example.com/bucket',
        ])->assertSuccessful();

        $this->mockController->shouldReceive('validateDiskAccess')->once();
        $this->postJson("/api/v1/user-disks", [
            'name' => 'my disk2',
            'type' => 's3',
            'key' => 'abc',
            'secret' => 'abc',
            'bucket' => 'bucket',
            'region' => '',
            'endpoint' => 'http://bucket.example.com',
        ])->assertSuccessful();
    }

    public function testStoreWebDAV()
    {
        $this->beUser();
        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 'webdav',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);

        config(['user_disks.types' => ['webdav']]);

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 'webdav',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['baseUri']);

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 'webdav',
                'baseUri' => 'https://example.com',
                'userName' => 'joe',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 'webdav',
                'baseUri' => 'https://example.com',
                'password' => 'secret',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['userName']);

        $this->mockController->shouldReceive('validateDiskAccess')->once();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 'webdav',
                'baseUri' => 'https://example.com',
                'userName' => 'joe',
                'password' => 'secret',
            ])
            ->assertStatus(201);

        $disk = UserDisk::where('user_id', $this->user()->id)->first();
        $this->assertNotNull($disk);
        $this->assertEquals('my disk', $disk->name);
        $this->assertEquals('webdav', $disk->type);
        $this->assertNotNull($disk->expires_at);
        $expect = [
            'baseUri' => 'https://example.com',
            'userName' => 'joe',
            'password' => 'secret',
        ];
        $this->assertEquals($expect, $disk->options);
    }

    public function testStoreWebDAVNoAuth()
    {
        config(['user_disks.types' => ['webdav']]);
        $this->beUser();
        $this->mockController->shouldReceive('validateDiskAccess')->once();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 'webdav',
                'baseUri' => 'https://example.com',
            ])
            ->assertStatus(201);
    }

    public function testStoreWebDAVPrefix()
    {
        config(['user_disks.types' => ['webdav']]);
        $this->beUser();
        $this->mockController->shouldReceive('validateDiskAccess')->once();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 'webdav',
                'baseUri' => 'https://example.com/remote.php/dav/files/joe',
            ])
            ->assertStatus(201);

        $disk = UserDisk::where('user_id', $this->user()->id)->first();
        $this->assertSame('https://example.com/', $disk->options['baseUri']);
        $this->assertSame('remote.php/dav/files/joe', $disk->options['pathPrefix']);
    }

    public function testStoreElements()
    {
        $this->beUser();
        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 'elements',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);

        config(['user_disks.types' => ['elements']]);

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 'elements',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['baseUri', 'token']);

        $this->mockController->shouldReceive('validateDiskAccess')->once();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 'elements',
                'baseUri' => 'https://example.com',
                'token' => 'secret',
            ])
            ->assertStatus(201);

        $disk = UserDisk::where('user_id', $this->user()->id)->first();
        $this->assertNotNull($disk);
        $this->assertEquals('my disk', $disk->name);
        $this->assertEquals('elements', $disk->type);
        $this->assertNotNull($disk->expires_at);
        $expect = [
            'baseUri' => 'https://example.com',
            'token' => 'secret',
        ];
        $this->assertEquals($expect, $disk->options);
    }

    public function testStoreAruna()
    {
        $this->beUser();

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 'elements',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);

        config(['user_disks.types' => ['aruna']]);

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 'aruna',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['bucket', 'endpoint', 'key', 'secret']);

        $this->mockController->shouldReceive('validateDiskAccess')->once();
        $this->postJson("/api/v1/user-disks", [
                'name' => 'my disk',
                'type' => 'aruna',
                'key' => 'abc',
                'secret' => 'abc',
                'bucket' => 'bucket',
                'endpoint' => 'http://bucket.example.com',
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
        ];
        $this->assertEquals($expect, $disk->options);
    }

    public function testUpdate()
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
        $this->doTestApiRoute('PUT', "/api/v1/user-disks/{$disk->id}");

        $this->beUser();
        $this->putJson("/api/v1/user-disks/{$disk->id}")->assertStatus(403);

        $this->be($disk->user);
        $this->mockController->shouldReceive('validateDiskAccess')->once();
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
        $this->mockController->shouldReceive('validateDiskAccess')->once();
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
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
        $this->mockController->shouldReceive('validateDiskAccess')->once();
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

        $this->mockController->shouldReceive('validateDiskAccess')->once();
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

    public function testUpdateS3BucketName()
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

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://m.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'm',
            'region' => 'us-east-2',
            'endpoint' => 'https://example.com/onm',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://example.com/m',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'type' => 's3',
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'bucket',
            'region' => 'us-east-2',
            'endpoint' => 'https://example.com/bucket.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'type' => 's3',
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'bucket',
            'region' => 'us-east-2',
            'endpoint' => 'https://bucket .example.com/',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'type' => 's3',
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'bucket',
            'region' => 'us-east-2',
            'endpoint' => 'https://bucket/example.com/',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->once();
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'type' => 's3',
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'bucket',
            'region' => 'us-east-2',
            'endpoint' => 'https://bucket.example.com/',
        ])->assertSuccessful();

        $this->mockController->shouldReceive('validateDiskAccess')->once();
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'type' => 's3',
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'bucket',
            'region' => 'us-east-2',
            'endpoint' => 'https://example.com/bucket',
        ])->assertSuccessful();
    }

    public function testUpdateS3DiskAccessTimeout()
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
        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('some Timeout error'));
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('some timeout error'));
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);
    }

    public function testUpdateS3InvalidEndpoint()
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
        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('some cURL error'));
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('some curl error'));
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('some Error parsing XML'));
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('some error parsing xml'));
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);
    }

    public function testUpdateS3InvalidBucket()
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
        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('error AccessDenied'));
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('error accessDenied'));
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('error NoSuchBucket'));
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('error noSuchBucket'));
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('error NoSuchKey'));
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('error noSuchKey'));
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);

        // bucket does not exist
        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('error InvalidAccessKeyId'));
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);

        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('error invalidAccessKeyId'));
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);
    }

    public function testUpdateS3InvalidDiskConfig()
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
        $this->mockController->shouldReceive('validateDiskAccess')->once()->andThrow(new Exception('some other error'));
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'name' => 'cba',
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'region' => 'us-east-2',
            'endpoint' => 'https://onm.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['error']);
    }

    public function testUpdateS3InvalidConfig()
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
        $this->mockController->shouldReceive('validateDiskAccess')->never();
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
            'endpoint' => 'https://bucket.example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint']);

        $disk = $disk->fresh();
        $this->assertEquals('https://jkl.example.com', $disk->options['endpoint']);
    }

    public function testUpdateS3Empty()
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

        $this->mockController->shouldReceive('validateDiskAccess')->once();
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

    public function testUpdateWebDAV()
    {
        config(['user_disks.types' => ['webdav']]);

        $disk = UserDisk::factory()->create([
            'type' => 'webdav',
            'name' => 'abc',
            'options' => [
                'baseUri' => 'https://example.com',
                'userName' => 'joe',
                'password' => 'secret',
            ],
        ]);

        $this->be($disk->user);
        $this->mockController->shouldReceive('validateDiskAccess')->once();
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
                'name' => 'cba',
                'baseUri' => 'https://dir.example.com/',
                'userName' => 'jane',
                'password' => 'more secret',
            ])
            ->assertStatus(200);

        $disk->refresh();
        $expect = [
            'baseUri' => 'https://dir.example.com/',
            'userName' => 'jane',
            'password' => 'more secret',
        ];
        $this->assertEquals('webdav', $disk->type);
        $this->assertEquals('cba', $disk->name);
        $this->assertEquals($expect, $disk->options);
    }

    public function testUpdateWebDAVPrefix()
    {
        config(['user_disks.types' => ['webdav']]);

        $disk = UserDisk::factory()->create([
            'type' => 'webdav',
            'name' => 'abc',
            'options' => [
                'baseUri' => 'https://example.com',
                'userName' => 'joe',
                'password' => 'secret',
            ],
        ]);

        $this->be($disk->user);
        $this->mockController->shouldReceive('validateDiskAccess')->once();
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
                'baseUri' => 'https://example.com/dir',
            ])
            ->assertStatus(200);

        $disk->refresh();
        $this->assertSame('https://example.com/', $disk->options['baseUri']);
        $this->assertSame('dir', $disk->options['pathPrefix']);
    }

    public function testUpdateElements()
    {
        config(['user_disks.types' => ['elements']]);

        $disk = UserDisk::factory()->create([
            'type' => 'elements',
            'name' => 'abc',
            'options' => [
                'baseUri' => 'https://example.com',
                'token' => 'secret',
            ],
        ]);

        $this->be($disk->user);
        $this->mockController->shouldReceive('validateDiskAccess')->once();
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
                'name' => 'cba',
                'baseUri' => 'https://example.com/dir',
                'token' => 'more secret',
            ])
            ->assertStatus(200);

        $disk->refresh();
        $expect = [
            'baseUri' => 'https://example.com/dir',
            'token' => 'more secret',
        ];
        $this->assertEquals('elements', $disk->type);
        $this->assertEquals('cba', $disk->name);
        $this->assertEquals($expect, $disk->options);
    }

    public function testUpdateAruna()
    {
        config(['user_disks.types' => ['aruna']]);

        $disk = UserDisk::factory()->create([
            'type' => 'aruna',
            'name' => 'abc',
            'options' => [
                'key' => 'def',
                'secret' => 'ghi',
                'bucket' => 'jkl',
                'endpoint' => 'https://jkl.example.com',
            ],
        ]);

        $this->be($disk->user);
        $this->mockController->shouldReceive('validateDiskAccess')->once();
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
                'name' => 'cba',
                'key' => 'fed',
                'secret' => 'ihg',
                'bucket' => 'onm',
                'endpoint' => 'https://onm.example.com',
            ])
            ->assertStatus(200);

        $disk->refresh();
        $expect = [
            'key' => 'fed',
            'secret' => 'ihg',
            'bucket' => 'onm',
            'endpoint' => 'https://onm.example.com',
        ];
        $this->assertEquals('aruna', $disk->type);
        $this->assertEquals('cba', $disk->name);
        $this->assertEquals($expect, $disk->options);
    }

    public function testUpdateDCache()
    {
        config(['user_disks.types' => ['dcache']]);

        $disk = UserDisk::factory()->create([
            'type' => 'dcache',
            'name' => 'my dcache',
            'options' => [
                'token' => 'access_token',
                'refresh_token' => 'refresh_token',
                'token_expires_at' => now()->addHour(),
                'refresh_token_expires_at' => now()->addDay(),
            ],
        ]);

        $this->be($disk->user);
        $this->mockController->shouldReceive('validateDiskAccess')->once();
        $this->putJson("/api/v1/user-disks/{$disk->id}", [
                'pathPrefix' => '/user/data',
            ])
            ->assertStatus(200);

        $disk->refresh();
        $this->assertSame('/user/data', $disk->options['pathPrefix']);
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

    public function testStoreDCacheSuccess()
    {
        config([
            'user_disks.types' => ['dcache'],
            'user_disks.dcache-token-exchange.client_id' => 'test-client-id',
            'user_disks.dcache-token-exchange.client_secret' => 'test-client-secret',
        ]);

        $this->beUser();

        $socialiteUser = (object) ['token' => 'oidc-access-token'];

        $socialiteProvider = Mockery::mock(Provider::class);
        $socialiteProvider->shouldReceive('redirectUrl')->with(url('/user-disks/dcache/callback'))->andReturnSelf();
        $socialiteProvider->shouldReceive('setScopes')->with(['openid', 'profile', 'email', 'eduperson_principal_name'])->andReturnSelf();
        $socialiteProvider->shouldReceive('user')->andReturn($socialiteUser);
        $socialiteProvider->shouldReceive('redirect');

        Socialite::shouldReceive('driver')
            ->with('haai')
            ->andReturn($socialiteProvider);

        Http::fake([
            UserDisk::DCACHE_TOKEN_ENDPOINT => Http::response([
                'access_token' => 'dcache-access-token',
                'refresh_token' => 'dcache-refresh-token',
                'expires_in' => 3600,
                'refresh_expires_in' => 86400,
            ], 200),
        ]);

        $response = $this->post("/api/v1/user-disks", [
            'name' => 'my dcache disk',
            'type' => 'dcache',
            'pathPrefix' => '/user/data',
        ]);

        $this->assertEquals('my dcache disk', session('dcache-disk-name'));
        $this->assertEquals('/user/data', session('dcache-disk-pathPrefix'));

        $this->mockController->shouldReceive('validateDiskAccess')->once();
        $response = $this->get('/user-disks/dcache/callback');

        $disk = UserDisk::where('user_id', $this->user()->id)->first();
        $this->assertNotNull($disk);
        $this->assertEquals('my dcache disk', $disk->name);
        $this->assertEquals('dcache', $disk->type);
        $this->assertNotNull($disk->expires_at);
        $this->assertEquals('dcache-access-token', $disk->options['token']);
        $this->assertEquals('dcache-refresh-token', $disk->options['refresh_token']);
        $this->assertEquals('/user/data', $disk->options['pathPrefix']);
        $this->assertArrayHasKey('token_expires_at', $disk->options);
        $this->assertArrayHasKey('refresh_token_expires_at', $disk->options);
    }

    public function testStoreDCacheErrorObtainingAAIAttributes()
    {
        config([
            'user_disks.types' => ['dcache'],
            'user_disks.dcache-token-exchange.client_id' => 'test-client-id',
            'user_disks.dcache-token-exchange.client_secret' => 'test-client-secret',
        ]);

        $this->beUser();

        $socialiteProvider = Mockery::mock(Provider::class);
        $socialiteProvider->shouldReceive('redirectUrl')->andThrow(new Exception);

        Socialite::shouldReceive('driver')
            ->with('haai')
            ->andReturn($socialiteProvider);

        Log::shouldReceive('error')->once();

        $response = $this->get('/user-disks/dcache/callback');

        $response->assertRedirect(route('create-storage-disks'));
        $response->assertSessionHas('messageType', 'danger');
        $response->assertSessionHas('message', 'There was an error while obtaining the user attributes.');

        $disk = UserDisk::where('user_id', $this->user()->id)->first();
        $this->assertNull($disk);
    }

    public function testStoreDCacheErrorDuringTokenExchange()
    {
        config([
            'user_disks.types' => ['dcache'],
            'user_disks.dcache-token-exchange.client_id' => 'test-client-id',
            'user_disks.dcache-token-exchange.client_secret' => 'test-client-secret',
        ]);

        $this->beUser();

        $socialiteUser = (object) ['token' => 'oidc-access-token'];

        $socialiteProvider = Mockery::mock(Provider::class);
        $socialiteProvider->shouldReceive('redirectUrl')->andReturnSelf();
        $socialiteProvider->shouldReceive('setScopes')->andReturnSelf();
        $socialiteProvider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')
            ->with('haai')
            ->andReturn($socialiteProvider);

        Log::shouldReceive('error')->once();

        Http::shouldReceive('asForm')->andReturnSelf();
        Http::shouldReceive('post')->andThrow(new Exception);

        $response = $this->get('/user-disks/dcache/callback');

        $response->assertRedirect(route('create-storage-disks'));
        $response->assertSessionHas('messageType', 'danger');
        $response->assertSessionHas('message', 'There was an error while obtaining a dCache token.');

        $disk = UserDisk::where('user_id', $this->user()->id)->first();
        $this->assertNull($disk);
    }
}
