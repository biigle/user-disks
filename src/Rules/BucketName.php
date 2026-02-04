<?php

namespace Biigle\Modules\UserDisks\Rules;

use Illuminate\Support\Str;
use Illuminate\Contracts\Validation\Rule;

class BucketName implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $sanitizedValue = preg_quote($value, '/');

        // Bucket name must use only lower case character, numbers, hyphens and periods
        $remainder = preg_replace("/[a-z0-9]*/", '', $sanitizedValue);
        $remainder = Str::replace(["\.", "\-"], "", $remainder);

        if (strlen($remainder) > 0) {
            return false;
        }

        // Periods must not occur consecutively
        if (preg_match('/(\\\.){2,}/', $sanitizedValue)) {
            return false;
        }

        // Bucket name must start with a lower case character or number
        if (!preg_match('/^[a-z0-9]/', $sanitizedValue)) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The bucket name contains invalid characters.';
    }
}
