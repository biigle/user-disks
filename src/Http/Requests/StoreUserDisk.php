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
            'type' => 'required|in:s3',
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

        // Automatically detect if a path-style endpoint is used.
        if ($this->input('type') === 's3') {
            $path = parse_url($this->input('endpoint'), PHP_URL_PATH);
            $options['use_path_style_endpoint'] = !is_null($path) && $path !== '/';
        }

        return $options;
    }
}
