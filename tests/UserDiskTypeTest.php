<?php

namespace Biigle\Tests\Modules\UserDisks;

use Biigle\Modules\UserDisks\UserDiskType;
use ModelTestCase;

class UserDiskTypeTest extends ModelTestCase
{
    /**
     * The model class this class will test.
     */
    protected static $modelClass = UserDiskType::class;

    public function testAttributes()
    {
        $this->assertNotNull($this->model->name);
        $this->assertNull($this->model->created_at);
        $this->assertNull($this->model->updated_at);
    }

    public function testGetConfigTemplate()
    {
        $template = [
            'driver' => 'local',
            'key' => 'value',
        ];
        config(['user_disks.disk_templates.test' => $template]);

        $this->assertEquals($template, $this->model->getConfigTemplate());
    }

    public function testGetValidationRules()
    {
        $rules = [
            'driver' => 'required',
            'key' => 'filled',
        ];
        config(['user_disks.disk_validation.test' => $rules]);

        $this->assertEquals($rules, $this->model->getValidationRules());
    }
}
