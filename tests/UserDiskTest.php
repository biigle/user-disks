<?php

namespace Biigle\Tests\Modules\UserDisks;

use Biigle\Modules\UserDisks\UserDisk;
use Biigle\Tests\UserTest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use ModelTestCase;

class UserDiskTest extends ModelTestCase
{
    /**
     * The model class this class will test.
     */
    protected static $modelClass = UserDisk::class;

    public function testAttributes()
    {
        $this->assertNotNull($this->model->type);
        $this->assertNotNull($this->model->name);
        $this->assertNotNull($this->model->options);
        $this->assertNotNull($this->model->user);
        $this->assertNotNull($this->model->created_at);
        $this->assertNotNull($this->model->updated_at);
    }

    public function testEncryptOptions()
    {
        $options = [
            'id' => 'abcde',
            'secret' => 'fghij',
        ];
        $this->model->options = $options;
        $attributes = $this->model->getAttributes();
        $this->assertEquals($options, json_decode(Crypt::decryptString($attributes['options']), true));
    }

    public function testGetConfigTemplate()
    {
        $template = [
            'driver' => 'local',
            'key' => 'value',
        ];
        config(['user_disks.templates.test' => $template]);

        $this->assertEquals($template, UserDisk::getConfigTemplate('test'));
    }

    public function testGetConfig()
    {
        $template = [
            'driver' => 'local',
            'key' => 'value',
        ];
        config(['user_disks.templates.test' => $template]);

        $disk = UserDisk::factory()->make([
            'type' => 'test',
            'options' => [
                'key' => 'abc',
            ],
        ]);

        $expect = [
            'driver' => 'local',
            'key' => 'abc',
            'read-only' => true,
        ];

        $this->assertEquals($expect, $disk->getConfig());
    }

    public function testGetConfigReadOnly()
    {
        $template = [
            'driver' => 'local',
            'read-only' => false,
        ];
        config(['user_disks.templates.test' => $template]);

        $disk = UserDisk::factory()->make([
            'type' => 'test',
            'options' => [
                'read-only' => false,
            ],
        ]);

        $expect = [
            'driver' => 'local',
            'read-only' => true,
        ];

        $this->assertEquals($expect, $disk->getConfig());
    }

    public function testGetS3Config()
    {
        $disk = UserDisk::factory()->make([
            'type' => 's3',
            'options' => [
                'key' => 'abc',
                'secret' => 'efg',
                'bucket' => 'bucket',
                'endpoint' => 'https://bucket.s3.example.com',
            ],
        ]);

        $expect = [
            'driver' => 's3',
            'key' => 'abc',
            'secret' => 'efg',
            'bucket' => 'bucket',
            'endpoint' => 'https://bucket.s3.example.com',
            'stream_reads' => true,
            'http' => [
                'connect_timeout' => 5,
            ],
            'throw' => true,
            'read-only' => true,
            'bucket_endpoint' => true,
            'region' => 'us-east-1',
        ];

        $this->assertEquals($expect, $disk->getConfig());
    }

    public function testGetConfigTemplateDoesNotExist()
    {
        $this->expectException(\TypeError::class);
        $this->model->type = 'test';
        $this->model->getConfig();
    }

    public function testGetStoreValidationRules()
    {
        $rules = [
            'secret' => 'required',
            'key' => 'required',
        ];
        config(['user_disks.store_validation.test' => $rules]);

        $this->assertEquals($rules, UserDisk::getStoreValidationRules('test'));
    }

    public function testGetStoreValidationRulesS3()
    {
        $this->assertNotEmpty(UserDisk::getStoreValidationRules('s3'));
    }

    public function testGetUpdateValidationRules()
    {
        $rules = [
            'secret' => 'required',
            'key' => 'required',
        ];
        config(['user_disks.update_validation.test' => $rules]);

        $this->assertEquals($rules, UserDisk::getUpdateValidationRules('test'));
    }

    public function testGetUpdateValidationRulesS3()
    {
        $this->assertNotEmpty(UserDisk::getUpdateValidationRules('s3'));
    }

    public function testIsAboutToExpire()
    {
        config(['user_disks.about_to_expire_weeks' => 4]);
        $this->model->expires_at = now()->addWeeks(5);
        $this->assertFalse($this->model->isAboutToExpire());
        $this->model->expires_at = now()->addWeeks(3);
        $this->assertTrue($this->model->isAboutToExpire());
    }

    public function testExtend()
    {
        config(['user_disks.expires_months' => 2]);
        $time = $this->model->expires_at;
        $this->model->extend();
        $this->assertNotEquals($time->toDateTimeString(), $this->model->expires_at->toDateTimeString());
    }

