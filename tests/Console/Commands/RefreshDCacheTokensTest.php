<?php

namespace Biigle\Tests\Modules\UserDisks\Console\Commands;

use Biigle\Modules\UserDisks\UserDisk;
use Illuminate\Support\Facades\Http;
use TestCase;

class RefreshDCacheTokensTest extends TestCase
{
    public function testHandleRefreshesExpiringTokens()
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

        // Disk with refresh token expiring in 1 hour (should be refreshed)
        $disk1 = UserDisk::factory()->create([
            'type' => 'dcache',
            'options' => [
                'token' => 'old-token-1',
                'refresh_token' => 'old-refresh-token-1',
                'token_expires_at' => now()->addMinutes(30),
                'refresh_token_expires_at' => now()->addHour(),
            ],
        ]);

        // Disk with refresh token expiring in 2 hours exactly (should be refreshed)
        $disk2 = UserDisk::factory()->create([
            'type' => 'dcache',
            'options' => [
                'token' => 'old-token-2',
                'refresh_token' => 'old-refresh-token-2',
                'token_expires_at' => now()->addMinutes(30),
                'refresh_token_expires_at' => now()->addHours(2),
            ],
        ]);

        // Disk with refresh token expiring in 3 hours (should NOT be refreshed)
        $disk3 = UserDisk::factory()->create([
            'type' => 'dcache',
            'options' => [
                'token' => 'old-token-3',
                'refresh_token' => 'old-refresh-token-3',
                'token_expires_at' => now()->addMinutes(30),
                'refresh_token_expires_at' => now()->addHours(3),
            ],
        ]);

        // Non-dcache disk (should be ignored)
        $disk4 = UserDisk::factory()->create([
            'type' => 's3',
            'options' => [],
        ]);

        $this->artisan('user-disks:refresh-dcache-tokens')->assertExitCode(0);

        // Verify disk1 was refreshed
        $disk1->refresh();
        $this->assertEquals('new-access-token', $disk1->options['token']);
        $this->assertEquals('new-refresh-token', $disk1->options['refresh_token']);

        // Verify disk2 was refreshed
        $disk2->refresh();
        $this->assertEquals('new-access-token', $disk2->options['token']);
        $this->assertEquals('new-refresh-token', $disk2->options['refresh_token']);

        // Verify disk3 was NOT refreshed
        $disk3->refresh();
        $this->assertEquals('old-token-3', $disk3->options['token']);
        $this->assertEquals('old-refresh-token-3', $disk3->options['refresh_token']);

        // Verify HTTP was called exactly twice (for disk1 and disk2)
        Http::assertSentCount(2);
    }

    public function testHandleWithHttpError()
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
                'token_expires_at' => now()->addMinutes(30),
                'refresh_token_expires_at' => now()->addHour(),
            ],
        ]);

        $this->artisan('user-disks:refresh-dcache-tokens')->assertExitCode(0);

        // Verify disk was not modified
        $disk->refresh();
        $this->assertEquals('old-token', $disk->options['token']);
        $this->assertEquals('old-refresh-token', $disk->options['refresh_token']);
    }
}
