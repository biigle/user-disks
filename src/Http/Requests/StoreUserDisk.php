<?php

namespace Biigle\Modules\UserDisks\Http\Requests;

use Biigle\Modules\UserDisks\UserDisk;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserDisk extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', UserDisk::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return array_merge([
            'name' => 'required',
            'type' => 'required|in:s3,aruna',
        ], $this->getTypeValidationRules());
    }

    /**
     * Get additional validation rules for a storage disk type.
     *
     * @return array
     */
    public function getTypeValidationRules()
    {
        return UserDisk::getStoreValidationRules($this->input('type')) ?: [];
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
        $options = array_filter($options, fn ($v) => !is_null($v));

        return $options;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Different user may have equal disk names, but disk names of one user must be unqiue
            if (UserDisk::where(['user_id' => $this->user()->id, 'name' => $this->input('name')])->exists()) {
                $validator->errors()->add('name', 'Disk name already exists');
            }
        });
    }
}
