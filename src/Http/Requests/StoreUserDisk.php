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
        switch ($this->input('type')) {
            case 's3':
                return [
                    'key' => 'required',
                    'secret' => 'required',
                    'region' => 'required',
                    'bucket' => 'required',
                    'endpoint' => 'required|url',
                    'use_path_style_endpoint' => 'boolean',
                ];
            default:
                return [];
        }
    }
}
