<?php

namespace Biigle\Modules\UserDisks\Http\Requests;

use Biigle\Modules\UserDisks\UserDisk;
use Illuminate\Foundation\Http\FormRequest;

class StoreuserDisk extends FormRequest
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
        $typeRules = UserDisk::getValidationRules($this->input('type')) ?? [];

        return array_merge([
            'name' => 'required',
            'type' => 'required|in:s3',
        ], $typeRules);
    }
}