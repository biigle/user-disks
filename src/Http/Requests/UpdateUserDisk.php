<?php

namespace Biigle\Modules\UserDisks\Http\Requests;

use Biigle\Modules\UserDisks\UserDisk;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Uri;

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
        $this->disk = UserDisk::findOrFail($this->route('id'));

        // Remove empty fields.
        $this->replace(array_filter($this->all(), fn ($value) => !is_null($value)));

        if ($this->disk->type === 'webdav' && $this->has('baseUri')) {
            $uri = Uri::of($this->input('baseUri'));
            $path = $uri->path();
            if ($path && $path !== '/') {
                $this->merge([
                    'baseUri' => (string) $uri->withPath(''),
                    'pathPrefix' => $path,
                ]);
            }

        }
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

        // Automatically detect if a path-style endpoint is used.
        if ($this->disk->type === 's3' && $this->has('endpoint')) {
            $path = parse_url($this->input('endpoint'), PHP_URL_PATH);
            $options['use_path_style_endpoint'] = !is_null($path) && $path !== '/';
        }

        return $options;
    }
}
