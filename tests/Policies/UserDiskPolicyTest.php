<?php

namespace Biigle\Tests\Modules\UserDisks\Policies;

use ApiTestCase;
use Biigle\Modules\UserDisks\UserDisk;
use Biigle\Role;

class UserDiskPolicyTest extends ApiTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->request = UserDisk::factory()->create(['user_id' => $this->editor()->id]);
    }

    public function testCreate()
    {
        $this->assertFalse($this->globalGuest()->can('create', UserDisk::class));
        $this->assertTrue($this->user()->can('create', UserDisk::class));
        $this->assertTrue($this->guest()->can('create', UserDisk::class));
        $this->assertTrue($this->editor()->can('create', UserDisk::class));
        $this->assertTrue($this->expert()->can('create', UserDisk::class));
        $this->assertTrue($this->admin()->can('create', UserDisk::class));
        $this->assertTrue($this->globalAdmin()->can('create', UserDisk::class));
    }

    public function testAccess()
    {
        $this->assertFalse($this->globalGuest()->can('access', $this->request));
        $this->assertFalse($this->user()->can('access', $this->request));
        $this->assertFalse($this->guest()->can('access', $this->request));
        $this->assertTrue($this->editor()->can('access', $this->request));
        $this->assertFalse($this->expert()->can('access', $this->request));
        $this->assertFalse($this->admin()->can('access', $this->request));
        $this->assertFalse($this->globalAdmin()->can('access', $this->request));
    }

    public function testUpdate()
    {
        $this->assertFalse($this->globalGuest()->can('update', $this->request));
        $this->assertFalse($this->user()->can('update', $this->request));
        $this->assertFalse($this->guest()->can('update', $this->request));
        $this->assertTrue($this->editor()->can('update', $this->request));
        $this->assertFalse($this->expert()->can('update', $this->request));
        $this->assertFalse($this->admin()->can('update', $this->request));
        $this->assertFalse($this->globalAdmin()->can('update', $this->request));
    }

    public function testDestroy()
    {
        $this->assertFalse($this->globalGuest()->can('destroy', $this->request));
        $this->assertFalse($this->user()->can('destroy', $this->request));
        $this->assertFalse($this->guest()->can('destroy', $this->request));
        $this->assertTrue($this->editor()->can('destroy', $this->request));
        $this->assertFalse($this->expert()->can('destroy', $this->request));
        $this->assertFalse($this->admin()->can('destroy', $this->request));
        $this->assertTrue($this->globalAdmin()->can('destroy', $this->request));
    }
}
