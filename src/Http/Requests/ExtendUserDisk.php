<?php

namespace Biigle\Modules\UserDisks\Http\Requests;

use Biigle\Modules\UserDisks\UserDisk;
use Illuminate\Foundation\Http\FormRequest;

class ExtendUserDisk extends FormRequest
{
    /**
     * Storage disk that should be approved.
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
        return [
            //
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->disk->isAboutToExpire()) {
                $validator->errors()->add('id', "The storage disk is not about to expire.");
            }
        });
    }
}