    public function testUniqueProperties()
    {
        self::create([
            'name' => $this->model->name,
            'user_id' => UserTest::create()->id
        ]);

        $this->expectException(UniqueConstraintViolationException::class);
        // User already uses a disk with this name
        self::create([
            'name' => $this->model->name,
            'user_id' => $this->model->user_id
        ]);
    }

    public function testIsDCacheAccessTokenExpiring()
    {
        $disk = UserDisk::factory()->create([
            'type' => 'dcache',
            'options' => [
                'token_expires_at' => '2025-11-27 16:06:00',
            ],
        ]);

        $this->assertTrue($disk->isDCacheAccessTokenExpiring());
    }

    public function testIsDCacheAccessTokenExpiringExactlyOneMinute()
    {
        $disk = UserDisk::factory()->create([
            'type' => 'dcache',
            'options' => [
                'token_expires_at' => now()->addMinute(),
            ],
        ]);

        $this->assertTrue($disk->isDCacheAccessTokenExpiring());
    }

    public function testIsDCacheAccessTokenExpiringMoreThanOneMinute()
    {
        $disk = UserDisk::factory()->create([
            'type' => 'dcache',
            'options' => [
                'token_expires_at' => now()->addMinutes(2),
            ],
        ]);

        $this->assertFalse($disk->isDCacheAccessTokenExpiring());
    }

    public function testIsDCacheAccessTokenExpiringNonDCache()
    {
        $disk = UserDisk::factory()->create([
            'type' => 's3',
            'options' => [
                'token_expires_at' => now()->addHour(),
            ],
        ]);

        $this->assertFalse($disk->isDCacheAccessTokenExpiring());
    }

    public function testIsDCacheRefreshTokenExpiring()
    {
        $disk = UserDisk::factory()->create([
            'type' => 'dcache',
            'options' => [
                'refresh_token_expires_at' => '2025-11-27 16:06:00',
            ],
        ]);

        $this->assertTrue($disk->isDCacheRefreshTokenExpiring());
    }

    public function testIsDCacheRefreshTokenExpiringExactlyTwoHours()
    {
        $disk = UserDisk::factory()->create([
            'type' => 'dcache',
            'options' => [
                'refresh_token_expires_at' => now()->addHours(2),
            ],
        ]);

        $this->assertTrue($disk->isDCacheRefreshTokenExpiring());
    }

    public function testIsDCacheRefreshTokenExpiringMoreThanTwoHours()
    {
        $disk = UserDisk::factory()->create([
            'type' => 'dcache',
            'options' => [
                'refresh_token_expires_at' => now()->addHours(3),
            ],
        ]);

        $this->assertFalse($disk->isDCacheRefreshTokenExpiring());
    }

    public function testIsDCacheRefreshTokenExpiringNonDCache()
    {
        $disk = UserDisk::factory()->create([
            'type' => 's3',
            'options' => [
                'refresh_token_expires_at' => now()->addHour(),
            ],
        ]);

        $this->assertFalse($disk->isDCacheRefreshTokenExpiring());
    }

    public function testRefreshDCacheToken()
    {
        config([
            'services.dcache-token-exchange.client_id' => 'test-client-id',
            'services.dcache-token-exchange.client_secret' => 'test-client-secret',
        ]);

        Http::fake([
            'keycloak.desy.de/*' => Http::response([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'expires_in' => 3600,
                'refresh_expires_in' => 7200,
            ], 200),
        ]);

        $disk = UserDisk::factory()->create([
            'type' => 'dcache',
            'options' => [
                'token' => 'old-token',
                'refresh_token' => 'old-refresh-token',
                'token_expires_at' => now()->addMinutes(30),
                'refresh_token_expires_at' => now()->addHour(),
            ],
        ]);

        $result = $disk->refreshDCacheToken();

        $this->assertTrue($result);
        $disk->refresh();
        $this->assertEquals('new-access-token', $disk->options['token']);
        $this->assertEquals('new-refresh-token', $disk->options['refresh_token']);
    }

    public function testRefreshDCacheTokenHttpError()
    {
        config([
            'services.dcache-token-exchange.client_id' => 'test-client-id',
            'services.dcache-token-exchange.client_secret' => 'test-client-secret',
        ]);

        Http::fake([
            'keycloak.desy.de/*' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $disk = UserDisk::factory()->create([
            'type' => 'dcache',
            'options' => [
                'token' => 'old-token',
                'refresh_token' => 'old-refresh-token',
            ],
        ]);

        $result = $disk->refreshDCacheToken();

        $this->assertFalse($result);
        $disk->refresh();
        $this->assertEquals('old-token', $disk->options['token']);
        $this->assertEquals('old-refresh-token', $disk->options['refresh_token']);
    }
}
