<?php

namespace Biigle\Modules\UserDisks\Http\Requests;

use Biigle\Modules\UserDisks\UserDisk;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserDisk extends FormRequest
{
    /**
     * The user disk that should be updated.
     *
     * @var UserDisk
     */
    public $disk;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->disk = UserDisk::findOrFail($this->route('id'));

        return $this->user()->can('update', $this->disk);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return array_merge([
            'name' => 'filled',
        ], $this->getTypeValidationRules());
    }

    /**
     * Get additional validation rules for a storage disk type.
     *
     * @return array
     */
    public function getTypeValidationRules()
    {
        return UserDisk::getUpdateValidationRules($this->disk->type);
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Remove empty fields.
        $this->replace(array_filter($this->all(), fn ($value) => !is_null($value)));
    }

    /**
     * Get the storage disk options from the input of this request.
     *
     * @return array
     */
    public function getDiskOptions()
    {
        $optionKeys = array_keys($this->getTypeValidationRules());
        $options = $this->safe()->only($optionKeys);

        foreach ($this->rules() as $key => $rules) {
            if (in_array('boolean', explode('|', $rules)) && array_key_exists($key, $options)) {
                $options[$key] = boolval($options[$key]);
            }
        }

        return $options;
    }
}
