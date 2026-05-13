<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfilePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'old_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', 'min:6', 'max:20'],
        ];
    }
}
