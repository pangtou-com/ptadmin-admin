<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use PTAdmin\Admin\Models\Admin;
use PTAdmin\Foundation\Auth\AdminAuth;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $adminId = (int) AdminAuth::user()->getAuthIdentifier();

        return [
            'nickname' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique(Admin::class)->whereNull('deleted_at')->ignore($adminId)],
            'mobile' => ['nullable', 'string', 'max:20', Rule::unique(Admin::class)->whereNull('deleted_at')->ignore($adminId)],
        ];
    }
}
